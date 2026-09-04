<?php

namespace App\Features\TrafficFine\Actions;

use App\Features\TrafficFine\Services\MonitoringEntitlementService;
use App\Features\TrafficFine\Services\TrafficFineLookupService;
use App\Mail\VehicleMonitoringChangedMail;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckVehicleMonitoringAction
{
    public function __construct(
        private readonly TrafficFineLookupService $lookupService,
        private readonly MonitoringEntitlementService $entitlements,
    ) {}

    public function handle(int $monitoringId): void
    {
        $monitoring = VehicleMonitoring::query()
            ->with(['user:id,email', 'vehicle:id,user_id,name,plate,vehicle_type'])
            ->find($monitoringId);

        if (
            ! $monitoring instanceof VehicleMonitoring
            || ! $monitoring->enabled
            || ! $monitoring->user instanceof User
            || ! $monitoring->vehicle instanceof UserVehicle
        ) {
            return;
        }

        if ($this->entitlements->current($monitoring->user) === null) {
            return;
        }

        $result = $this->lookupService->lookup(
            plate: $monitoring->vehicle->plate,
            vehicleType: $monitoring->vehicle->vehicle_type,
            forceRefresh: true,
        );

        $mailData = DB::transaction(function () use ($monitoringId, $result): ?array {
            $lockedMonitoring = VehicleMonitoring::query()
                ->with(['user:id,email', 'vehicle:id,user_id,name,plate,vehicle_type'])
                ->lockForUpdate()
                ->find($monitoringId);

            if (
                ! $lockedMonitoring instanceof VehicleMonitoring
                || ! $lockedMonitoring->enabled
                || ! $lockedMonitoring->user instanceof User
                || ! $lockedMonitoring->vehicle instanceof UserVehicle
                || $this->entitlements->current($lockedMonitoring->user, true) === null
            ) {
                return null;
            }

            $previousViolationCount = $lockedMonitoring->last_violation_count;
            $currentViolationCount = $result->data->violationCount;
            $shouldNotify = $lockedMonitoring->email_notifications
                && filled($lockedMonitoring->user->email)
                && ($previousViolationCount === null
                    ? $currentViolationCount > 0
                    : $previousViolationCount !== $currentViolationCount);

            $lockedMonitoring->update([
                'last_checked_at' => $result->data->checkedAt,
                'last_violation_count' => $currentViolationCount,
            ]);

            if (! $shouldNotify) {
                return null;
            }

            return [
                'email' => $lockedMonitoring->user->email,
                'vehicle_name' => $lockedMonitoring->vehicle->name,
                'plate' => $lockedMonitoring->vehicle->plate,
                'vehicle_type' => $lockedMonitoring->vehicle->vehicle_type,
                'previous_violation_count' => $previousViolationCount,
                'current_violation_count' => $currentViolationCount,
            ];
        });

        if ($mailData === null) {
            return;
        }

        Mail::to($mailData['email'])->queue(new VehicleMonitoringChangedMail(
            vehicleName: $mailData['vehicle_name'],
            plate: $mailData['plate'],
            vehicleType: $mailData['vehicle_type'],
            previousViolationCount: $mailData['previous_violation_count'],
            currentViolationCount: $mailData['current_violation_count'],
        ));
    }
}
