<?php

namespace App\Features\Admin\Deposit\Resources;

use App\Models\RechargeOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RechargeOrder
 */
class AdminDepositResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->order_code,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null,
            'method' => [
                'code' => $this->method,
                'label' => $this->method_label,
            ],
            'amount' => (float) $this->amount,
            'bonus_amount' => (float) $this->bonus_amount,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'expired_at' => $this->expires_at?->toISOString(),
        ];
    }
}
