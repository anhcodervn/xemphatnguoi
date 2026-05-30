<?php

use App\Features\Client\Contact\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('contact')
    ->name('client.contact.')
    ->controller(ContactController::class)
    ->group(function (): void {
        Route::post('/feedback', 'store')->name('feedback.store');
    });

Route::middleware('auth:sanctum')
    ->prefix('contact')
    ->name('client.contact.')
    ->controller(ContactController::class)
    ->group(function (): void {
        Route::get('/info', 'info')->name('info');
    });
