<?php

namespace App\Features\TrafficFine\Actions;

use App\Exceptions\ApiException;
use App\Features\TrafficFine\Services\MonitoringEntitlementService;
use App\Models\MonitoringSubscription;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use Illuminate\Support\Facades\DB;

class ConfigureVehicleMonitoringAction
{
    public function __construct(private readonly MonitoringEntitlementService $entitlements) {}

    /**
     * @param  array{enabled: bool, email_notifications: bool}  $payload
     */
    public function handle(UserVehicle $vehicle, array $payload): VehicleMonitoring
    {
        return DB::transaction(function () use ($vehicle, $payload): VehicleMonitoring {
            $user = User::query()->lockForUpdate()->findOrFail($vehicle->user_id);
            $monitoring = VehicleMonitoring::query()
                ->where('user_vehicle_id', $vehicle->id)
                ->lockForUpdate()
                ->first();

            if ($payload['enabled'] && ! $monitoring?->enabled) {
                $subscription = $this->entitlements->current($user, true);

                if (! $subscription instanceof MonitoringSubscription) {
                    throw new ApiException('Bạn cần đăng ký gói dịch vụ trước khi bật theo dõi xe.', 422);
                }

                $enabledCount = $user->vehicleMonitorings()->where('enabled', true)->count();

                if ($enabledCount >= $subscription->vehicle_limit) {
                    throw new ApiException("Gói hiện tại chỉ cho phép theo dõi {$subscription->vehicle_limit} xe.", 422);
                }
            }

            return $vehicle->monitoring()->updateOrCreate([], [
                'user_id' => $vehicle->user_id,
                'enabled' => $payload['enabled'],
                'email_notifications' => $payload['enabled'] && $payload['email_notifications'],
            ]);
        });
    }
}
