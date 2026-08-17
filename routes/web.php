<?php

use App\Features\Auth\Controllers\AuthController;
use App\Features\TrafficFine\Controllers\PublicTrafficFineController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\PublicContentPageController;
use App\Http\Controllers\PublicSeoFileController;
use App\Http\Controllers\PublicSeoPageController;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Route;

Route::middleware('site.active')->controller(PublicTrafficFineController::class)->group(function (): void {
    Route::get('/', 'home')->name('traffic-fines.home');
    Route::get('/tra-cuu-phat-nguoi', 'lookupPage')->name('traffic-fines.lookup-page');
    Route::get('/tra-cuu-phat-nguoi-o-to', 'topic')->defaults('topic', 'car-lookup')->name('traffic-fines.lookup.car');
    Route::get('/tra-cuu-phat-nguoi-xe-may', 'topic')->defaults('topic', 'motorbike-lookup')->name('traffic-fines.lookup.motorbike');
    Route::get('/tra-cuu-phat-nguoi-xe-may-dien', 'topic')->defaults('topic', 'electric-motorbike-lookup')->name('traffic-fines.lookup.electric-motorbike');
    Route::get('/tra-cuu/{plate}', 'result')->name('traffic-fines.result');
    Route::get('/bang-gia', 'pricing')->name('traffic-fines.pricing');
    Route::get('/doi-tac', 'partners')->name('partners.api');
    Route::permanentRedirect('/huong-dan', '/huong-dan-tra-cuu-phat-nguoi')->name('traffic-fines.guide');
    Route::get('/huong-dan-tra-cuu-phat-nguoi', 'guide')->name('traffic-fines.knowledge.guide');
    Route::get('/phat-nguoi-la-gi', 'topic')->defaults('topic', 'what-is')->name('traffic-fines.knowledge.what-is');
    Route::get('/muc-phat', 'topic')->defaults('topic', 'penalties')->name('traffic-fines.penalties.index');
    Route::get('/muc-phat/loi-vuot-den-do', 'topic')->defaults('topic', 'red-light')->name('traffic-fines.penalties.red-light');
    Route::get('/muc-phat/loi-qua-toc-do', 'topic')->defaults('topic', 'speeding')->name('traffic-fines.penalties.speeding');
    Route::get('/muc-phat/loi-sai-lan', 'topic')->defaults('topic', 'wrong-lane')->name('traffic-fines.penalties.wrong-lane');
    Route::get('/muc-phat/loi-di-nguoc-chieu', 'topic')->defaults('topic', 'wrong-way')->name('traffic-fines.penalties.wrong-way');
    Route::get('/muc-phat/loi-dung-do-sai-quy-dinh', 'topic')->defaults('topic', 'parking')->name('traffic-fines.penalties.parking');
    Route::get('/muc-phat/loi-khong-chap-hanh-bien-bao', 'topic')->defaults('topic', 'signs')->name('traffic-fines.penalties.signs');
});

Route::get('/sitemap.xml', [PublicTrafficFineController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [PublicSeoFileController::class, 'robots'])->name('robots');
Route::get('/ads.txt', [PublicSeoFileController::class, 'ads'])->name('ads');

Route::middleware(['guest', 'site.active'])->group(function (): void {
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

Route::middleware('site.active')
    ->get('/dashboard/lookup', fn () => redirect()->route('traffic-fines.lookup-page'))
    ->name('dashboard.lookup.redirect');

Route::middleware(['auth', 'site.active'])->get('/dashboard/{any?}', function (SettingStore $settingStore) {
    return view('app', [
        'systemSettings' => $settingStore->getMany([
            'site_name' => config('app.name', 'XemPhatNguoi.vn'),
            'meta_title' => '',
            'meta_description' => '',
            'gtm_id' => '',
            'meta_pixel_id' => '',
            'custom_header' => config('system_settings.defaults.seo.custom_header', ''),
            'custom_script' => '',
            'light_logo' => '',
            'dark_logo' => '',
            'favicon' => '',
        ]),
    ]);
})->where('any', '.*')->name('dashboard');

Route::middleware(['auth', 'admin', 'site.active'])->get('/admin/{any?}', function (SettingStore $settingStore) {
    return view('app', [
        'systemSettings' => $settingStore->getMany([
            'site_name' => config('app.name', 'XemPhatNguoi.vn'),
            'meta_title' => '',
            'meta_description' => '',
            'gtm_id' => '',
            'meta_pixel_id' => '',
            'custom_header' => config('system_settings.defaults.seo.custom_header', ''),
            'custom_script' => '',
            'light_logo' => '',
            'dark_logo' => '',
            'favicon' => '',
        ]),
    ]);
})->where('any', '.*')->name('admin.dashboard');

if (file_exists(base_path('app/Features/BlogPost/routes.php'))) {
    require base_path('app/Features/BlogPost/routes.php');
}
