<?php

use App\Exceptions\ApiException;
use App\Features\Api\V1\Actions\StoreRechargeClientOrderAction;
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

function createOwnershipTestPackage(): Package
{
    return Package::query()->create([
        'name' => 'Ownership Pro',
        'slug' => 'ownership-pro',
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

function grantOwnershipAccess(User $user, Package $package): UserSubscription
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

test('client bank accounts endpoint only returns the authenticated user accounts', function () {
    $owner = User::query()->create([
        'username' => 'owner',
        'email' => 'owner@example.com',
        'password' => 'password',
    ]);

    $otherUser = User::query()->create([
        'username' => 'other',
        'email' => 'other@example.com',
        'password' => 'password',
    ]);

    $package = createOwnershipTestPackage();
    grantOwnershipAccess($owner, $package);

    $ownerAccount = BankAccount::query()->create([
        'user_id' => $owner->id,
        'bank_name' => 'acb',
        'account_name' => 'Owner Account',
        'account_number' => '123456789',
        'username' => 'owner-acb',
        'status' => 'active',
    ]);

    BankAccount::query()->create([
        'user_id' => $otherUser->id,
        'bank_name' => 'acb',
        'account_name' => 'Other Account',
        'account_number' => '987654321',
        'username' => 'other-acb',
        'status' => 'active',
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson('/api/bank/accounts');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $ownerAccount->id);
    $response->assertJsonPath('data.0.account_name', 'Owner Account');
});

test('client cannot view another user bank account details', function () {
    $owner = User::query()->create([
        'username' => 'owner-two',
        'email' => 'owner-two@example.com',
        'password' => 'password',
    ]);

    $otherUser = User::query()->create([
        'username' => 'other-two',
        'email' => 'other-two@example.com',
        'password' => 'password',
    ]);

    $package = createOwnershipTestPackage();
    grantOwnershipAccess($owner, $package);

    $otherAccount = BankAccount::query()->create([
        'user_id' => $otherUser->id,
        'bank_name' => 'acb',
        'account_name' => 'Private Account',
        'account_number' => '111222333',
        'username' => 'private-acb',
        'status' => 'active',
    ]);

    Sanctum::actingAs($owner);

    $this->getJson("/api/bank/accounts/{$otherAccount->id}")
        ->assertNotFound();
});

test('client can toggle own bank account status and inactive account cannot be viewed', function () {
    $owner = User::query()->create([
        'username' => 'owner-four',
        'email' => 'owner-four@example.com',
        'password' => 'password',
    ]);

    $package = createOwnershipTestPackage();
    grantOwnershipAccess($owner, $package);

    $bankAccount = BankAccount::query()->create([
        'user_id' => $owner->id,
        'bank_name' => 'acb',
        'account_name' => 'Toggle Account',
        'account_number' => '1122334455',
        'username' => 'toggle-acb',
        'status' => 'active',
    ]);

    Sanctum::actingAs($owner);

    $this->patchJson("/api/bank/accounts/{$bankAccount->id}/status", [
        'status' => 'inactive',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'inactive');

    $this->getJson("/api/bank/accounts/{$bankAccount->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Thẻ này đang tắt. Vui lòng bật lại để sử dụng chức năng này.');
});

test('recharge client order action rejects bank account from another user', function () {
    $owner = User::query()->create([
        'username' => 'owner-three',
        'email' => 'owner-three@example.com',
        'password' => 'password',
    ]);

    $otherUser = User::query()->create([
        'username' => 'other-three',
        'email' => 'other-three@example.com',
        'password' => 'password',
    ]);

    $otherAccount = BankAccount::query()->create([
        'user_id' => $otherUser->id,
        'bank_name' => 'acb',
        'account_name' => 'Other ACB',
        'account_number' => '444555666',
        'username' => 'other-acb-three',
        'status' => 'active',
    ]);

    expect(fn () => app(StoreRechargeClientOrderAction::class)->handle($owner, null, [
        'bank_id' => $otherAccount->id,
        'amount' => 10000,
    ]))->toThrow(ApiException::class);
});
