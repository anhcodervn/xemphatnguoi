<?php

namespace App\Features\Admin\MonitoringPlan\Services;

use App\Models\VehicleMonitoring;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringVehicleService
{
    /** @param array<string, mixed> $filters */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? 'enabled';

        return VehicleMonitoring::query()
            ->with([
                'vehicle:id,user_id,name,plate,vehicle_type',
                'user:id,username,email,full_name',
                'user.monitoringSubscriptions' => fn (HasMany $query) => $query->active()->latest('expires_at'),
            ])
            ->whereHas('vehicle')
            ->whereHas('user')
            ->when($status !== 'all', fn (Builder $query) => $query->where('enabled', $status === 'enabled'))
            ->when(! empty($filters['vehicle_type']), fn (Builder $query) => $query
                ->whereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('vehicle_type', $filters['vehicle_type'])))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->whereHas('vehicle', fn (Builder $vehicle) => $vehicle
                        ->where('plate', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate((int) ($filters['per_page'] ?? 20))
            ->withQueryString();
    }
}
