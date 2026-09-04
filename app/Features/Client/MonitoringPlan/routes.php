<?php

use App\Features\Client\MonitoringPlan\Controllers\MonitoringPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('client/monitoring-plans')
    ->name('client.monitoring-plans.')
    ->controller(MonitoringPlanController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/subscribe', 'subscribe')->middleware('throttle:10,1')->name('subscribe');
    });
