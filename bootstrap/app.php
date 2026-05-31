<?php

use App\Http\Controllers\SpaController;
use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\EnsureApiKeyPermission;
use App\Http\Middleware\EnsureHasActiveSubscription;
use App\Http\Middleware\LogApiRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Route::middleware('web')->group(base_path('routes/auth.php'));
            // Route::middleware('web')->group(base_path('routes/settings.php'));

            // Route::middleware(['web', 'auth'])->group(function (): void {
            //     Route::get('/dashboard', SpaController::class)->name('dashboard');
            // });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'admin' => EnsureAdminUser::class,
            'active-subscription' => EnsureHasActiveSubscription::class,
            'api-key.permission' => EnsureApiKeyPermission::class,
            'api-key.log' => LogApiRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
