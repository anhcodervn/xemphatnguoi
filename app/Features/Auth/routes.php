<?php

use App\Features\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('api.auth.')
    ->group(function (): void {
        Route::get('/', [AuthController::class, 'index'])->name('index');

        Route::post('login', [AuthController::class, 'apiLogin'])->name('login');
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
    });
