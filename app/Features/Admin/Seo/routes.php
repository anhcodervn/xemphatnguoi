<?php

use App\Features\Admin\Seo\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'admin'])
    ->prefix('admin-api/seo')
    ->name('admin-api.seo.')
    ->group(function (): void {
        Route::get('/overview', [SeoController::class, 'overview'])->name('overview');

        Route::get('/categories', [SeoController::class, 'categories'])->name('categories.index');
        Route::post('/categories', [SeoController::class, 'storeCategory'])->name('categories.store');
        Route::get('/categories/{seoCategory}', [SeoController::class, 'showCategory'])->name('categories.show');
        Route::patch('/categories/{seoCategory}', [SeoController::class, 'updateCategory'])->name('categories.update');
        Route::post('/categories/{seoCategory}/merge', [SeoController::class, 'mergeCategory'])->name('categories.merge');
        Route::delete('/categories/{seoCategory}', [SeoController::class, 'destroyCategory'])->name('categories.destroy');

        Route::get('/tags', [SeoController::class, 'tags'])->name('tags.index');
        Route::post('/tags', [SeoController::class, 'storeTag'])->name('tags.store');
        Route::patch('/tags/{seoTag}', [SeoController::class, 'updateTag'])->name('tags.update');
        Route::post('/tags/{seoTag}/merge', [SeoController::class, 'mergeTag'])->name('tags.merge');
        Route::delete('/tags/{seoTag}', [SeoController::class, 'destroyTag'])->name('tags.destroy');

        Route::get('/posts', [SeoController::class, 'posts'])->name('posts.index');
        Route::post('/posts', [SeoController::class, 'storePost'])->name('posts.store');
        Route::get('/posts/{seoPost}', [SeoController::class, 'showPost'])->name('posts.show');
        Route::patch('/posts/{seoPost}', [SeoController::class, 'updatePost'])->name('posts.update');
        Route::post('/posts/{seoPost}/save-draft', [SeoController::class, 'saveDraft'])->name('posts.save-draft');
        Route::post('/posts/{seoPost}/approve', [SeoController::class, 'approve'])->name('posts.approve');
        Route::post('/posts/{seoPost}/reject', [SeoController::class, 'reject'])->name('posts.reject');
        Route::post('/posts/{seoPost}/publish', [SeoController::class, 'publish'])->name('posts.publish');
        Route::delete('/posts/{seoPost}', [SeoController::class, 'destroyPost'])->name('posts.destroy');

        Route::get('/sitemaps', [SeoController::class, 'sitemaps'])->name('sitemaps.index');
    });
