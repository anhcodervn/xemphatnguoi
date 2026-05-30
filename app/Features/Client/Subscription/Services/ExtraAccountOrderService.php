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
        return DB::transaction(function () use ($user, $extraAccountOrder): ExtraAccountOrder {
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

            return $this->applyExtraAccountOrderPaymentAction->handle($lockedOrder);
        });
    }
}
