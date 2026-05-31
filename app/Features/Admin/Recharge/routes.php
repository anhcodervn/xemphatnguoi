<?php

use App\Features\Admin\Recharge\Controllers\RechargeMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/recharge-methods')
    ->name('admin-api.recharge-methods.')
    ->controller(RechargeMethodController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{rechargeMethod}', 'show')->name('show');
        Route::patch('{rechargeMethod}', 'update')->name('update');
        Route::delete('{rechargeMethod}', 'destroy')->name('destroy');
    });
