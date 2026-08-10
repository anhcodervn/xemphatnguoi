<?php

use App\Features\Client\Proxy\Controllers\ProxyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('client/proxy')->group(function (): void {
    Route::get('/dashboard', [ProxyController::class, 'dashboard']);
    Route::post('/check', [ProxyController::class, 'check'])
        ->middleware('throttle:10,1');
    Route::get('/check/{batch}', [ProxyController::class, 'checkStatus'])
        ->whereUlid('batch')
        ->middleware('throttle:30,1');
    Route::get('/products', [ProxyController::class, 'products']);
    Route::get('/orders', [ProxyController::class, 'orders']);
    Route::get('/orders/{order}', [ProxyController::class, 'showOrder'])
        ->whereNumber('order');
    Route::get('/proxies', [ProxyController::class, 'proxies']);
    Route::get('/proxies/{proxy}', [ProxyController::class, 'proxy'])
        ->whereNumber('proxy');
    Route::post('/orders', [ProxyController::class, 'order']);
    Route::post('/proxies/{proxy}/fetch-rotating', [ProxyController::class, 'fetchRotatingProxy'])
        ->whereNumber('proxy');
    Route::post('/proxies/{proxy}/change-proxy', [ProxyController::class, 'changeProxy'])
        ->whereNumber('proxy');
    Route::post('/proxies/{proxy}/renew', [ProxyController::class, 'renewProxy'])
        ->whereNumber('proxy');
});
