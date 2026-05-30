<?php

namespace App\Features\Client\Subscription\Services;

use App\Models\Account;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\AccountStatus;
use Illuminate\Support\Facades\DB;

class AccountProvisioningService
{
    public function __construct(
        private readonly SubscriptionQuotaService $subscriptionQuotaService,
    ) {}

    public function createAccount(User $user, UserSubscription $subscription): Account
    {
        return DB::transaction(function () use ($user, $subscription): Account {
            $lockedSubscription = UserSubscription::query()
                ->whereKey($subscription->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->subscriptionQuotaService->ensureCanCreateAccount($lockedSubscription);

            $account = Account::query()->create([
                'user_id' => $user->id,
                'subscription_id' => $lockedSubscription->id,
                'status' => AccountStatus::Active,
            ]);

            $lockedSubscription->increment('used_account');

            return $account;
        });
    }
}
