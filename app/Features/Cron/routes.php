<?php

use App\Features\Cron\Controllers\CronController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::prefix('cronjob')
    ->name('cron.')
    ->group(function (): void {
        Route::get('/', [CronController::class, 'index'])->name('index');

        Route::any('callback-apibankvn', [CronController::class, 'callbackApiBankVn'])
            ->withoutMiddleware([VerifyCsrfToken::class])
            ->name('callbackApiBankVn');
    });
