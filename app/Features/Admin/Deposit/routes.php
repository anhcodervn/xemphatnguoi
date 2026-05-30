<?php

use App\Features\Admin\Deposit\Controllers\DepositController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin/deposits')
    ->name('admin.deposits.')
    ->controller(DepositController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('{deposit}', 'show')->name('show');
        Route::post('{deposit}/approve', 'approve')->name('approve');
        Route::post('{deposit}/reject', 'reject')->name('reject');
    });
