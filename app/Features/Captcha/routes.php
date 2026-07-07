<?php

use App\Features\Captcha\Controllers\AdminCaptchaServiceController;
use App\Features\Captcha\Controllers\AdminCaptchaSourceController;
use App\Features\Captcha\Controllers\AdminCaptchaTaskController;
use App\Features\Captcha\Controllers\ApiCaptchaController;
use App\Features\Captcha\Controllers\ClientCaptchaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin-api/captcha-sources')->group(function (): void {
    Route::get('/', [AdminCaptchaSourceController::class, 'index']);
    Route::post('/', [AdminCaptchaSourceController::class, 'store']);
    Route::get('/{captchaSource}', [AdminCaptchaSourceController::class, 'show']);
    Route::patch('/{captchaSource}', [AdminCaptchaSourceController::class, 'update']);
    Route::delete('/{captchaSource}', [AdminCaptchaSourceController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin-api/captcha-services')->group(function (): void {
    Route::get('/', [AdminCaptchaServiceController::class, 'index']);
    Route::post('/', [AdminCaptchaServiceController::class, 'store']);
    Route::get('/{captchaService}', [AdminCaptchaServiceController::class, 'show']);
    Route::patch('/{captchaService}', [AdminCaptchaServiceController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin-api/captcha-tasks')->group(function (): void {
    Route::get('/', [AdminCaptchaTaskController::class, 'index']);
});

Route::middleware('auth:sanctum')->prefix('client/captcha')->group(function (): void {
    Route::get('/overview', [ClientCaptchaController::class, 'overview']);
    Route::get('/services', [ClientCaptchaController::class, 'services']);
    Route::get('/tasks', [ClientCaptchaController::class, 'tasks']);
});

Route::middleware(['api-key.auth', 'api-key.log'])->prefix('v1')->group(function (): void {
    Route::get('/services', [ApiCaptchaController::class, 'services'])
        ->middleware('api-key.permission:captcha-services.read');
    Route::get('/balance', [ApiCaptchaController::class, 'balance'])
        ->middleware('api-key.permission:captcha-tasks.read');
    Route::get('/user', [ApiCaptchaController::class, 'userInfo'])
        ->middleware('api-key.permission:captcha-tasks.read');
    Route::post('/create', [ApiCaptchaController::class, 'create'])
        ->middleware('api-key.permission:captcha-tasks.create');
    Route::post('/result', [ApiCaptchaController::class, 'result'])
        ->middleware('api-key.permission:captcha-tasks.read');
});
