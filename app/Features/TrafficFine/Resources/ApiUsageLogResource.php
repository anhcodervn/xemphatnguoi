<?php

namespace App\Features\TrafficFine\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiUsageLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $query = data_get($this->request_data, 'query', []);

        return [
            'id' => $this->id,
            'api_key_name' => $this->whenLoaded('apiKey', fn (): ?string => $this->apiKey?->name),
            'plate' => is_array($query) ? ($query['plate'] ?? null) : null,
            'vehicle_type' => is_array($query) ? ($query['vehicle_type'] ?? null) : null,
            'method' => $this->method,
            'status_code' => $this->status_code,
            'response_time_ms' => $this->response_time_ms,
            'unit_price' => $this->unit_price,
            'charged_amount' => $this->charged_amount,
            'billing_status' => $this->billing_status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
