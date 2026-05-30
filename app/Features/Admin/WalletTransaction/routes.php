<?php

use App\Features\Admin\WalletTransaction\Controllers\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin/wallet-transactions')
    ->name('admin.wallet-transactions.')
    ->controller(WalletTransactionController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
    });
