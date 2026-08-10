<?php

namespace App\Features\Admin\Couponts\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usagePercent = null;

        if ($this->max_usage !== null && $this->max_usage > 0) {
            $usagePercent = round(($this->used_count / $this->max_usage) * 100, 2);
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'min_order_amount' => $this->min_order_amount,
            'max_discount_amount' => $this->max_discount_amount,
            'max_usage' => $this->max_usage,
            'max_usage_per_user' => $this->max_usage_per_user,
            'used_count' => $this->used_count,
            'starts_at' => optional($this->starts_at)?->toDateTimeString(),
            'expired_at' => optional($this->expired_at)?->toDateTimeString(),
            'first_order_only' => (bool) $this->first_order_only,
            'is_active' => (bool) $this->is_active,
            'is_available' => $this->isAvailable(),
            'requirements' => $this->requirements ?? [],
            'logs_count' => $this->whenCounted('logs'),
            'usage_percent' => $usagePercent,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
