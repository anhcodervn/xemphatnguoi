<?php

namespace App\Features\Admin\MonitoringPlan\Resources;

use App\Models\MonitoringSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringSubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user ? [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'full_name' => $this->user->full_name,
            ] : null),
            'monitoring_plan_id' => $this->monitoring_plan_id,
            'plan_name' => $this->plan_name,
            'vehicle_limit' => $this->vehicle_limit,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'duration_days' => $this->duration_days,
            'status' => $this->status,
            'is_active' => $this->status === MonitoringSubscription::STATUS_ACTIVE
                && $this->started_at->isPast()
                && $this->expires_at->isFuture(),
            'auto_renew' => $this->auto_renew,
            'renewal_count' => $this->renewal_count,
            'started_at' => $this->started_at->toISOString(),
            'expires_at' => $this->expires_at->toISOString(),
            'last_renewed_at' => $this->last_renewed_at?->toISOString(),
        ];
    }
}
