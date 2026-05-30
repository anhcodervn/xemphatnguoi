<?php

use App\Features\Client\Package\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('package')
    ->name('api.client.package.')
    ->controller(PackageController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('quote', 'quote')->name('quote');
        Route::post('orders', 'store')->name('orders.store');
        Route::post('orders/{packageOrder}/pay', 'pay')->name('orders.pay');
    });
