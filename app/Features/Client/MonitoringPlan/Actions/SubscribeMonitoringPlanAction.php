<?php

namespace App\Features\Client\MonitoringPlan\Actions;

use App\Exceptions\ApiException;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubscribeMonitoringPlanAction
{
    public function __construct(private readonly WalletService $walletService) {}

    public function handle(User $user, int $planId, ?int $requestedVehicleCount): MonitoringSubscription
    {
        return DB::transaction(function () use ($user, $planId, $requestedVehicleCount): MonitoringSubscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $plan = MonitoringPlan::query()->active()->lockForUpdate()->find($planId);

            if (! $plan instanceof MonitoringPlan) {
                throw new ApiException('Gói theo dõi không tồn tại hoặc đã ngừng bán.', 422);
            }

            if ($lockedUser->monitoringSubscriptions()->active()->exists()) {
                throw new ApiException('Bạn đang có một gói theo dõi còn hiệu lực.', 422);
            }

            $vehicleLimit = $plan->is_custom
                ? (int) $requestedVehicleCount
                : (int) $plan->vehicle_limit;

            if ($plan->is_custom && $vehicleLimit < $plan->min_vehicle_count) {
                throw new ApiException("Gói này yêu cầu tối thiểu {$plan->min_vehicle_count} xe.", 422);
            }

            $unitPrice = $plan->is_custom
                ? (float) $plan->unit_price
                : ((float) $plan->price / $vehicleLimit);
            $totalPrice = $plan->is_custom
                ? $unitPrice * $vehicleLimit
                : (float) $plan->price;
            $enabledVehicleCount = $lockedUser->vehicleMonitorings()->where('enabled', true)->count();

            if ($enabledVehicleCount > $vehicleLimit) {
                throw new ApiException("Bạn đang bật theo dõi {$enabledVehicleCount} xe. Hãy giảm xuống còn {$vehicleLimit} xe trước khi đăng ký gói này.", 422);
            }

            $startedAt = now();

            $subscription = $lockedUser->monitoringSubscriptions()->create([
                'monitoring_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'vehicle_limit' => $vehicleLimit,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'duration_days' => $plan->duration_days,
                'started_at' => $startedAt,
                'expires_at' => $startedAt->copy()->addDays($plan->duration_days),
            ]);

            $this->walletService->debitWithTransaction(
                user: $lockedUser,
                amount: $totalPrice,
                referenceType: 'monitoring_subscription',
                referenceId: $subscription->id,
                description: "Đăng ký gói theo dõi {$plan->name} ({$vehicleLimit} xe)",
            );

            return $subscription->load('plan');
        });
    }
}
