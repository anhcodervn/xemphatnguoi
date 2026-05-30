<?php

namespace App\Features\Api\V1\Resources;

use App\Models\RechargeClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RechargeClient
 */
class RechargeClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'client_order_code' => $this->client_order_code,
            'bank_id' => $this->bank_account_id,
            'method' => $this->method,
            'method_label' => $this->method_label,
            'amount' => (float) $this->amount,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'transfer_content' => $this->transfer_content,
            'status' => $this->status,
            'requested_at' => $this->requested_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'matched_bank_transaction_id' => $this->matched_bank_transaction_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'metadata' => $this->metadata,
        ];
    }
}
