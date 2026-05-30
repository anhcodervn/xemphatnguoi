<?php

use App\Features\Api\V1\Controllers\V1Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:api-key', 'api-key.log'])
    ->controller(V1Controller::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/me', 'me')->middleware('api-key.permission:profile.read')->name('me');
        Route::get('/list-bank-accounts', 'listBankAccounts')->middleware('api-key.permission:bank-accounts.read')->name('bank-accounts.index');
        Route::post('/transactions', 'listTransactions')->middleware('api-key.permission:transactions.read')->name('transactions.index');
        Route::post('/recharge-orders', 'storeRechargeOrder')->middleware('api-key.permission:recharge.create')->name('recharge-orders.store');
        Route::get('/recharge-orders/{orderCode}', 'showRechargeOrder')->middleware('api-key.permission:recharge.read')->name('recharge-orders.show');
    });
