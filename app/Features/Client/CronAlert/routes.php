<?php

use App\Features\Client\CronAlert\Controllers\CronAlertChannelController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('client/cron-alert-channels')
    ->name('api.client.cron-alert-channels.')
    ->controller(CronAlertChannelController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{cronAlertChannel}', 'show')->name('show');
        Route::match(['put', 'patch'], '{cronAlertChannel}', 'update')->name('update');
        Route::delete('{cronAlertChannel}', 'destroy')->name('destroy');
        Route::post('{cronAlertChannel}/test', 'test')->name('test');
    });
