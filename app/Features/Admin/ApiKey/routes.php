<?php

use App\Features\Admin\ApiKey\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/api-keys')
    ->name('admin-api.api-keys.')
    ->group(function (): void {
        Route::get('/', [ApiKeyController::class, 'index'])->name('index');
    });
