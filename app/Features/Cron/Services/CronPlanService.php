<?php

namespace App\Features\Cron\Services;

use App\Exceptions\ApiException;
use App\Features\Cron\Support\CronPackageCatalog;
use App\Models\CronJob;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;

class CronPlanService
{
    public function activeSubscription(User $user): ?UserSubscription
    {
        return $user->userSubscriptions()
            ->with('package')
            ->where('status', SubscriptionStatus::Active)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->latest('id')
            ->first();
    }

    public function requireActiveSubscription(User $user): UserSubscription
    {
        $subscription = $this->activeSubscription($user);

        if (! $subscription instanceof UserSubscription) {
            throw new ApiException('Bạn cần gói thuê đang hoạt động để sử dụng AutoCron.', 422);
        }

        return $subscription;
    }

    /**
     * @return array<string, mixed>
     */
    public function limitsForUser(User $user): array
    {
        return $this->limitsForSubscription($this->activeSubscription($user));
    }

    /**
     * @return array<string, mixed>
     */
    public function limitsForSubscription(?UserSubscription $subscription): array
    {
        if (! $subscription instanceof UserSubscription) {
            return CronPackageCatalog::defaults();
        }

        return CronPackageCatalog::resolve(
            overrides: is_array($subscription->package_limits) ? $subscription->package_limits : null,
            package: $subscription->relationLoaded('package') && $subscription->package instanceof Package ? $subscription->package : null,
            subscription: $subscription,
        );
    }

    public function ensureCronJobCapacity(User $user, ?CronJob $ignoreCronJob = null): void
    {
        $limits = $this->limitsForUser($user);
        $maxCronJobs = (int) ($limits['max_cron_jobs'] ?? 0);

        $currentCount = $user->cronJobs()
            ->when($ignoreCronJob instanceof CronJob, fn ($query) => $query->whereKeyNot($ignoreCronJob->id))
            ->count();

        if ($currentCount >= $maxCronJobs) {
            throw new ApiException(sprintf('Bạn đã đạt giới hạn %d cron jobs của gói hiện tại.', $maxCronJobs), 422);
        }
    }

    public function syncSubscriptionUsage(User $user): void
    {
        $subscription = $this->activeSubscription($user);

        if (! $subscription instanceof UserSubscription) {
            return;
        }

        $subscription->forceFill([
            'used_account' => $user->cronJobs()->count(),
            'base_account_limit' => (int) ($this->limitsForSubscription($subscription)['max_cron_jobs'] ?? 0),
        ])->save();
    }
}
