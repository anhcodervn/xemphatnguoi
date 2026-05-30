<?php

use App\Features\Client\Wallet\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('client/wallet')
    ->name('client/wallet.')
    ->group(function (): void {
        Route::get('/', [WalletController::class, 'index'])->name('index');
    });
