<?php

use App\Features\Client\Notifications\Controllers\NotificationsController;
use Illuminate\Support\Facades\Route;

Route::prefix('client/notifications')
    ->name('client/notifications.')
    ->group(function (): void {
        Route::get('/', [NotificationsController::class, 'index'])->name('index');
    });
