<?php

namespace App\Features\Client\MonitoringPlan\Resources;

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
            'description' => $this->description,
            'is_custom' => $this->is_custom,
            'vehicle_limit' => $this->vehicle_limit,
            'price' => $this->price,
            'unit_price' => $this->unit_price,
            'min_vehicle_count' => $this->min_vehicle_count,
            'minimum_price' => $this->is_custom
                ? number_format((float) $this->unit_price * $this->min_vehicle_count, 2, '.', '')
                : $this->price,
            'duration_days' => $this->duration_days,
        ];
    }
}
