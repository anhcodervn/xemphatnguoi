<?php

namespace App\Features\Client\Recharge\Resources;

use App\Models\RechargeOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RechargeOrder
 */
class RechargeOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'method' => $this->method,
            'method_label' => $this->method_label,
            'amount' => (string) $this->amount,
            'bonus_amount' => (string) $this->bonus_amount,
            'total_amount' => (string) $this->total_amount,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'transfer_content' => $this->transfer_content,
            'status' => $this->status,
            'requested_at' => $this->requested_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'metadata' => $this->metadata,
        ];
    }
}
