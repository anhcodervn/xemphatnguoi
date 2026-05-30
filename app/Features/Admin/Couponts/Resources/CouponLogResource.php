<?php

namespace App\Features\Admin\Couponts\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'status' => $this->status,
            'coupon' => $this->whenLoaded('coupon', fn (): array => [
                'id' => $this->coupon?->id,
                'code' => $this->coupon?->code,
                'name' => $this->coupon?->name,
            ]),
            'user' => $this->whenLoaded('user', fn (): ?array => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'admin' => $this->whenLoaded('admin', fn (): ?array => $this->admin ? [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
            ] : null),
            'package_order' => $this->whenLoaded('packageOrder', fn (): ?array => $this->packageOrder ? [
                'id' => $this->packageOrder->id,
                'order_code' => $this->packageOrder->order_code,
            ] : null),
            'order_amount' => $this->order_amount,
            'discount_amount' => $this->discount_amount,
            'note' => $this->note,
            'payload' => $this->payload,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
