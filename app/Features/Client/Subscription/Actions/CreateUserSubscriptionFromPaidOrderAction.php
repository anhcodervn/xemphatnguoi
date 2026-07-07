<?php

namespace App\Features\Client\Subscription\Actions;

use App\Exceptions\ApiException;
use App\Features\Captcha\Support\CaptchaPlanCatalog;
use App\Models\PackageOrder;
use App\Models\UserSubscription;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;

class CreateUserSubscriptionFromPaidOrderAction
{
    public function handle(PackageOrder $packageOrder): UserSubscription
    {
        $packageOrder->loadMissing(['package', 'user']);

        if ($packageOrder->payment_status !== PaymentStatus::Paid) {
            throw new ApiException('Đơn hàng gói chưa được thanh toán.', 422);
        }

        $paidAt = $packageOrder->paid_at ?? now();
        $expiresAt = $paidAt->copy()->addDays($packageOrder->package->duration_days);
        $resolvedPackageLimits = CaptchaPlanCatalog::resolve(
            is_array($packageOrder->package->package_limits) ? $packageOrder->package->package_limits : null,
        );

        return UserSubscription::query()->firstOrCreate([
            'order_id' => $packageOrder->id,
        ], [
            'user_id' => $packageOrder->user_id,
            'package_id' => $packageOrder->package_id,
            'package_name' => $packageOrder->package->name,
            'package_price' => $packageOrder->price,
            'package_limits' => $resolvedPackageLimits,
            'base_account_limit' => max(
                (int) $packageOrder->package->account_limit,
                (int) ($resolvedPackageLimits['max_api_keys'] ?? 0),
            ),
            'extra_account_limit' => 0,
            'used_account' => 0,
            'used_captcha_quota' => 0,
            'captcha_usage_by_service' => [],
            'auto_renew_enabled' => (bool) $packageOrder->auto_renew_enabled,
            'starts_at' => $paidAt,
            'expires_at' => $expiresAt,
            'status' => $expiresAt->isPast() ? SubscriptionStatus::Expired : SubscriptionStatus::Active,
        ]);
    }
}
