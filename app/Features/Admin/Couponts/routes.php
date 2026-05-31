<?php

use App\Features\Admin\Couponts\Controllers\CouponController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/coupons')
    ->name('admin-api.coupons.')
    ->controller(CouponController::class)
    ->group(function (): void {
        Route::get('logs', 'logs')->name('logs');
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{coupon}', 'show')->name('show');
        Route::patch('{coupon}', 'update')->name('update');
        Route::delete('{coupon}', 'destroy')->name('destroy');
    });
