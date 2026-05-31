<?php

use App\Features\Client\Package\Services\PackageService;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\User;
use App\Support\SettingStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

if (file_exists(base_path('app/Features/Auth/routes.php'))) {
    require base_path('app/Features/Auth/routes.php');
}

if (file_exists(base_path('app/Features/Client/Subscription/routes.php'))) {
    require base_path('app/Features/Client/Subscription/routes.php');
}

if (file_exists(base_path('app/Features/Client/Package/routes.php'))) {
    require base_path('app/Features/Client/Package/routes.php');
}

if (file_exists(base_path('app/Features/Client/Recharge/routes.php'))) {
    require base_path('app/Features/Client/Recharge/routes.php');
}

if (file_exists(base_path('app/Features/Api/V1/routes.php'))) {
    require base_path('app/Features/Api/V1/routes.php');
}

Route::prefix('')->group(function (): void {
    Route::get('/system-settings', function (SettingStore $settingStore) {
        $defaults = [
            'site_name' => config('app.name', 'Nạp Tiền Tự Động'),
            'site_domain' => '',
            'site_description' => '',
            'site_active' => true,
            'allow_register' => true,
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
            'robots' => 'index,follow',
            'gtm_id' => '',
            'meta_pixel_id' => '',
            'custom_script' => '',
            'recharge_syntax' => 'NAP',
            'terms_of_use' => [],
            'privacy_policy' => [],
            'refund_policy' => [],
        ];

        $settings = $settingStore->getMany($defaults);

        return response()->json([
            'status' => true,
            'data' => [
                'settings' => $settings,
            ],
        ]);
    });

    Route::get('/user', function (Request $request, WalletService $walletService, PackageService $packageService) {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return [
            ...$user->only([
                'id',
                'username',
                'email',
                'phone',
                'full_name',
                'avatar',
                'role',
                'status',
                'name',
            ]),
            'wallet' => $walletService->getWalletInfo($user),
            'user_subscriptions' => $packageService->getCurrentUserSubscriptionInfo($user),
        ];
    });
})->middleware('auth:sanctum');

if (file_exists(base_path('app/Features/Client/Bank/routes.php'))) {
    require base_path('app/Features/Client/Bank/routes.php');
}

if (file_exists(base_path('app/Features/Client/Webhook/routes.php'))) {
    require base_path('app/Features/Client/Webhook/routes.php');
}

if (file_exists(base_path('app/Features/Client/Profile/routes.php'))) {
    require base_path('app/Features/Client/Profile/routes.php');
}

if (file_exists(base_path('app/Features/Client/Notification/routes.php'))) {
    require base_path('app/Features/Client/Notification/routes.php');
}

if (file_exists(base_path('app/Features/Client/Contact/routes.php'))) {
    require base_path('app/Features/Client/Contact/routes.php');
}

if (file_exists(base_path('app/Features/Admin/PackageOrder/routes.php'))) {
    require base_path('app/Features/Admin/PackageOrder/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Package/routes.php'))) {
    require base_path('app/Features/Admin/Package/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Couponts/routes.php'))) {
    require base_path('app/Features/Admin/Couponts/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Setting/routes.php'))) {
    require base_path('app/Features/Admin/Setting/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Deposit/routes.php'))) {
    require base_path('app/Features/Admin/Deposit/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Recharge/routes.php'))) {
    require base_path('app/Features/Admin/Recharge/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Webhook/routes.php'))) {
    require base_path('app/Features/Admin/Webhook/routes.php');
}

if (file_exists(base_path('app/Features/Admin/User/routes.php'))) {
    require base_path('app/Features/Admin/User/routes.php');
}

if (file_exists(base_path('app/Features/Admin/WalletTransaction/routes.php'))) {
    require base_path('app/Features/Admin/WalletTransaction/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Notifications/routes.php'))) {
    require base_path('app/Features/Admin/Notifications/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Mail/routes.php'))) {
    require base_path('app/Features/Admin/Mail/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Queue/routes.php'))) {
    require base_path('app/Features/Admin/Queue/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Feedback/routes.php'))) {
    require base_path('app/Features/Admin/Feedback/routes.php');
}

if (file_exists(base_path('app/Features/Admin/Bank/routes.php'))) {
    require base_path('app/Features/Admin/Bank/routes.php');
}

if (file_exists(base_path('app/Features/Client/ApiKey/routes.php'))) {
    require base_path('app/Features/Client/ApiKey/routes.php');
}
