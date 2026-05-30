<?php

namespace App\Features\Admin\WalletTransaction\Resources;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WalletTransaction
 */
class AdminWalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => sprintf('WTX-%06d', $this->id),
            'user' => $this->wallet?->user ? [
                'id' => $this->wallet->user->id,
                'name' => $this->wallet->user->name,
                'email' => $this->wallet->user->email,
                'phone' => $this->wallet->user->phone,
            ] : null,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'content' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
