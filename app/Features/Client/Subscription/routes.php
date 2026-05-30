<?php

use App\Features\Client\Subscription\Controllers\AccountController;
use App\Features\Client\Subscription\Controllers\ExtraAccountOrderController;
use App\Features\Client\Subscription\Controllers\PackageOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('client/subscriptions')
    ->name('api.client.subscriptions.')
    ->group(function (): void {
        Route::get('/', [PackageOrderController::class, 'index'])->name('index');
        Route::post('package-orders', [PackageOrderController::class, 'store'])->name('package-orders.store');
        Route::post('package-orders/{packageOrder}/pay', [PackageOrderController::class, 'pay'])->name('package-orders.pay');

        Route::post('extra-account-orders', [ExtraAccountOrderController::class, 'store'])->name('extra-account-orders.store');
        Route::post('extra-account-orders/{extraAccountOrder}/pay', [ExtraAccountOrderController::class, 'pay'])->name('extra-account-orders.pay');

        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
    });
