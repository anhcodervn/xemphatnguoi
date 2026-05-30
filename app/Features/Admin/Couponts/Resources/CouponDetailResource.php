<?php

namespace App\Features\Admin\Couponts\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...(new CouponResource($this->resource))->toArray($request),
            'recent_logs' => CouponLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
