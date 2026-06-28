<?php

namespace App\Features\Client\Package\Services;

use App\Features\Cron\Support\CronPackageCatalog;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;

class PackageService
{
    /**
     * @return array{
     *     id:int,
     *     user_id:int,
     *     name:string,
     *     package_id:int,
     *     order_id:?int,
     *     package_name:string,
     *     package_price:string,
     *     base_account_limit:int,
     *     extra_account_limit:int,
 *     used_account:int,
 *     auto_renew_enabled:bool,
 *     starts_at:?string,
 *     expires_at:?string,
 *     status:string,
     *     package:array{
     *         id:int,
     *         name:string,
     *         slug:string,
     *         description:?string,
     *         price:string,
     *         duration_days:int,
     *         account_limit:int,
     *         can_buy_extra_account:bool,
     *         extra_account_price:string,
     *         request_limit:int,
     *         request_per_minute:int,
     *         concurrent_limit:int,
     *         features:array<int|string, mixed>|null,
     *         status:string
     *     }
     * }|null
     */
    public function getCurrentUserSubscriptionInfo(User $user): ?array
    {
        $subscription = $this->getCurrentSubscription($user);

        if (! $subscription instanceof UserSubscription) {
            return null;
        }

        $package = $subscription->package;

        if (! $package instanceof Package) {
            return null;
        }

        return [
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'package_id' => $subscription->package_id,
            'order_id' => $subscription->order_id,
            'package_name' => $subscription->package_name,
            'package_price' => (string) $subscription->package_price,
            'package_limits' => CronPackageCatalog::resolve(
                overrides: is_array($subscription->package_limits) ? $subscription->package_limits : null,
                package: $package,
                subscription: $subscription,
            ),
            'base_account_limit' => $subscription->base_account_limit,
            'extra_account_limit' => $subscription->extra_account_limit,
            'used_account' => $user->cronJobs()->count(),
            'auto_renew_enabled' => (bool) $subscription->auto_renew_enabled,
            'starts_at' => $subscription->starts_at?->toISOString(),
            'expires_at' => $subscription->expires_at?->toISOString(),
            'status' => $subscription->status->value,
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'description' => $package->description,
                'price' => (string) $package->price,
                'duration_days' => $package->duration_days,
                'account_limit' => $package->account_limit,
                'can_buy_extra_account' => $package->can_buy_extra_account,
                'extra_account_price' => (string) $package->extra_account_price,
                'request_limit' => $package->request_limit,
                'request_per_minute' => $package->request_per_minute,
                'concurrent_limit' => $package->concurrent_limit,
                'features' => $package->features,
                'package_limits' => CronPackageCatalog::resolve(
                    overrides: is_array($package->package_limits) ? $package->package_limits : null,
                    package: $package,
                ),
                'status' => $package->status->value,
            ],
        ];
    }

    public function getCurrentSubscription(User $user): ?UserSubscription
    {
        $activeSubscription = $this->getActiveSubscription($user);

        if ($activeSubscription instanceof UserSubscription) {
            return $activeSubscription;
        }

        return $user->userSubscriptions()
            ->with('package')
            ->latest('expires_at')
            ->latest('id')
            ->first();
    }

    public function getActiveSubscription(User $user): ?UserSubscription
    {
        return $user->userSubscriptions()
            ->with('package')
            ->where('status', SubscriptionStatus::Active)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();
    }

    public function updateAutoRenew(UserSubscription $subscription, bool $enabled): UserSubscription
    {
        $payload = [
            'auto_renew_enabled' => $enabled,
        ];

        if ($enabled) {
            $payload['auto_renew_attempted_at'] = null;
            $payload['auto_renew_status'] = null;
            $payload['auto_renew_message'] = null;
        }

        $subscription->forceFill($payload)->save();

        return $subscription->fresh(['package']);
    }
}
