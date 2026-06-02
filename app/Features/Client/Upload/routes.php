<?php

use App\Features\Client\Upload\Controllers\ImageUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('uploads')
    ->name('client/uploads.')
    ->controller(ImageUploadController::class)
    ->group(function (): void {
        Route::post('/image', 'store')->name('image.store');
    });
