<?php

namespace App\Features\Admin\MonitoringPlan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringVehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subscription = $this->user?->monitoringSubscriptions->first();

        return [
            'id' => $this->id,
            'vehicle' => [
                'id' => $this->vehicle->id,
                'name' => $this->vehicle->name,
                'plate' => $this->vehicle->plate,
                'vehicle_type' => $this->vehicle->vehicle_type,
            ],
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
            ],
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan_name,
                'expires_at' => $subscription->expires_at->toISOString(),
            ] : null,
            'enabled' => $this->enabled,
            'email_notifications' => $this->email_notifications,
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'last_violation_count' => $this->last_violation_count,
        ];
    }
}
