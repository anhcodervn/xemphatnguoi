<?php

use App\Features\Admin\RechargeConfig\Controllers\RechargeConfigController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/recharge-config')
    ->name('admin.recharge-config.')
    ->controller(RechargeConfigController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/verify-credentials', 'verifyCredentials')->name('verify-credentials');
        Route::patch('/{configRecharge}', 'update')->name('update');
        Route::patch('/{configRecharge}/toggle', 'toggle')->name('toggle');
        Route::delete('/{configRecharge}', 'destroy')->name('destroy');
    });
