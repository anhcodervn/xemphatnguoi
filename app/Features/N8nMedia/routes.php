<?php

use App\Features\N8nMedia\Controllers\N8nMediaController;
use App\Http\Middleware\VerifyN8nContentApi;
use Illuminate\Support\Facades\Route;

Route::prefix('internal/n8n/media')
    ->middleware([VerifyN8nContentApi::class, 'throttle:n8n-content'])
    ->name('internal.n8n.media.')
    ->controller(N8nMediaController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{filename}', 'show')
            ->where('filename', '[A-Za-z0-9._-]+\.webp')
            ->name('show');
        Route::post('/', 'store')->name('store');
        Route::delete('/{filename}', 'destroy')
            ->where('filename', '[A-Za-z0-9._-]+\.webp')
            ->name('destroy');
    });
