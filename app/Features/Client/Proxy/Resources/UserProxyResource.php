<?php

namespace App\Features\Client\Proxy\Resources;

use App\Models\UserProxy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class UserProxyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $sourceOrder = $this->whenLoaded('sourceOrder');
        $isRotating = ! $product instanceof MissingValue
            && is_array($product?->settings)
            && ($product->settings['proxy_type'] ?? null) === 'rotating';
        $isChanging = $this->status === UserProxy::STATUS_CHANGING;

        return [
            'id' => $this->id,
            'label' => $this->label,
            'status' => $this->status,
            'product' => ! $product || $product instanceof MissingValue ? null : [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
            ],
            'source_order_code' => ! $sourceOrder || $sourceOrder instanceof MissingValue ? null : $sourceOrder->order_code,
            'country_code' => $this->country_code,
            'protocol' => $this->protocol,
            'proxy_type' => $isRotating ? 'rotating' : 'static',
            'access_key' => $isRotating && ! $isChanging ? $this->provider_proxy_id : null,
            'connection' => ! $isChanging && ! $isRotating && filled($this->host) ? [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
            ] : null,
            'error_message' => $this->error_message,
            'expires_at' => $this->expires_at?->toISOString(),
            'last_changed_at' => $this->last_changed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
