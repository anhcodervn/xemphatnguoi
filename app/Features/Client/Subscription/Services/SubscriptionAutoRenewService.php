<?php

namespace App\Features\Client\Subscription\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Package\Services\PackageCheckoutService;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\UserSubscription;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;
use App\Support\MailQueue;
use Illuminate\Support\Facades\DB;

class SubscriptionAutoRenewService
{
    public function __construct(
        private readonly PackageCheckoutService $packageCheckoutService,
        private readonly MailQueue $mailQueue,
    ) {}

    /**
     * @return array{processed:int,renewed:int,failed:int,skipped:int,expired_only:int}
     */
    public function processDueSubscriptions(int $limit = 100): array
    {
        $summary = [
            'processed' => 0,
            'renewed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'expired_only' => 0,
        ];

        $dueSubscriptions = UserSubscription::query()
            ->with(['user', 'package'])
            ->where('expires_at', '<=', now())
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Expired])
            ->orderBy('expires_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($dueSubscriptions as $subscription) {
            $result = $this->processSubscription($subscription);

            $summary['processed']++;
            $summary[$result] = ($summary[$result] ?? 0) + 1;
        }

        return $summary;
    }

    private function processSubscription(UserSubscription $subscription): string
    {
        $mailPayload = null;
        $result = DB::transaction(function () use ($subscription, &$mailPayload): string {
            /** @var UserSubscription $lockedSubscription */
            $lockedSubscription = UserSubscription::query()
                ->with(['user', 'package'])
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubscription->status === SubscriptionStatus::Cancelled) {
                return 'skipped';
            }

            if ($lockedSubscription->expires_at !== null && $lockedSubscription->expires_at->lte(now()) && $lockedSubscription->status !== SubscriptionStatus::Expired) {
                $lockedSubscription->forceFill([
                    'status' => SubscriptionStatus::Expired,
                ])->save();
            }

            if (! $lockedSubscription->auto_renew_enabled) {
                return 'expired_only';
            }

            if ($lockedSubscription->auto_renew_attempted_at !== null) {
                return match ($lockedSubscription->auto_renew_status) {
                    'success' => 'renewed',
                    'failed' => 'failed',
                    default => 'skipped',
                };
            }

            $existingPaidRenewal = PackageOrder::query()
                ->where('source_subscription_id', $lockedSubscription->id)
                ->where('payment_status', PaymentStatus::Paid)
                ->exists();

            if ($existingPaidRenewal) {
                $lockedSubscription->forceFill([
                    'auto_renew_attempted_at' => now(),
                    'auto_renew_status' => 'skipped',
                    'auto_renew_message' => 'Subscription already renewed before the automatic renewal job ran.',
                ])->save();

                return 'skipped';
            }

            $existingPendingRenewal = PackageOrder::query()
                ->where('source_subscription_id', $lockedSubscription->id)
                ->where('payment_status', PaymentStatus::Pending)
                ->where('status', PackageOrderStatus::Pending)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            if ($existingPendingRenewal) {
                $lockedSubscription->forceFill([
                    'auto_renew_attempted_at' => now(),
                    'auto_renew_status' => 'skipped',
                    'auto_renew_message' => 'A pending renewal order already exists for this subscription.',
                ])->save();

                return 'skipped';
            }

            $user = $lockedSubscription->user;
            $package = $lockedSubscription->package;

            if (! $package instanceof Package) {
                $this->markFailed($lockedSubscription, 'Không tìm thấy gói để tự gia hạn.');
                $mailPayload = $this->buildFailureMailPayload($lockedSubscription, 'Không tìm thấy gói để tự gia hạn.');

                return 'failed';
            }

            try {
                $order = $this->packageCheckoutService->createOrder($user, $package, [
                    'payment_method' => 'wallet',
                    'auto_renew_enabled' => true,
                ]);

                $payResult = $this->packageCheckoutService->payWithWallet($user, $order);

                $lockedSubscription->forceFill([
                    'status' => SubscriptionStatus::Expired,
                    'auto_renew_attempted_at' => now(),
                    'auto_renew_status' => 'success',
                    'auto_renew_message' => sprintf('Auto-renewed successfully via order %s.', $payResult['order']->order_code),
                ])->save();

                $mailPayload = $this->buildSuccessMailPayload(
                    expiredSubscription: $lockedSubscription,
                    renewedSubscription: $payResult['subscription'],
                    order: $payResult['order'],
                );

                return 'renewed';
            } catch (ApiException $exception) {
                if (isset($order) && $order instanceof PackageOrder) {
                    $order->forceFill([
                        'payment_status' => PaymentStatus::Failed,
                        'status' => PackageOrderStatus::Cancelled,
                    ])->save();
                }

                $this->markFailed($lockedSubscription, $exception->getMessage());
                $mailPayload = $this->buildFailureMailPayload($lockedSubscription, $exception->getMessage());

                return 'failed';
            }
        });

        if (is_array($mailPayload)) {
            $this->mailQueue->dispatch(
                to: $mailPayload['to'],
                subjectText: $mailPayload['subject'],
                title: $mailPayload['title'],
                messageLines: $mailPayload['lines'],
            );
        }

        return $result;
    }

    private function markFailed(UserSubscription $subscription, string $message): void
    {
        $subscription->forceFill([
            'status' => SubscriptionStatus::Expired,
            'auto_renew_attempted_at' => now(),
            'auto_renew_status' => 'failed',
            'auto_renew_message' => $message,
        ])->save();
    }

    /**
     * @return array{to:string,subject:string,title:string,lines:array<int,string>}|null
     */
    private function buildSuccessMailPayload(UserSubscription $expiredSubscription, UserSubscription $renewedSubscription, PackageOrder $order): ?array
    {
        $user = $expiredSubscription->user;
        $email = is_string($user?->email) ? trim($user->email) : '';

        if ($email === '') {
            return null;
        }

        return [
            'to' => $email,
            'subject' => 'Tự gia hạn gói thành công',
            'title' => 'Tự gia hạn gói dịch vụ thành công',
            'lines' => [
                sprintf('Gói: %s', $renewedSubscription->package_name),
                sprintf('Mã đơn hàng: %s', $order->order_code),
                sprintf('Số tiền đã trừ từ ví: %sđ', number_format((float) $order->final_amount, 0, ',', '.')),
                sprintf('Thời gian mới: %s đến %s', optional($renewedSubscription->starts_at)?->format('d/m/Y H:i'), optional($renewedSubscription->expires_at)?->format('d/m/Y H:i')),
                'Tự gia hạn vẫn đang được bật cho chu kỳ tiếp theo.',
            ],
        ];
    }

    /**
     * @return array{to:string,subject:string,title:string,lines:array<int,string>}|null
     */
    private function buildFailureMailPayload(UserSubscription $subscription, string $reason): ?array
    {
        $user = $subscription->user;
        $email = is_string($user?->email) ? trim($user->email) : '';

        if ($email === '') {
            return null;
        }

        return [
            'to' => $email,
            'subject' => 'Tự gia hạn gói thất bại',
            'title' => 'Tự gia hạn gói dịch vụ thất bại',
            'lines' => [
                sprintf('Gói: %s', $subscription->package_name),
                sprintf('Hết hạn lúc: %s', optional($subscription->expires_at)?->format('d/m/Y H:i') ?? '--'),
                sprintf('Lý do: %s', $reason),
                'Nếu muốn tiếp tục sử dụng, vui lòng nạp thêm số dư ví rồi gia hạn lại thủ công.',
            ],
        ];
    }
}
