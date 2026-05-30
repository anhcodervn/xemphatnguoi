<?php

namespace App\Features\Admin\Deposit\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDepositDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $deposit = $this->resource['deposit'];
        $walletTransaction = $this->resource['wallet_transaction'];

        return [
            'deposit' => AdminDepositResource::make($deposit)->resolve(),
            'bank_transfer_info' => [
                'bank_name' => $deposit->bank_name,
                'account_number' => $deposit->account_number,
                'account_name' => $deposit->account_name,
                'transfer_content' => $deposit->transfer_content,
            ],
            'wallet_transaction' => $walletTransaction ? [
                'id' => $walletTransaction->id,
                'amount' => (float) $walletTransaction->amount,
                'balance_before' => (float) $walletTransaction->balance_before,
                'balance_after' => (float) $walletTransaction->balance_after,
                'status' => $walletTransaction->status,
                'created_at' => $walletTransaction->created_at?->toISOString(),
            ] : null,
            'payment_transactions' => $this->resource['payment_transactions']->map(fn ($transaction): array => [
                'id' => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'amount' => (float) $transaction->amount,
                'content' => $transaction->content,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at?->toISOString(),
            ])->all(),
            'logs' => $this->resource['logs'],
        ];
    }
}
