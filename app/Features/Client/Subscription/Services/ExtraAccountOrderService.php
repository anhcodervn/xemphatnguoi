<?php

namespace App\Features\Client\Subscription\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Subscription\Actions\ApplyExtraAccountOrderPaymentAction;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ExtraAccountOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\ExtraAccountOrderStatus;
use App\Support\Enums\SubscriptionStatus;
use App\Utils\SendMessage;
use Illuminate\Support\Facades\DB;

class ExtraAccountOrderService
{
    public function __construct(
        private readonly ApplyExtraAccountOrderPaymentAction $applyExtraAccountOrderPaymentAction,
        private readonly WalletService $walletService,
    ) {}

    public function createOrder(UserSubscription $subscription, int $quantity): ExtraAccountOrder
    {
        $subscription->loadMissing('package');

        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new ApiException('Subscription hiện không hoạt động.', 422);
        }

        if (! $subscription->package->can_buy_extra_account) {
            throw new ApiException('Gói này không cho phép mua thêm slot.', 422);
        }

        return ExtraAccountOrder::query()->create([
            'user_subscription_id' => $subscription->id,
            'quantity' => $quantity,
            'price' => $subscription->package->extra_account_price * $quantity,
            'status' => ExtraAccountOrderStatus::Pending,
            'expired_at' => null,
        ]);
    }

    public function markAsPaid(User $user, ExtraAccountOrder $extraAccountOrder): ExtraAccountOrder
    {
        $shouldNotify = false;

        $paidOrder = DB::transaction(function () use ($user, $extraAccountOrder, &$shouldNotify): ExtraAccountOrder {
            $lockedOrder = ExtraAccountOrder::query()
                ->with(['subscription.package'])
                ->whereKey($extraAccountOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status === ExtraAccountOrderStatus::Paid) {
                return $lockedOrder->fresh(['subscription']);
            }

            if ((float) $lockedOrder->price > 0) {
                $this->walletService->debit(
                    user: $user,
                    amount: (float) $lockedOrder->price,
                    referenceType: ExtraAccountOrder::class,
                    referenceId: $lockedOrder->id,
                    description: sprintf('Thanh toán đơn mua thêm thẻ #%d', $lockedOrder->id),
                );
            }

            $lockedOrder->forceFill([
                'status' => ExtraAccountOrderStatus::Paid,
            ])->save();

            $paidOrder = $this->applyExtraAccountOrderPaymentAction->handle($lockedOrder);
            $shouldNotify = true;

            return $paidOrder;
        });

        if ($shouldNotify) {
            $paidOrder->loadMissing('subscription.package');

            SendMessage::sendInfoReport('Người dùng mua thêm extra thành công', [
                'User ID' => $user->id,
                'Username' => $user->username,
                'Order ID' => $paidOrder->id,
                'Package' => $paidOrder->subscription?->package?->name,
                'Subscription ID' => $paidOrder->user_subscription_id,
                'Số lượng extra' => $paidOrder->quantity,
                'Số tiền' => $paidOrder->price,
                'Tổng extra hiện tại' => $paidOrder->subscription?->extra_account_limit,
                'Hiệu lực đến' => $paidOrder->expired_at,
            ]);
        }

        return $paidOrder;
    }
}
