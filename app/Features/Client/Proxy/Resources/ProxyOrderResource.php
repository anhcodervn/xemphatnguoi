<?php

namespace App\Features\Client\Proxy\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class ProxyOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');

        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'type' => $this->type,
            'status' => $this->status,
            'product' => [
                'id' => ! $product || $product instanceof MissingValue ? $this->proxy_product_id : $product->id,
                'code' => ! $product || $product instanceof MissingValue ? $this->product_code : $product->code,
                'name' => ! $product || $product instanceof MissingValue ? $this->product_name : $product->name,
            ],
            'target_proxy_id' => $this->target_user_proxy_id,
            'quantity' => $this->quantity,
            'duration_days' => $this->duration_days,
            'country_code' => $this->country_code,
            'protocol' => $this->protocol,
            'unit_price' => $this->unit_price,
            'total_amount' => $this->total_amount,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'ordered_at' => $this->ordered_at?->toISOString(),
            'fulfilled_at' => $this->fulfilled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
