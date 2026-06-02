<?php

use App\Features\Auth\Controllers\AuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\PublicContentPageController;
use App\Http\Controllers\PublicSeoPageController;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (SettingStore $settingStore) {
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
        'light_logo' => '',
        'dark_logo' => '',
        'favicon' => '',
        'og_image' => '',
    ];

    if (Auth::check()) {
        return view('app', [
            'systemSettings' => $settingStore->getMany($defaults),
        ]);
    }

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

        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login.submit');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register.submit');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1')->name('auth.forgot-password.submit');
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
        ->middleware('throttle:3,1')
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

Route::controller(PublicContentPageController::class)->group(function (): void {
    Route::get('/gioi-thieu', 'show')->defaults('slug', 'gioi-thieu')->name('content.about');
    Route::get('/lien-he', 'show')->defaults('slug', 'lien-he')->name('content.contact');
    Route::get('/dieu-khoan-su-dung', 'show')->defaults('slug', 'dieu-khoan-su-dung')->name('content.terms');
    Route::get('/chinh-sach-bao-mat', 'show')->defaults('slug', 'chinh-sach-bao-mat')->name('content.privacy');
    Route::get('/chinh-sach-hoan-tien', 'show')->defaults('slug', 'chinh-sach-hoan-tien')->name('content.refund');
    Route::get('/chinh-sach-thanh-toan', 'show')->defaults('slug', 'chinh-sach-thanh-toan')->name('content.payment');
    Route::get('/chinh-sach-su-dung-api', 'show')->defaults('slug', 'chinh-sach-su-dung-api')->name('content.api-usage');
    Route::get('/mien-tru-trach-nhiem', 'show')->defaults('slug', 'mien-tru-trach-nhiem')->name('content.disclaimer');
    Route::get('/cau-hoi-thuong-gap', 'show')->defaults('slug', 'cau-hoi-thuong-gap')->name('content.faq');
    Route::get('/trang-thai-he-thong', 'show')->defaults('slug', 'trang-thai-he-thong')->name('content.system-status');
    Route::get('/cap-nhat-he-thong', 'show')->defaults('slug', 'cap-nhat-he-thong')->name('content.system-updates');
});

Route::controller(PublicSeoPageController::class)->group(function (): void {
    Route::get('/blog', 'index')->name('seo.index');
    Route::get('/blog/{slug}', 'show')->name('seo.show');
});

Route::middleware('auth')->get('/{any}', function (SettingStore $settingStore) {
    return view('app', [
        'systemSettings' => $settingStore->getMany([
            'site_name' => config('app.name', 'Nạp Tiền Tự Động'),
            'meta_title' => '',
            'meta_description' => '',
            'light_logo' => '',
            'dark_logo' => '',
            'favicon' => '',
        ]),
    ]);
})->where('any', '.*');
