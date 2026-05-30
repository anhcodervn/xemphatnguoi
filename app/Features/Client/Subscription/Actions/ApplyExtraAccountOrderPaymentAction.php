<?php

namespace App\Features\Client\Subscription\Actions;

use App\Exceptions\ApiException;
use App\Models\ExtraAccountOrder;
use App\Models\UserSubscription;
use App\Support\Enums\ExtraAccountOrderStatus;

class ApplyExtraAccountOrderPaymentAction
{
    public function handle(ExtraAccountOrder $extraAccountOrder): ExtraAccountOrder
    {
        $extraAccountOrder->loadMissing('subscription');

        if ($extraAccountOrder->status !== ExtraAccountOrderStatus::Paid) {
            throw new ApiException('Đơn mua thêm slot chưa được thanh toán.', 422);
        }

        $subscription = UserSubscription::query()
            ->whereKey($extraAccountOrder->user_subscription_id)
            ->lockForUpdate()
            ->firstOrFail();

        $subscription->increment('extra_account_limit', $extraAccountOrder->quantity);

        $extraAccountOrder->forceFill([
            'expired_at' => $subscription->expires_at,
        ])->save();

        return $extraAccountOrder->fresh(['subscription']);
    }
}
