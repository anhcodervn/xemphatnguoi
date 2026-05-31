<?php

use App\Features\Admin\Package\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/packages')
    ->name('admin-api.packages.')
    ->controller(PackageController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{package}', 'show')->name('show');
        Route::patch('{package}', 'update')->name('update');
        Route::delete('{package}', 'destroy')->name('destroy');
    });
