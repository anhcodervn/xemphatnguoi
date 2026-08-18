<?php

use App\Features\N8nContent\Controllers\N8nContentController;
use App\Http\Middleware\VerifyN8nContentApi;
use Illuminate\Support\Facades\Route;

Route::prefix('internal/n8n')
    ->middleware([VerifyN8nContentApi::class, 'throttle:n8n-content'])
    ->name('internal.n8n.')
    ->group(function (): void {
        Route::get('/ping', [N8nContentController::class, 'ping'])->name('ping');
        Route::get('/categories', [N8nContentController::class, 'categories'])->name('categories.index');
        Route::post('/categories', [N8nContentController::class, 'storeCategory'])->name('categories.store');
        Route::get('/tags', [N8nContentController::class, 'tags'])->name('tags.index');
        Route::post('/tags', [N8nContentController::class, 'storeTag'])->name('tags.store');
        Route::post('/articles/check', [N8nContentController::class, 'checkArticle'])->name('articles.check');
        Route::post('/articles', [N8nContentController::class, 'storeArticle'])->name('articles.store');
        Route::put('/articles/{seoPost}', [N8nContentController::class, 'updateArticle'])->name('articles.update');
    });
