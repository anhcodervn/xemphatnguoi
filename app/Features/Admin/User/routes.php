<?php

use App\Features\Admin\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin/users')
    ->name('admin.users.')
    ->controller(UserController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('{user}', 'show')->name('show');
        Route::patch('{user}/status', 'updateStatus')->name('status.update');
        Route::post('{user}/wallet-adjust', 'walletAdjust')->name('wallet.adjust');
        Route::get('{user}/wallet-transactions', 'walletTransactions')->name('wallet-transactions.index');
        Route::get('{user}/package-orders', 'packageOrders')->name('package-orders.index');
        Route::get('{user}/webhooks', 'webhooks')->name('webhooks.index');
        Route::get('{user}/logs', 'logs')->name('logs.index');
    });
