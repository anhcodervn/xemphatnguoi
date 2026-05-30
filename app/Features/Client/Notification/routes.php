<?php

use App\Features\Client\Notification\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('notifications')
    ->name('client.notifications.')
    ->controller(NotificationController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('{notification}/read', 'markRead')->name('read');
    });
