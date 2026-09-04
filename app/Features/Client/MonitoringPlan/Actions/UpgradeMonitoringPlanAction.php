<?php

namespace App\Features\Client\MonitoringPlan\Actions;

use App\Exceptions\ApiException;
use App\Features\Client\Wallet\Services\WalletService;
use App\Features\TrafficFine\Services\MonitoringEntitlementService;
use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpgradeMonitoringPlanAction
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly MonitoringEntitlementService $entitlements,
    ) {}

    public function handle(User $user, int $planId, ?int $requestedVehicleCount): MonitoringSubscription
    {
        return DB::transaction(function () use ($user, $planId, $requestedVehicleCount): MonitoringSubscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $currentSubscription = $this->entitlements->current($lockedUser, true);

            if (! $currentSubscription instanceof MonitoringSubscription) {
                throw new ApiException('Bạn chưa có gói đang hoạt động để nâng cấp.', 422);
            }

            $plan = MonitoringPlan::query()->active()->lockForUpdate()->find($planId);

            if (! $plan instanceof MonitoringPlan) {
                throw new ApiException('Gói theo dõi không tồn tại hoặc đã ngừng bán.', 422);
            }

            $vehicleLimit = $plan->is_custom
                ? (int) $requestedVehicleCount
                : (int) $plan->vehicle_limit;

            if ($plan->is_custom && $vehicleLimit < $plan->min_vehicle_count) {
                throw new ApiException("Gói này yêu cầu tối thiểu {$plan->min_vehicle_count} xe.", 422);
            }

            if ($vehicleLimit <= $currentSubscription->vehicle_limit) {
                throw new ApiException("Chỉ có thể nâng cấp lên gói có hạn mức lớn hơn {$currentSubscription->vehicle_limit} xe.", 422);
            }

            $unitPrice = $plan->is_custom
                ? (float) $plan->unit_price
                : ((float) $plan->price / $vehicleLimit);
            $totalPrice = $plan->is_custom
                ? $unitPrice * $vehicleLimit
                : (float) $plan->price;
            $startedAt = now();

            $upgradedSubscription = $lockedUser->monitoringSubscriptions()->create([
                'monitoring_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'vehicle_limit' => $vehicleLimit,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'duration_days' => $plan->duration_days,
                'status' => MonitoringSubscription::STATUS_ACTIVE,
                'auto_renew' => $currentSubscription->auto_renew,
                'upgraded_from_subscription_id' => $currentSubscription->id,
                'started_at' => $startedAt,
                'expires_at' => $startedAt->copy()->addDays($plan->duration_days),
            ]);

            $this->walletService->debitWithTransaction(
                user: $lockedUser,
                amount: $totalPrice,
                referenceType: 'monitoring_subscription_upgrade',
                referenceId: $upgradedSubscription->id,
                description: "Nâng cấp gói theo dõi {$plan->name} ({$vehicleLimit} xe)",
            );

            $currentSubscription->update([
                'status' => MonitoringSubscription::STATUS_UPGRADED,
                'auto_renew' => false,
            ]);

            return $upgradedSubscription->load('plan');
        });
    }
}
