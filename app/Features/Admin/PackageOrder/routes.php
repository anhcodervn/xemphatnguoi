<?php

use App\Features\Admin\PackageOrder\Controllers\PackageOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/package-orders')
    ->name('admin.package-orders.')
    ->controller(PackageOrderController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('{order}', 'show')->name('show');
    });
