<?php

namespace App\Features\Client\ApiKey\Resources;

use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApiLog
 */
class ApiLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'ip' => $this->ip,
            'request_data' => $this->request_data,
            'response_data' => $this->response_data,
            'status_code' => $this->status_code,
            'response_time_ms' => $this->response_time_ms,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
