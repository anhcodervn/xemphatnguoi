<?php

use App\Http\Controllers\SpaController;
use App\Http\Middleware\AuthenticateApiKey;
use App\Http\Middleware\EnsureAdminUser;
use App\Http\Middleware\EnsureApiKeyPermission;
use App\Http\Middleware\EnsureSiteIsActive;
use App\Http\Middleware\LogApiRequest;
use App\Support\SettingStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'admin' => EnsureAdminUser::class,
            'api-key.auth' => AuthenticateApiKey::class,
            'api-key.permission' => EnsureApiKeyPermission::class,
            'api-key.log' => LogApiRequest::class,
            'site.active' => EnsureSiteIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $throwable, Request $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('admin-api/*')) {
                return null;
            }

            $statusCode = $throwable instanceof HttpExceptionInterface
                ? $throwable->getStatusCode()
                : 500;

            if (! in_array($statusCode, [404, 520, 524], true)) {
                return null;
            }

            /** @var SettingStore $settingStore */
            $settingStore = app(SettingStore::class);

            $systemSettings = $settingStore->getMany([
                'site_name' => config('app.name', 'XemPhatNguoi.vn'),
                'site_description' => '',
                'support_email' => '',
                'hotline' => '',
                'light_logo' => '',
                'dark_logo' => '',
                'favicon' => '',
            ]);

            $context = 'landing';

            if ($request->is('admin*')) {
                $context = 'admin';
            } elseif ($request->user() !== null) {
                $context = 'client';
            }

            $contextActions = [
                'landing' => [
                    'primary' => ['label' => 'Về trang chủ', 'href' => '/'],
                    'secondary' => ['label' => 'Liên hệ hỗ trợ', 'href' => '/lien-he'],
                ],
                'client' => [
                    'primary' => ['label' => 'Về tổng quan', 'href' => '/dashboard'],
                    'secondary' => ['label' => 'Liên hệ & góp ý', 'href' => '/dashboard/contact'],
                ],
                'admin' => [
                    'primary' => ['label' => 'Về dashboard admin', 'href' => '/admin'],
                    'secondary' => ['label' => 'Quản lý queue', 'href' => '/admin/queues'],
                ],
            ];

            return response()->view("errors.{$statusCode}", [
                'errorContext' => $context,
                'errorActions' => Arr::get($contextActions, $context, $contextActions['landing']),
                'systemSettings' => $systemSettings,
            ], $statusCode);
        });
    })->create();
