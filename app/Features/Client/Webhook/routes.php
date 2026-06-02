<?php

use App\Features\Client\Webhook\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active-subscription'])
    ->prefix('webhook')
    ->name('client/webhook.')
    ->group(function (): void {
        Route::get('/', [WebhookController::class, 'index'])->name('index');
        Route::get('bank/{bankAccount}', [WebhookController::class, 'byBank'])->name('bank.index');
        Route::post('bank/{bankAccount}/dispatch', [WebhookController::class, 'dispatch'])->name('bank.dispatch');
        Route::post('bank/{bankAccount}/transactions/{bankTransaction}/dispatch', [WebhookController::class, 'dispatchTransaction'])
            ->name('bank.transaction.dispatch');
        Route::post('bank/{bankAccount}', [WebhookController::class, 'store'])->name('bank.store');
        Route::get('{webhook}/logs', [WebhookController::class, 'logs'])->name('logs');
        Route::get('{webhook}/secret', [WebhookController::class, 'secret'])->name('secret');
        Route::put('{webhook}', [WebhookController::class, 'update'])->name('update');
        Route::delete('{webhook}', [WebhookController::class, 'destroy'])->name('destroy');
    });
