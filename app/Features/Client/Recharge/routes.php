<?php

use App\Features\Client\Recharge\Controllers\RechargeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('recharge')
    ->name('client/recharge.')
    ->group(function (): void {
        Route::get('/', [RechargeController::class, 'index'])->name('index');
        Route::post('orders', [RechargeController::class, 'store'])->name('orders.store');
        Route::get('orders/{rechargeOrder}', [RechargeController::class, 'show'])->name('orders.show');
    });
