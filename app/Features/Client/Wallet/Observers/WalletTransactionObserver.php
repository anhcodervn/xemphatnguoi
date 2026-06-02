<?php

namespace App\Features\Client\Wallet\Observers;

use App\Features\Client\Wallet\Actions\DispatchWalletBalanceChangedMailAction;
use App\Models\WalletTransaction;

class WalletTransactionObserver
{
    public function __construct(
        private readonly DispatchWalletBalanceChangedMailAction $dispatchWalletBalanceChangedMailAction,
    ) {}

    public function created(WalletTransaction $walletTransaction): void
    {
        $this->dispatchWalletBalanceChangedMailAction->handle($walletTransaction);
    }
}
