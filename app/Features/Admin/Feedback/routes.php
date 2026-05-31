<?php

use App\Features\Admin\Feedback\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/feedbacks')
    ->name('admin.feedbacks.')
    ->controller(FeedbackController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::patch('{feedback}/status', 'updateStatus')->name('status');
    });
