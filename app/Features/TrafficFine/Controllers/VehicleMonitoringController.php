<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Actions\ConfigureVehicleMonitoringAction;
use App\Features\TrafficFine\Requests\UpdateVehicleMonitoringRequest;
use App\Features\TrafficFine\Services\MonitoringEntitlementService;
use App\Features\TrafficFine\Services\VehicleMonitoringSettingsService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserVehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleMonitoringController extends Controller
{
    public function index(
        Request $request,
        MonitoringEntitlementService $entitlements,
        VehicleMonitoringSettingsService $monitoringSettings,
    ): JsonResponse {
        $this->authorize('viewAny', UserVehicle::class);

        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => [
                'interval_hours' => $monitoringSettings->intervalHours(),
                'subscription' => $entitlements->summary($user),
                'vehicles' => $user->vehicles()
                    ->with('monitoring:id,user_vehicle_id,enabled,email_notifications,last_checked_at,last_violation_count')
                    ->latest('id')
                    ->get(),
            ],
        ]);
    }

    public function update(
        UpdateVehicleMonitoringRequest $request,
        UserVehicle $vehicle,
        ConfigureVehicleMonitoringAction $configureMonitoring,
    ): JsonResponse {
        $this->authorize('update', $vehicle);
        $monitoring = $configureMonitoring->handle($vehicle, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật theo dõi biển số.',
            'data' => $monitoring,
        ]);
    }
}
