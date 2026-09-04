<?php

use App\Features\Admin\MonitoringPlan\Controllers\MonitoringPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/monitoring/plans')
    ->name('admin.monitoring-plans.')
    ->controller(MonitoringPlanController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::patch('/{monitoringPlan}', 'update')->name('update');
        Route::delete('/{monitoringPlan}', 'destroy')->name('destroy');
    });
