<?php

namespace App\Features\TrafficFine\Services;

use App\Models\MonitoringSubscription;
use App\Models\User;

class MonitoringEntitlementService
{
    public function current(User $user, bool $lock = false): ?MonitoringSubscription
    {
        $query = $user->monitoringSubscriptions()->active()->latest('expires_at');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /** @return array<string, mixed>|null */
    public function summary(User $user): ?array
    {
        $subscription = $this->current($user);

        if (! $subscription instanceof MonitoringSubscription) {
            return null;
        }

        return $this->subscriptionSummary($user, $subscription, true);
    }

    /** @return array<string, mixed>|null */
    public function managementSummary(User $user): ?array
    {
        $subscription = $this->current($user) ?? $user->monitoringSubscriptions()
            ->where('status', MonitoringSubscription::STATUS_ACTIVE)
            ->where('auto_renew', true)
            ->latest('expires_at')
            ->first();

        if (! $subscription instanceof MonitoringSubscription) {
            return null;
        }

        return $this->subscriptionSummary($user, $subscription, $subscription->expires_at?->isFuture() === true);
    }

    /** @return array<string, mixed> */
    private function subscriptionSummary(User $user, MonitoringSubscription $subscription, bool $isActive): array
    {
        $enabledCount = $user->vehicleMonitorings()->where('enabled', true)->count();

        return [
            'id' => $subscription->id,
            'plan_name' => $subscription->plan_name,
            'vehicle_limit' => $subscription->vehicle_limit,
            'enabled_vehicle_count' => $enabledCount,
            'remaining_vehicle_count' => max(0, $subscription->vehicle_limit - $enabledCount),
            'total_price' => $subscription->total_price,
            'is_active' => $isActive,
            'auto_renew' => $subscription->auto_renew,
            'renewal_count' => $subscription->renewal_count,
            'started_at' => $subscription->started_at?->toISOString(),
            'expires_at' => $subscription->expires_at?->toISOString(),
            'last_renewed_at' => $subscription->last_renewed_at?->toISOString(),
        ];
    }
}
