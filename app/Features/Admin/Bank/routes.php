<?php

use App\Features\Admin\Bank\Controllers\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin-api/banks')
    ->name('admin-api.banks.')
    ->group(function (): void {
        Route::get('/', [BankController::class, 'index'])->name('index');
        Route::patch('{bank}', [BankController::class, 'update'])->name('update');
    });
