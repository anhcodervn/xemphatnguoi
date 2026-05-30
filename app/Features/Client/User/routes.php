<?php

use App\Features\Client\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('client/user')
    ->name('client/user.')
    ->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('index');
    });
