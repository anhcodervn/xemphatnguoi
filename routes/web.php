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
    ];

    return view('pages.landing.index', [
        'systemSettings' => [
            ...$defaults,
            ...$settingStore->getArray('system', []),
        ],
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

if (file_exists(base_path('app/Features/Admin/Package/routes.php'))) {
    require base_path('app/Features/Admin/Package/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Recharge/routes.php'))) {
    require base_path('app/Features/Admin/Recharge/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Couponts/routes.php'))) {
    require base_path('app/Features/Admin/Couponts/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Setting/routes.php'))) {
    require base_path('app/Features/Admin/Setting/routes.php');
}

Route::middleware('auth')->get('/{any}', function () {
    return view('app');
})->where('any', '.*');

if (file_exists(base_path('app/Features/Client/Bank/routes.php'))) {
    require base_path('app/Features/Client/Bank/routes.php');
}

if (file_exists(base_path('app/Features/Client/Webhook/routes.php'))) {
    require base_path('app/Features/Client/Webhook/routes.php');
}

if (file_exists(base_path('app/Features/Client/Recharge/routes.php'))) {
    require base_path('app/Features/Client/Recharge/routes.php');
}

if (file_exists(base_path('app/Features/Client/Notifications/routes.php'))) {
    require base_path('app/Features/Client/Notifications/routes.php');
}

if (file_exists(base_path('app/Features/Api/V1/routes.php'))) {
    require base_path('app/Features/Api/V1/routes.php');
}
