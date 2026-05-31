<?php

use App\Features\Admin\Setting\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/settings')
    ->name('admin-api.settings.')
    ->controller(SettingController::class)
    ->group(function (): void {
        Route::patch('system', 'updateSystem')->name('system.update');
        Route::patch('options', 'updateOptions')->name('options.update');
        Route::patch('{tab}', 'update')->name('update');
        Route::get('{tab}', 'show')->name('show');
    });
