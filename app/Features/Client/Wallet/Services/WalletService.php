<?php

namespace App\Features\Client\Wallet\Services;

use App\Events\WalletBalanceChanged;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

class WalletService
{
    public function createWallet(User $user, string $type = Wallet::TYPE_MAIN): Wallet
    {
        return $user->wallets()->firstOrCreate([
            'type' => $type,
        ], [
            'balance' => 0,
            'hold_balance' => 0,
            'total_recharge' => 0,
            'total_spent' => 0,
        ]);
    }

    /**
     * @return array{id:int,user_id:int,type:string,balance:string,hold_balance:string,total_recharge:string,total_spent:string,created_at:?string,updated_at:?string}
     */
    public function getWalletInfo(User $user, string $type = Wallet::TYPE_MAIN): array
    {
        $wallet = $this->getWallet($user, $type);

        return [
            'id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'type' => $wallet->type,
            'balance' => (string) $wallet->balance,
            'hold_balance' => (string) $wallet->hold_balance,
            'total_recharge' => (string) $wallet->total_recharge,
            'total_spent' => (string) $wallet->total_spent,
            'created_at' => $wallet->created_at?->toISOString(),
            'updated_at' => $wallet->updated_at?->toISOString(),
        ];
    }

    public function getWallet(User $user, string $type = Wallet::TYPE_MAIN): Wallet
    {
        $wallet = $user->relationLoaded('wallet') && $type === Wallet::TYPE_MAIN
            ? $user->wallet
            : $user->wallets()->where('type', $type)->first();

        if (! $wallet instanceof Wallet) {
            $wallet = $this->createWallet($user, $type);
        }

        return $wallet;
    }

    public function debit(
        User $user,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $description,
        string $type = Wallet::TYPE_MAIN,
    ): Wallet {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->lockForUpdate()
            ->first();

        if (! $wallet instanceof Wallet) {
            $wallet = $this->createWallet($user, $type);
            $wallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        if ((float) $wallet->balance < $amount) {
            throw new ApiException('Số dư ví chính không đủ để thanh toán đơn hàng.', 422);
        }

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter = $balanceBefore - $amount;

        $wallet->forceFill([
            'balance' => $balanceAfter,
            'total_spent' => (float) $wallet->total_spent + $amount,
        ])->save();

        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'status' => 'success',
        ]);

        $wallet = $wallet->refresh();
        $this->broadcastBalanceChanged($user, $wallet, $transaction, -$amount);

        return $wallet;
    }

    public function credit(
        User $user,
        float $amount,
        string $referenceType,
        int $referenceId,
        string $description,
        string $type = Wallet::TYPE_MAIN,
    ): Wallet {
        $wallet = Wallet::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->lockForUpdate()
            ->firstOrFail();

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter = $balanceBefore + $amount;

        $wallet->forceFill([
            'balance' => $balanceAfter,
            'total_spent' => max(0, (float) $wallet->total_spent - $amount),
        ])->save();

        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'status' => 'success',
        ]);

        $wallet = $wallet->refresh();
        $this->broadcastBalanceChanged($user, $wallet, $transaction, $amount);

        return $wallet;
    }

    private function broadcastBalanceChanged(User $user, Wallet $wallet, WalletTransaction $transaction, float $signedAmount): void
    {
        WalletBalanceChanged::dispatch(
            userId: $user->id,
            walletType: $wallet->type,
            balance: (string) $wallet->balance,
            holdBalance: (string) $wallet->hold_balance,
            totalRecharge: (string) $wallet->total_recharge,
            totalSpent: (string) $wallet->total_spent,
            changeType: $transaction->type,
            amount: number_format($signedAmount, 2, '.', ''),
            transactionId: $transaction->id,
            description: (string) $transaction->description,
            changedAt: $transaction->created_at?->toISOString() ?? now()->toISOString(),
        );
    }
}
