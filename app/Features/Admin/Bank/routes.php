<?php

use App\Features\Admin\Bank\Controllers\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/banks')
    ->name('admin-api.banks.')
    ->group(function (): void {
        Route::get('/', [BankController::class, 'index'])->name('index');
        Route::post('/', [BankController::class, 'store'])->name('store');
        Route::get('{bank}', [BankController::class, 'show'])->name('show');
        Route::patch('{bank}', [BankController::class, 'update'])->name('update');
        Route::delete('{bank}', [BankController::class, 'destroy'])->name('destroy');
    });
