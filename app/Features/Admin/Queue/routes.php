<?php

use App\Features\Admin\Queue\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/queues')
    ->name('admin.queues.')
    ->controller(QueueController::class)
    ->group(function (): void {
        Route::get('overview', 'overview')->name('overview');
        Route::get('logs', 'logs')->name('logs');
        Route::get('failed-jobs', 'failedJobs')->name('failed-jobs');
        Route::post('failed-jobs/{id}/retry', 'retryFailedJob')->name('failed-jobs.retry');
        Route::delete('failed-jobs/{id}', 'deleteFailedJob')->name('failed-jobs.delete');
    });
