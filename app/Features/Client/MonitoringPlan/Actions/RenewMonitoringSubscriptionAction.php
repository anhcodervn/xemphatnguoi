<?php

namespace App\Features\Client\MonitoringPlan\Actions;

use App\Exceptions\ApiException;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RenewMonitoringSubscriptionAction
{
    public const RESULT_RENEWED = 'renewed';

    public const RESULT_SKIPPED = 'skipped';

    public const RESULT_INSUFFICIENT_BALANCE = 'insufficient_balance';

    public function __construct(private readonly WalletService $walletService) {}

    public function handle(int $subscriptionId): string
    {
        try {
            return DB::transaction(function () use ($subscriptionId): string {
                $subscriptionUserId = MonitoringSubscription::query()->whereKey($subscriptionId)->value('user_id');

                if ($subscriptionUserId === null) {
                    return self::RESULT_SKIPPED;
                }

                $user = User::query()->lockForUpdate()->findOrFail($subscriptionUserId);
                $subscription = MonitoringSubscription::query()->lockForUpdate()->find($subscriptionId);

                if (! $subscription instanceof MonitoringSubscription
                    || ! $subscription->auto_renew
                    || $subscription->status !== MonitoringSubscription::STATUS_ACTIVE
                    || $subscription->expires_at?->isFuture()) {
                    return self::RESULT_SKIPPED;
                }

                $plan = MonitoringPlan::query()->lockForUpdate()->find($subscription->monitoring_plan_id);

                if (! $plan instanceof MonitoringPlan || ! $plan->is_active) {
                    return self::RESULT_SKIPPED;
                }

                if ($user->monitoringSubscriptions()->active()->exists()) {
                    return self::RESULT_SKIPPED;
                }

                $startedAt = now();
                $renewedSubscription = $user->monitoringSubscriptions()->create([
                    'monitoring_plan_id' => $subscription->monitoring_plan_id,
                    'plan_name' => $subscription->plan_name,
                    'vehicle_limit' => $subscription->vehicle_limit,
                    'unit_price' => $subscription->unit_price,
                    'total_price' => $subscription->total_price,
                    'duration_days' => $subscription->duration_days,
                    'status' => MonitoringSubscription::STATUS_ACTIVE,
                    'auto_renew' => true,
                    'renewed_from_subscription_id' => $subscription->id,
                    'renewal_count' => $subscription->renewal_count + 1,
                    'started_at' => $startedAt,
                    'expires_at' => $startedAt->copy()->addDays($subscription->duration_days),
                    'last_renewed_at' => $startedAt,
                ]);

                $this->walletService->debitWithTransaction(
                    user: $user,
                    amount: (float) $subscription->total_price,
                    referenceType: 'monitoring_subscription_renewal',
                    referenceId: $renewedSubscription->id,
                    description: "Tự gia hạn gói theo dõi {$subscription->plan_name} ({$subscription->vehicle_limit} xe)",
                );

                $subscription->update([
                    'status' => MonitoringSubscription::STATUS_RENEWED,
                    'auto_renew' => false,
                    'last_renewed_at' => $startedAt,
                ]);

                return self::RESULT_RENEWED;
            });
        } catch (ApiException $exception) {
            if ($exception->getCode() === 422) {
                return self::RESULT_INSUFFICIENT_BALANCE;
            }

            throw $exception;
        }
    }
}
