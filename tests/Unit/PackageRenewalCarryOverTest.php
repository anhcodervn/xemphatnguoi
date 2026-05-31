<?php

use App\Features\Client\Package\Services\PackageCheckoutService;
use App\Models\Account;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    Schema::dropIfExists('wallet_transactions');
    Schema::dropIfExists('accounts');
    Schema::dropIfExists('package_orders');
    Schema::dropIfExists('user_subscriptions');
    Schema::dropIfExists('packages');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('full_name')->nullable();
        $table->string('avatar')->nullable();
        $table->string('google_id')->nullable()->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamp('last_login_at')->nullable();
        $table->string('last_login_ip', 45)->nullable();
        $table->string('referral_code')->nullable()->unique();
        $table->unsignedBigInteger('referred_by')->nullable();
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('wallets', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('packages', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->decimal('price', 16, 2)->default(0);
        $table->integer('duration_days')->default(30);
        $table->integer('account_limit')->default(1);
        $table->boolean('can_buy_extra_account')->default(false);
        $table->decimal('extra_account_price', 16, 2)->default(0);
        $table->integer('request_limit')->default(0);
        $table->integer('request_per_minute')->default(0);
        $table->integer('concurrent_limit')->default(0);
        $table->json('features')->nullable();
        $table->string('status')->default(PackageStatus::Active->value);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('user_subscriptions', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('package_id');
        $table->unsignedBigInteger('order_id')->nullable();
        $table->string('package_name');
        $table->decimal('package_price', 16, 2)->default(0);
        $table->integer('base_account_limit')->default(1);
        $table->integer('extra_account_limit')->default(0);
        $table->integer('used_account')->default(0);
        $table->timestamp('starts_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->string('status')->default(SubscriptionStatus::Active->value);
        $table->timestamps();
    });

    Schema::create('package_orders', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('package_id');
        $table->unsignedBigInteger('source_subscription_id')->nullable();
        $table->string('order_code')->unique();
        $table->decimal('price', 16, 2)->default(0);
        $table->decimal('discount_amount', 16, 2)->default(0);
        $table->decimal('credit_amount', 16, 2)->default(0);
        $table->decimal('final_amount', 16, 2)->default(0);
        $table->string('payment_method')->nullable();
        $table->string('payment_status')->default('pending');
        $table->string('status')->default('pending');
        $table->timestamp('paid_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });

    Schema::create('accounts', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('subscription_id');
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('wallet_transactions', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('wallet_id');
        $table->string('type');
        $table->decimal('amount', 16, 2)->default(0);
        $table->decimal('balance_before', 16, 2)->default(0);
        $table->decimal('balance_after', 16, 2)->default(0);
        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();
        $table->string('description')->nullable();
        $table->string('status')->default('success');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('renewal carries purchased extra quota and moves accounts to new subscription', function () {
    $user = User::query()->create([
        'username' => 'renewal-user',
        'email' => 'renewal@example.com',
        'password' => 'password',
    ]);

    $user->wallet()->update([
        'balance' => 500000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $package = Package::query()->create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price' => 300000,
        'duration_days' => 30,
        'account_limit' => 2,
        'can_buy_extra_account' => true,
        'extra_account_price' => 75000,
        'request_limit' => 1000,
        'request_per_minute' => 20,
        'concurrent_limit' => 2,
        'status' => PackageStatus::Active,
    ]);

    $expiredSubscription = UserSubscription::query()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => 2,
        'extra_account_limit' => 3,
        'used_account' => 2,
        'starts_at' => Carbon::parse('2026-04-15 12:00:00'),
        'expires_at' => Carbon::parse('2026-05-24 12:00:00'),
        'status' => SubscriptionStatus::Expired,
    ]);

    $accounts = Account::factory()->count(2)->create([
        'user_id' => $user->id,
        'subscription_id' => $expiredSubscription->id,
    ]);

    $service = app(PackageCheckoutService::class);
    $order = $service->createOrder($user, $package);
    $result = $service->payWithWallet($user, $order);

    $subscription = $result['subscription']->fresh();

    expect($order->fresh()->source_subscription_id)->toBe($expiredSubscription->id)
        ->and($subscription->extra_account_limit)->toBe(3)
        ->and($subscription->used_account)->toBe(2)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    foreach ($accounts as $account) {
        expect($account->fresh()->subscription_id)->toBe($subscription->id);
    }

    $walletTransaction = $user->wallet->transactions()->latest('id')->first();

    expect($walletTransaction?->description)->toBe('Gia hạn gói qua đơn hàng '.$order->fresh()->order_code);
});

test('upgrade carries purchased extra quota and moves accounts to the upgraded subscription', function () {
    $user = User::query()->create([
        'username' => 'upgrade-user',
        'email' => 'upgrade@example.com',
        'password' => 'password',
    ]);

    $user->wallet()->update([
        'balance' => 900000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $currentPackage = Package::query()->create([
        'name' => 'Basic',
        'slug' => 'basic',
        'price' => 90000,
        'duration_days' => 30,
        'account_limit' => 1,
        'can_buy_extra_account' => true,
        'extra_account_price' => 50000,
        'request_limit' => 500,
        'request_per_minute' => 10,
        'concurrent_limit' => 1,
        'status' => PackageStatus::Active,
    ]);

    $targetPackage = Package::query()->create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => 300000,
        'duration_days' => 30,
        'account_limit' => 3,
        'can_buy_extra_account' => true,
        'extra_account_price' => 50000,
        'request_limit' => 2000,
        'request_per_minute' => 40,
        'concurrent_limit' => 4,
        'status' => PackageStatus::Active,
    ]);

    $sourceSubscription = UserSubscription::query()->create([
        'user_id' => $user->id,
        'package_id' => $currentPackage->id,
        'package_name' => $currentPackage->name,
        'package_price' => $currentPackage->price,
        'base_account_limit' => 1,
        'extra_account_limit' => 2,
        'used_account' => 1,
        'starts_at' => Carbon::parse('2026-05-15 12:00:00'),
        'expires_at' => Carbon::parse('2026-06-14 12:00:00'),
        'status' => SubscriptionStatus::Active,
    ]);

    $account = Account::factory()->create([
        'user_id' => $user->id,
        'subscription_id' => $sourceSubscription->id,
    ]);

    $service = app(PackageCheckoutService::class);
    $order = $service->createOrder($user, $targetPackage);
    $result = $service->payWithWallet($user, $order);

    $subscription = $result['subscription']->fresh();

    expect($subscription->extra_account_limit)->toBe(2)
        ->and($subscription->used_account)->toBe(1)
        ->and($account->fresh()->subscription_id)->toBe($subscription->id)
        ->and($sourceSubscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);

    $walletTransaction = $user->wallet->transactions()->latest('id')->first();

    expect($walletTransaction?->description)->toBe('Nâng cấp gói qua đơn hàng '.$order->fresh()->order_code);
});
