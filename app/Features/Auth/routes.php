<?php

use App\Features\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('api.auth.')
    ->group(function (): void {
        Route::get('/', [AuthController::class, 'index'])->name('index');

        Route::post('login', [AuthController::class, 'apiLogin'])->middleware('throttle:10,1')->name('login');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1')->name('forgot-password');
    });
