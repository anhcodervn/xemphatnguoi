<?php

use App\Features\Client\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('profile')
    ->name('client/profile.')
    ->controller(ProfileController::class)
    ->group(function (): void {
        Route::get('/', 'show')->name('show');
        Route::patch('/', 'update')->name('update');
        Route::put('/password', 'updatePassword')->name('password.update');
        Route::post('/logout-other-devices', 'logoutOtherDevices')->name('logout-other-devices');
        Route::get('/user-logs', 'userLogs')->name('user-logs.index');
        Route::get('/wallet-transactions', 'walletTransactions')->name('wallet-transactions.index');
    });
