<?php

namespace App\Features\Admin\Proxy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProxyProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'proxy_category_id' => $this->proxy_category_id,
            'code' => $this->code,
            'name' => $this->name,
            'country_code' => $this->country_code,
            'protocol' => $this->protocol,
            'supported_protocols' => $this->supportedProtocols(),
            'description' => $this->description,
            'provider_product_code' => $this->provider_product_code,
            'default_provider_id' => $this->default_provider_id,
            'sort_order' => $this->sort_order,
            'base_price' => $this->base_price,
            'selling_price' => $this->selling_price,
            'max_quantity' => $this->max_quantity,
            'is_active' => $this->is_active,
            'settings' => $this->settings ?? [],
            'provider' => ProxyProviderResource::make($this->whenLoaded('provider')),
            'category' => ProxyCategoryResource::make($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
