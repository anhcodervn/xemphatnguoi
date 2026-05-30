<?php

namespace App\Features\Admin\PackageOrder\Resources;

use App\Models\PackageOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PackageOrder
 */
class AdminPackageOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->order_code,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null,
            'package' => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
            ] : null,
            'price' => (float) $this->final_amount,
            'duration_days' => $this->package?->duration_days,
            'started_at' => $this->subscription?->starts_at?->toISOString(),
            'expired_at' => $this->subscription?->expires_at?->toISOString(),
            'payment_status' => $this->payment_status?->value ?? $this->payment_status,
            'status' => $this->status?->value ?? $this->status,
            'is_renewal' => $this->source_subscription_id !== null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
