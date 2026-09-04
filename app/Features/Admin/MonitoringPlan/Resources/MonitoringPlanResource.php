<?php

namespace App\Features\Admin\MonitoringPlan\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_custom' => $this->is_custom,
            'vehicle_limit' => $this->vehicle_limit,
            'price' => $this->price,
            'unit_price' => $this->unit_price,
            'min_vehicle_count' => $this->min_vehicle_count,
            'duration_days' => $this->duration_days,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
