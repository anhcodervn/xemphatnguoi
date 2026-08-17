<?php

use App\Features\Admin\Analytics\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/analytics')
    ->name('admin.analytics.')
    ->controller(AnalyticsController::class)
    ->group(function (): void {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    });
