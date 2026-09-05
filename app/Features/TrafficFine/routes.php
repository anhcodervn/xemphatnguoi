<?php

use App\Features\TrafficFine\Controllers\AdSlotController;
use App\Features\TrafficFine\Controllers\TrafficFineAdminController;
use App\Features\TrafficFine\Controllers\TrafficFineDashboardController;
use App\Features\TrafficFine\Controllers\TrafficFineLookupController;
use App\Features\TrafficFine\Controllers\UserVehicleController;
use App\Features\TrafficFine\Controllers\VehicleMonitoringController;
use Illuminate\Support\Facades\Route;

Route::post('/lookup', TrafficFineLookupController::class)
    ->middleware('throttle:traffic-fine-lookup')
    ->name('traffic-fines.lookup');

Route::prefix('v1')
    ->middleware(['api-key.auth', 'api-key.permission:traffic-fines.lookup', 'api-key.log', 'throttle:traffic-fine-lookup'])
    ->group(function (): void {
        Route::get('/lookup', TrafficFineLookupController::class)->name('v1.traffic-fines.lookup');
    });

Route::prefix('client/traffic-fines')
    ->name('client.traffic-fines.')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/dashboard', [TrafficFineDashboardController::class, 'index'])->name('dashboard');
        Route::get('/histories', [TrafficFineDashboardController::class, 'histories'])->name('histories');
        Route::get('/api-usage', [TrafficFineDashboardController::class, 'apiUsage'])->name('api-usage');
        Route::get('/monitoring', [VehicleMonitoringController::class, 'index'])->name('monitoring.index');
        Route::post('/lookup', TrafficFineLookupController::class)
            ->middleware('throttle:traffic-fine-lookup')
            ->name('lookup');
        Route::post('/vehicles/{vehicle}/lookup', [UserVehicleController::class, 'lookup'])
            ->middleware('throttle:traffic-fine-lookup')
            ->name('vehicles.lookup');
        Route::patch('/vehicles/{vehicle}/monitoring', [VehicleMonitoringController::class, 'update'])
            ->name('vehicles.monitoring.update');
        Route::apiResource('vehicles', UserVehicleController::class);
    });

Route::prefix('admin-api/traffic-fines')
    ->name('admin.traffic-fines.')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function (): void {
        Route::get('/overview', [TrafficFineAdminController::class, 'overview'])->name('overview');
        Route::get('/report', [TrafficFineAdminController::class, 'report'])->name('report');
        Route::get('/results', [TrafficFineAdminController::class, 'results'])->name('results');
        Route::get('/logs', [TrafficFineAdminController::class, 'logs'])->name('logs');
        Route::get('/provider', [TrafficFineAdminController::class, 'provider'])->name('provider');
        Route::post('/provider', [TrafficFineAdminController::class, 'storeProvider'])->name('provider.store');
        Route::get('/provider/{provider}/balance', [TrafficFineAdminController::class, 'providerBalance'])
            ->middleware('throttle:10,1')
            ->name('provider.balance');
        Route::patch('/provider/{provider}', [TrafficFineAdminController::class, 'updateProvider'])->name('provider.update');
        Route::delete('/provider/{provider}', [TrafficFineAdminController::class, 'destroyProvider'])->name('provider.destroy');
        Route::get('/billing', [TrafficFineAdminController::class, 'billing'])->name('billing');
        Route::put('/billing', [TrafficFineAdminController::class, 'updateBilling'])->name('billing.update');
        Route::apiResource('ad-slots', AdSlotController::class);
    });
