<?php

use App\Features\Recharge\Controllers\RechargeCallbackController;
use Illuminate\Support\Facades\Route;

Route::prefix('recharge/callbacks')
    ->name('api.recharge.callbacks.')
    ->controller(RechargeCallbackController::class)
    ->group(function (): void {
        Route::post('/apibankvn', 'apibankvn')->name('apibankvn');
    });
