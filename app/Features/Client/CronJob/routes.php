<?php

use App\Features\Client\CronJob\Controllers\CronJobController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('client/cron-jobs')
    ->name('api.client.cron-jobs.')
    ->controller(CronJobController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/logs', 'globalLogs')->name('logs.global');
        Route::post('/', 'store')->name('store');
        Route::get('{cronJob}', 'show')->name('show');
        Route::match(['put', 'patch'], '{cronJob}', 'update')->name('update');
        Route::delete('{cronJob}', 'destroy')->name('destroy');
        Route::post('{cronJob}/pause', 'pause')->name('pause');
        Route::post('{cronJob}/resume', 'resume')->name('resume');
        Route::post('{cronJob}/run-now', 'runNow')->name('run-now');
        Route::get('{cronJob}/logs', 'logs')->name('logs.index');
        Route::get('{cronJob}/stats', 'stats')->name('stats');
    });
