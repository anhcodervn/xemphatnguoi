<?php

use App\Features\Client\ApiKey\Controllers\ApiKeyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('client/api-keys')
    ->name('api.client.api-keys.')
    ->controller(ApiKeyController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/permissions', 'permissions')->name('permissions');
        Route::post('/', 'store')->name('store');
        Route::patch('/{apiKey}', 'update')->name('update');
        Route::post('/{apiKey}/rotate-secret', 'rotateSecret')->name('rotate-secret');
    });
