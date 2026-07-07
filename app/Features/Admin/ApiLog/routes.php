<?php

use App\Features\Admin\ApiLog\Controllers\ApiLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/api-logs')
    ->name('admin-api.api-logs.')
    ->controller(ApiLogController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
    });
