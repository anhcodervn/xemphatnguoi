<?php

use App\Features\Admin\CronJob\Controllers\CronJobController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/cron-jobs')
    ->name('admin.cron-jobs.')
    ->controller(CronJobController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/logs', 'globalLogs')->name('logs.global');
        Route::get('{cronJob}', 'show')->name('show');
        Route::patch('{cronJob}/status', 'updateStatus')->name('status.update');
        Route::delete('{cronJob}', 'destroy')->name('destroy');
        Route::get('{cronJob}/logs', 'logs')->name('logs.index');
    });
