<?php

use App\Features\Admin\Webhook\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/webhooks')
    ->name('admin.webhooks.')
    ->controller(WebhookController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('{webhook}', 'show')->name('show');
        Route::post('{webhook}/toggle', 'toggle')->name('toggle');
        Route::post('{webhook}/test', 'test')->name('test');
        Route::get('{webhook}/logs', 'logs')->name('logs.index');
    });
