<?php

namespace App\Features\Client\Subscription\Services;

use App\Exceptions\ApiException;
use App\Features\Captcha\Support\CaptchaPlanCatalog;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Arr;

class SubscriptionQuotaService
{
    public function getTotalAccountLimit(UserSubscription $subscription): int
    {
        return $subscription->base_account_limit + $subscription->extra_account_limit;
    }

    public function getAvailableAccountLimit(UserSubscription $subscription): int
    {
        return max(0, $this->getTotalAccountLimit($subscription) - $subscription->used_account);
    }

    public function ensureCanCreateAccount(UserSubscription $subscription, int $quantity = 1): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new ApiException('Subscription hiện không hoạt động.', 422);
        }

        if ($subscription->expires_at->isPast()) {
            throw new ApiException('Subscription đã hết hạn.', 422);
        }

        if ($this->getAvailableAccountLimit($subscription) < $quantity) {
            throw new ApiException('Đã vượt quota tài khoản cho phép.', 422, [
                'available' => $this->getAvailableAccountLimit($subscription),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveCaptchaLimits(UserSubscription $subscription): array
    {
        return CaptchaPlanCatalog::resolve(
            is_array($subscription->package_limits) ? $subscription->package_limits : null,
        );
    }

    public function supportsService(UserSubscription $subscription, string $serviceCode): bool
    {
        $serviceWhitelist = Arr::get($this->resolveCaptchaLimits($subscription), 'service_whitelist', []);

        return is_array($serviceWhitelist) && in_array(trim($serviceCode), $serviceWhitelist, true);
    }

    public function hasUnlimitedCaptchaQuota(UserSubscription $subscription): bool
    {
        return Arr::get($this->resolveCaptchaLimits($subscription), 'monthly_captcha_quota') === null;
    }

    public function getRemainingCaptchaQuota(UserSubscription $subscription): ?int
    {
        if ($this->hasUnlimitedCaptchaQuota($subscription)) {
            return null;
        }

        $limit = (int) Arr::get($this->resolveCaptchaLimits($subscription), 'monthly_captcha_quota', 0);

        return max(0, $limit - (int) $subscription->used_captcha_quota);
    }

    public function ensureCanConsumeCaptcha(UserSubscription $subscription, string $serviceCode, int $quantity = 1): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            throw new ApiException('Gói captcha hiện không hoạt động.', 422);
        }

        if ($subscription->expires_at === null || $subscription->expires_at->isPast()) {
            throw new ApiException('Gói captcha đã hết hạn.', 422);
        }

        if (! $this->supportsService($subscription, $serviceCode)) {
            throw new ApiException('Loại captcha này không nằm trong gói hiện tại.', 422);
        }

        $remainingQuota = $this->getRemainingCaptchaQuota($subscription);

        if ($remainingQuota !== null && $remainingQuota < $quantity) {
            throw new ApiException('Gói captcha không còn đủ lượt sử dụng.', 422, [
                'remaining_quota' => $remainingQuota,
            ]);
        }
    }

    public function consumeCaptcha(UserSubscription $subscription, string $serviceCode, int $quantity = 1): UserSubscription
    {
        $this->ensureCanConsumeCaptcha($subscription, $serviceCode, $quantity);

        $usageByService = is_array($subscription->captcha_usage_by_service) ? $subscription->captcha_usage_by_service : [];
        $usageByService[$serviceCode] = max(0, (int) ($usageByService[$serviceCode] ?? 0)) + $quantity;

        $subscription->forceFill([
            'used_captcha_quota' => (int) $subscription->used_captcha_quota + $quantity,
            'captcha_usage_by_service' => $usageByService,
        ])->save();

        return $subscription->fresh();
    }
}
