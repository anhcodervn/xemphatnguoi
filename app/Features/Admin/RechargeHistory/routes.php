<?php

use App\Features\Admin\RechargeHistory\Controllers\RechargeHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/recharge-history')
    ->name('admin.recharge-history.')
    ->controller(RechargeHistoryController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
    });
