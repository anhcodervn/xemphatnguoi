<?php

namespace App\Features\Client\Subscription\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Subscription\Actions\CreateUserSubscriptionFromPaidOrderAction;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\PaymentStatus;
use App\Utils\SendMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageOrderService
{
    public function __construct(
        private readonly CreateUserSubscriptionFromPaidOrderAction $createUserSubscriptionFromPaidOrderAction,
    ) {}

    /**
     * @param  array{discount_amount?:mixed,payment_method?:mixed}  $payload
     */
    public function createOrder(User $user, Package $package, array $payload = []): PackageOrder
    {
        if ($package->status !== PackageStatus::Active) {
            throw new ApiException('Gói hiện không khả dụng.', 422);
        }

        $discountAmount = (float) ($payload['discount_amount'] ?? 0);
        $finalAmount = max(0, (float) $package->price - $discountAmount);

        return PackageOrder::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'order_code' => $this->generateOrderCode(),
            'price' => $package->price,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'payment_method' => $payload['payment_method'] ?? null,
            'payment_status' => PaymentStatus::Pending,
            'status' => PackageOrderStatus::Pending,
            'paid_at' => null,
        ]);
    }

    public function markAsPaid(PackageOrder $packageOrder, ?string $paymentMethod = null): UserSubscription
    {
        $shouldNotify = false;

        $subscription = DB::transaction(function () use ($packageOrder, $paymentMethod, &$shouldNotify): UserSubscription {
            $lockedOrder = PackageOrder::query()
                ->whereKey($packageOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status === PaymentStatus::Paid && $lockedOrder->subscription !== null) {
                return $lockedOrder->subscription;
            }

            $lockedOrder->forceFill([
                'payment_method' => $paymentMethod ?? $lockedOrder->payment_method,
                'payment_status' => PaymentStatus::Paid,
                'status' => PackageOrderStatus::Completed,
                'paid_at' => $lockedOrder->paid_at ?? now(),
            ])->save();

            $subscription = $this->createUserSubscriptionFromPaidOrderAction->handle($lockedOrder->fresh(['package']));
            $shouldNotify = true;

            return $subscription;
        });

        if ($shouldNotify) {
            $packageOrder->loadMissing('user', 'package', 'sourceSubscription');

            SendMessage::sendInfoReport('Người dùng đăng ký gói thành công', [
                'User ID' => $packageOrder->user_id,
                'Username' => $packageOrder->user?->username,
                'Package' => $packageOrder->package?->name,
                'Order code' => $packageOrder->order_code,
                'Thanh toán' => $paymentMethod ?? $packageOrder->payment_method,
                'Giá gói' => $packageOrder->price,
                'Giảm giá' => $packageOrder->discount_amount,
                'Thành tiền' => $packageOrder->final_amount,
                'Subscription ID' => $subscription->id,
                'Bắt đầu' => $subscription->starts_at,
                'Hết hạn' => $subscription->expires_at,
            ]);
        }

        return $subscription;
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'PKG-'.Str::upper(Str::random(10));
        } while (PackageOrder::query()->where('order_code', $code)->exists());

        return $code;
    }
}
