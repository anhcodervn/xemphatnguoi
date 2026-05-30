<?php

namespace App\Features\Client\Profile\Resources;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WalletTransaction
 */
class WalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => sprintf('WTX-%06d', $this->id),
            'time' => $this->created_at?->toISOString(),
            'content' => $this->description,
            'amount' => (string) $this->amount,
            'balance_after' => (string) $this->balance_after,
            'status' => $this->status,
            'type' => $this->normalizedType(),
        ];
    }

    private function normalizedType(): string
    {
        return match ($this->type) {
            'credit' => str_contains(strtolower((string) $this->description), 'bonus') ? 'bonus' : 'recharge',
            'debit' => 'deduct',
            'refund' => 'refund',
            'adjustment' => ((float) $this->amount) >= 0 ? 'bonus' : 'deduct',
            default => 'deduct',
        };
    }
}
