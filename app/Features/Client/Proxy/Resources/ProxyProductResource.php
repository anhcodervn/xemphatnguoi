<?php

namespace App\Features\Client\Proxy\Resources;

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
            'selling_price' => $this->selling_price,
            'max_quantity' => $this->max_quantity,
        ];
    }
}
