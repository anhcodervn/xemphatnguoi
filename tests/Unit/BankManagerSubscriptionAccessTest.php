<?php

use App\Models\BankAccount;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('bank_accounts');
    Schema::dropIfExists('banks');
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

    Schema::create('banks', function ($table): void {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');
        $table->string('short_name')->nullable();
        $table->string('logo')->nullable();
        $table->string('bg_color')->nullable();
        $table->json('metadata')->nullable();
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('bank_accounts', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('bank_name');
        $table->string('account_name');
        $table->string('account_number');
        $table->string('username')->nullable();
        $table->string('password')->nullable();
        $table->text('token')->nullable();
        $table->text('data_login')->nullable();
        $table->string('proxy')->nullable();
        $table->string('status')->default('active');
        $table->timestamp('last_sync_at')->nullable();
        $table->timestamps();
    });

    DB::table('banks')->insert([
        'code' => 'acb',
        'name' => 'Asia Commercial Bank',
        'short_name' => 'ACB',
        'logo' => null,
        'bg_color' => '#2563EB',
        'metadata' => null,
        'is_active' => 1,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function createActivePackage(): Package
{
    return Package::query()->create([
        'name' => 'Pro',
        'slug' => 'pro',
        'price' => 199000,
        'duration_days' => 30,
        'account_limit' => 1,
        'can_buy_extra_account' => false,
        'extra_account_price' => 0,
        'request_limit' => 1000,
        'request_per_minute' => 10,
        'concurrent_limit' => 1,
        'status' => PackageStatus::Active,
    ]);
}

function grantActiveSubscription(User $user, Package $package): UserSubscription
{
    return UserSubscription::query()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => 1,
        'extra_account_limit' => 0,
        'used_account' => 0,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(7),
        'status' => SubscriptionStatus::Active,
    ]);
}

test('user without active subscription cannot access bank manager api', function () {
    $user = User::query()->create([
        'username' => 'basic-user',
        'email' => 'basic@example.com',
        'password' => 'password',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/bank/accounts')
        ->assertForbidden()
        ->assertJson([
            'status' => false,
            'message' => 'Vui lòng đăng ký gói để truy cập vào trang này.',
        ]);
});

test('user with active subscription can access bank manager api', function () {
    $user = User::query()->create([
        'username' => 'pro-user',
        'email' => 'pro@example.com',
        'password' => 'password',
    ]);

    $package = createActivePackage();
    grantActiveSubscription($user, $package);

    BankAccount::query()->create([
        'user_id' => $user->id,
        'bank_name' => 'acb',
        'account_name' => 'Main Account',
        'account_number' => '123456789',
        'username' => 'bank-user',
        'status' => 'active',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/bank/accounts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.account_name', 'Main Account');
});
