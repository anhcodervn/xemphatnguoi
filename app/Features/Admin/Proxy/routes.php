<?php

use App\Features\Admin\Proxy\Controllers\ProxyCategoryController;
use App\Features\Admin\Proxy\Controllers\ProxyProductController;
use App\Features\Admin\Proxy\Controllers\ProxyProviderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin-api')->group(function (): void {
    Route::apiResource('proxy-categories', ProxyCategoryController::class)
        ->parameters(['proxy-categories' => 'proxyCategory']);
    Route::apiResource('proxy-providers', ProxyProviderController::class)
        ->parameters(['proxy-providers' => 'proxyProvider']);
    Route::apiResource('proxy-products', ProxyProductController::class)
        ->parameters(['proxy-products' => 'proxyProduct'])
        ->except('destroy');
});
