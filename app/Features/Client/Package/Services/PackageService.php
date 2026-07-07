<?php

namespace App\Features\Client\Package\Services;

use App\Exceptions\ApiException;
use App\Features\Captcha\Support\CaptchaPlanCatalog;
use App\Models\ApiKey;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Collection;

class PackageService
{
    public function getCurrentUserSubscriptionInfo(User $user): ?array
    {
        $subscription = $this->getCurrentSubscription($user);

        return $subscription instanceof UserSubscription
            ? $this->transformSubscription($subscription)
            : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveUserSubscriptionsInfo(User $user): array
    {
        return $this->getActiveSubscriptions($user)
            ->map(fn (UserSubscription $subscription): array => $this->transformSubscription($subscription))
            ->values()
            ->all();
    }

    public function getCurrentSubscription(User $user): ?UserSubscription
    {
        $activeSubscription = $this->getActiveSubscription($user);

        if ($activeSubscription instanceof UserSubscription) {
            return $activeSubscription;
        }

        return $user->userSubscriptions()
            ->with(['package', 'apiKeys'])
            ->latest('expires_at')
            ->latest('id')
            ->first();
    }

    public function getActiveSubscription(User $user): ?UserSubscription
    {
        return $this->getActiveSubscriptions($user)->first();
    }

    /**
     * @return Collection<int, UserSubscription>
     */
    public function getActiveSubscriptions(User $user)
    {
        return $user->userSubscriptions()
            ->with(['package', 'apiKeys'])
            ->where('status', SubscriptionStatus::Active)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->latest('id')
            ->get();
    }

    public function getActiveSubscriptionForService(User $user, string $serviceCode): ?UserSubscription
    {
        return $this->getActiveSubscriptions($user)
            ->first(function (UserSubscription $subscription) use ($serviceCode): bool {
                $limits = CaptchaPlanCatalog::resolve(
                    is_array($subscription->package_limits) ? $subscription->package_limits : null,
                );

                $serviceWhitelist = $limits['service_whitelist'] ?? [];

                if (! is_array($serviceWhitelist) || ! in_array(trim($serviceCode), $serviceWhitelist, true)) {
                    return false;
                }

                $quota = $limits['monthly_captcha_quota'] ?? null;

                return $quota === null || (int) $subscription->used_captcha_quota < (int) $quota;
            });
    }

    public function resolveSubscriptionFromApiKey(ApiKey $apiKey): UserSubscription
    {
        $subscription = $apiKey->subscription()->with(['package', 'apiKeys'])->first();

        if (! $subscription instanceof UserSubscription) {
            throw new ApiException('API key gói chưa được liên kết với gói captcha hợp lệ.', 422);
        }

        if ((int) $subscription->user_id !== (int) $apiKey->user_id) {
            throw new ApiException('API key gói không hợp lệ.', 422);
        }

        if ($subscription->status !== SubscriptionStatus::Active || $subscription->expires_at === null || $subscription->expires_at->isPast()) {
            throw new ApiException('Gói captcha của API key này đã hết hạn hoặc không còn hoạt động.', 422);
        }

        return $subscription;
    }

    public function availablePackages()
    {
        return Package::query()
            ->where('status', PackageStatus::Active)
            ->orderBy('price')
            ->get();
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

        return $subscription->fresh(['package', 'apiKeys']);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformSubscription(UserSubscription $subscription): array
    {
        $package = $subscription->package;

        if (! $package instanceof Package) {
            return [];
        }

        return [
            'id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'package_id' => $subscription->package_id,
            'order_id' => $subscription->order_id,
            'package_name' => $subscription->package_name,
            'package_price' => (string) $subscription->package_price,
            'package_limits' => CaptchaPlanCatalog::resolve(
                is_array($subscription->package_limits) ? $subscription->package_limits : null,
            ),
            'base_account_limit' => $subscription->base_account_limit,
            'extra_account_limit' => $subscription->extra_account_limit,
            'used_account' => $subscription->relationLoaded('apiKeys')
                ? $subscription->apiKeys->count()
                : $subscription->apiKeys()->count(),
            'used_captcha_quota' => (int) $subscription->used_captcha_quota,
            'remaining_captcha_quota' => $this->resolveRemainingCaptchaQuota($subscription),
            'auto_renew_enabled' => (bool) $subscription->auto_renew_enabled,
            'starts_at' => $subscription->starts_at?->toISOString(),
            'expires_at' => $subscription->expires_at?->toISOString(),
            'status' => $subscription->status->value,
            'package_api_keys' => ($subscription->relationLoaded('apiKeys') ? $subscription->apiKeys : $subscription->apiKeys()->get())
                ->map(fn (ApiKey $apiKey): array => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'api_key' => $apiKey->api_key,
                    'api_secret' => $apiKey->api_secret_encrypted,
                    'status' => $apiKey->status,
                    'expired_at' => $apiKey->expired_at?->toISOString(),
                    'created_at' => $apiKey->created_at?->toISOString(),
                ])
                ->values()
                ->all(),
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
                'package_limits' => CaptchaPlanCatalog::resolve(
                    is_array($package->package_limits) ? $package->package_limits : null,
                ),
                'status' => $package->status->value,
            ],
        ];
    }

    private function resolveRemainingCaptchaQuota(UserSubscription $subscription): ?int
    {
        $limits = CaptchaPlanCatalog::resolve(
            is_array($subscription->package_limits) ? $subscription->package_limits : null,
        );

        $quota = $limits['monthly_captcha_quota'] ?? null;

        if ($quota === null) {
            return null;
        }

        return max(0, (int) $quota - (int) $subscription->used_captcha_quota);
    }
}
