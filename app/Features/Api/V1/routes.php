<?php

use App\Features\Api\V1\Controllers\CronJobController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['api-key.auth', 'api-key.log'])
    ->name('api.v1.')
    ->group(function (): void {
        Route::controller(CronJobController::class)
            ->prefix('cron-jobs')
            ->name('cron-jobs.')
            ->group(function (): void {
                Route::get('/', 'index')->middleware('api-key.permission:cron-jobs.read')->name('index');
                Route::post('/', 'store')->middleware('api-key.permission:cron-jobs.write')->name('store');
                Route::get('/{cronJob}', 'show')->middleware('api-key.permission:cron-jobs.read')->name('show');
                Route::patch('/{cronJob}', 'update')->middleware('api-key.permission:cron-jobs.write')->name('update');
                Route::delete('/{cronJob}', 'destroy')->middleware('api-key.permission:cron-jobs.write')->name('destroy');
                Route::post('/{cronJob}/pause', 'pause')->middleware('api-key.permission:cron-jobs.write')->name('pause');
                Route::post('/{cronJob}/resume', 'resume')->middleware('api-key.permission:cron-jobs.write')->name('resume');
                Route::get('/{cronJob}/logs', 'logs')->middleware('api-key.permission:cron-logs.read')->name('logs');
            });
    });
