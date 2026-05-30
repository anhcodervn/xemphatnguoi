<?php

namespace App\Features\Admin\User\Actions;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class AdjustUserWalletAction
{
    /**
     * @param  array{type:string,amount:numeric-string|int|float,note?:string}  $payload
     * @return array<string, mixed>
     */
    public function handle(User $user, array $payload, User $actor): array
    {
        return DB::transaction(function () use ($user, $payload, $actor): array {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->where('type', Wallet::TYPE_MAIN)
                ->lockForUpdate()
                ->first();

            if (! $wallet instanceof Wallet) {
                $wallet = $user->wallets()->create([
                    'type' => Wallet::TYPE_MAIN,
                    'balance' => 0,
                    'hold_balance' => 0,
                    'total_recharge' => 0,
                    'total_spent' => 0,
                ]);
                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $amount = (float) $payload['amount'];
            $signedAmount = $payload['type'] === 'subtract' ? -1 * $amount : $amount;
            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $signedAmount;

            if ($balanceAfter < 0) {
                throw new ApiException('Số dư ví không đủ để thực hiện thao tác trừ.', 422);
            }

            $wallet->forceFill([
                'balance' => $balanceAfter,
            ])->save();

            $transaction = WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'type' => 'adjustment',
                'amount' => $signedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => User::class,
                'reference_id' => $actor->id,
                'description' => $payload['note'] ?? 'Admin wallet adjustment',
                'status' => 'success',
            ]);

            return [
                'wallet' => [
                    'id' => $wallet->id,
                    'balance' => (float) $wallet->fresh()->balance,
                    'hold_balance' => (float) $wallet->hold_balance,
                    'total_recharge' => (float) $wallet->total_recharge,
                    'total_spent' => (float) $wallet->total_spent,
                ],
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (float) $transaction->amount,
                    'balance_before' => (float) $transaction->balance_before,
                    'balance_after' => (float) $transaction->balance_after,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at?->toISOString(),
                ],
            ];
        });
    }
}
