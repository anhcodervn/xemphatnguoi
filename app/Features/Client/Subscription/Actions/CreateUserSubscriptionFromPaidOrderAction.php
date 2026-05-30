<?php

namespace App\Features\Client\Subscription\Actions;

use App\Exceptions\ApiException;
use App\Models\PackageOrder;
use App\Models\UserSubscription;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;

class CreateUserSubscriptionFromPaidOrderAction
{
    public function handle(PackageOrder $packageOrder): UserSubscription
    {
        $packageOrder->loadMissing('package');

        if ($packageOrder->payment_status !== PaymentStatus::Paid) {
            throw new ApiException('Đơn hàng gói chưa được thanh toán.', 422);
        }

        $paidAt = $packageOrder->paid_at ?? now();
        $expiresAt = $paidAt->copy()->addDays($packageOrder->package->duration_days);

        return UserSubscription::query()->firstOrCreate([
            'order_id' => $packageOrder->id,
        ], [
            'user_id' => $packageOrder->user_id,
            'package_id' => $packageOrder->package_id,
            'package_name' => $packageOrder->package->name,
            'package_price' => $packageOrder->price,
            'base_account_limit' => $packageOrder->package->account_limit,
            'extra_account_limit' => 0,
            'used_account' => 0,
            'starts_at' => $paidAt,
            'expires_at' => $expiresAt,
            'status' => $expiresAt->isPast() ? SubscriptionStatus::Expired : SubscriptionStatus::Active,
        ]);
    }
}
