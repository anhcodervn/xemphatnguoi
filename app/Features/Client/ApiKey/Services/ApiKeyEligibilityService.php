<?php

namespace App\Features\Client\ApiKey\Services;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class ApiKeyEligibilityService
{
    public function canCreate(User $user): bool
    {
        return $this->currentSubscription($user) instanceof UserSubscription;
    }

    public function ensureCanCreate(User $user): void
    {
        if (! $this->canCreate($user)) {
            throw new ApiException('Bạn cần có gói đang hoạt động để tạo API key.', 403);
        }
    }

    public function currentSubscription(User $user): ?UserSubscription
    {
        if ($user->relationLoaded('userSubscriptions')) {
            /** @var Collection<int, UserSubscription> $subscriptions */
            $subscriptions = $user->getRelation('userSubscriptions');

            return $subscriptions
                ->filter(fn (UserSubscription $subscription): bool => $this->isEligible($subscription))
                ->sortByDesc(fn (UserSubscription $subscription): int => $subscription->expires_at?->getTimestamp() ?? 0)
                ->first();
        }

        /** @var UserSubscription|null $subscription */
        $subscription = $user->userSubscriptions()
            ->with('package')
            ->where('status', SubscriptionStatus::Active)
            ->where(function ($query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('expires_at')
            ->first();

        return $subscription;
    }

    /**
     * @return array{
     *     can_create: bool,
     *     message: string,
     *     package: array{id:int|null,name:string,expires_at:string|null,status:string,request_limit:int|null,request_per_minute:int|null}|null
     * }
     */
    public function summary(User $user): array
    {
        $subscription = $this->currentSubscription($user);

        if (! $subscription instanceof UserSubscription) {
            return [
                'can_create' => false,
                'message' => 'Cần có gói đang hoạt động để tạo API key.',
                'package' => null,
            ];
        }

        $subscription->loadMissing('package');

        return [
            'can_create' => true,
            'message' => 'Gói hiện tại hợp lệ để tạo và sử dụng API key.',
            'package' => [
                'id' => $subscription->package_id,
                'name' => $subscription->package_name,
                'expires_at' => $subscription->expires_at?->toISOString(),
                'status' => $subscription->status->value,
                'request_limit' => $subscription->package?->request_limit,
                'request_per_minute' => $subscription->package?->request_per_minute,
            ],
        ];
    }

    private function isEligible(UserSubscription $subscription): bool
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            return false;
        }

        if (! $subscription->expires_at instanceof CarbonInterface) {
            return true;
        }

        return $subscription->expires_at->isFuture();
    }
}
