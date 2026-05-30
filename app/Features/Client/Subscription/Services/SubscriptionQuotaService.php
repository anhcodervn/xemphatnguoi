<?php

namespace App\Features\Client\Subscription\Services;

use App\Exceptions\ApiException;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;

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
}
