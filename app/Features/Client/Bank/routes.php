<?php

use App\Features\Client\Bank\Controllers\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active-subscription'])
    ->prefix('bank')
    ->name('client/bank.')
    ->group(function (): void {
        Route::get('/', [BankController::class, 'index'])->name('index');
        Route::get('accounts', [BankController::class, 'accounts'])->name('accounts');
        Route::get('accounts/{bankAccount}', [BankController::class, 'showAccount'])->name('accounts.show');
        Route::get('transaction/{bankAccount}', [BankController::class, 'transactions'])->name('transactions');

        Route::post('save-bank', [BankController::class, 'saveBank'])->name('save-bank');
        Route::put('accounts/{bankAccount}', [BankController::class, 'updateBank'])->name('accounts.update');
        Route::patch('accounts/{bankAccount}/status', [BankController::class, 'updateStatus'])->name('accounts.status');
        Route::delete('accounts/{bankAccount}', [BankController::class, 'destroyAccount'])->name('accounts.destroy');
    });
