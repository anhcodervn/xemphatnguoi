<?php

use App\Features\Cron\Controllers\InternalCronController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal/cron')
    ->name('api.internal.cron.')
    ->controller(InternalCronController::class)
    ->group(function (): void {
        Route::post('/dispatch-due', 'dispatchDue')->name('dispatch-due');
        Route::post('/prune-logs', 'pruneLogs')->name('prune-logs');
        Route::post('/recalculate-next-run', 'recalculateNextRun')->name('recalculate-next-run');
        Route::post('/reset-usage-quota', 'resetUsageQuota')->name('reset-usage-quota');
    });
