<?php

use App\Features\Admin\ApiLog\Controllers\ApiLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/api-logs')
    ->name('admin-api.api-logs.')
    ->group(function (): void {
        Route::get('/', [ApiLogController::class, 'index'])->name('index');
    });
