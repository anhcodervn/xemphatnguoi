<?php

namespace App\Features\Client\Proxy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class DashboardProxyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');

        return [
            'id' => $this->id,
            'label' => $this->label,
            'endpoint' => filled($this->host) ? "{$this->host}:{$this->port}" : ($this->label ?: "Proxy #{$this->id}"),
            'status' => $this->status,
            'product' => ! $product || $product instanceof MissingValue ? null : [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
            ],
            'country_code' => $this->country_code,
            'protocol' => $this->protocol,
            'expires_at' => $this->expires_at?->toISOString(),
        ];
    }
}
