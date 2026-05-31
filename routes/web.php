<?php

use App\Features\Auth\Controllers\AuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (SettingStore $settingStore) {
    if (Auth::check()) {
        return view('app');
    }

    $defaults = [
        'site_name' => config('app.name', 'Nạp Tiền Tự Động'),
        'site_domain' => '',
        'site_description' => '',
        'support_email' => '',
        'hotline' => '',
        'address' => '',
        'facebook' => '',
        'zalo' => '',
        'youtube' => '',
        'meta_title' => '',
        'meta_description' => '',
        'logo' => '',
        'favicon' => '',
        'og_image' => '',
    ];

    return view('pages.landing.index', [
        'systemSettings' => $settingStore->getMany($defaults),
    ]);
});

Route::middleware('guest')->group(function (): void {
    Route::prefix('/auth')->group(function (): void {
        Route::get('/login', function () {
            return view('pages.auth.login');
        })->name('auth.login');

        Route::get('/register', function () {
            return view('pages.auth.register');
        })->name('auth.register');

        Route::post('/login', [AuthController::class, 'login'])->name('auth.login.submit');
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register.submit');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password.submit');
        Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
        Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    });

    Route::get('/login', function () {
        return redirect()->route('auth.login');
    })->name('login');

    Route::get('/forgot-password', function () {
        return view('pages.auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('/reset-password/{token}', function (string $token) {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => request()->string('email')->toString(),
        ]);
    })->name('password.reset');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');

if (file_exists(base_path('app/Features/Cron/routes.php'))) {
    require base_path('app/Features/Cron/routes.php');
}

Route::middleware('auth')->get('/{any}', function () {
    return view('app');
})->where('any', '.*');
