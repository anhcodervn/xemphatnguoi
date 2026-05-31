<?php

use App\Jobs\SaveUserLogJob;
use App\Models\ApiKey;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Queue::fake();

    Schema::dropIfExists('api_keys');
    Schema::dropIfExists('api_logs');
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

    Schema::create('api_keys', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('name');
        $table->string('api_key')->unique();
        $table->string('api_secret');
        $table->json('permissions')->nullable();
        $table->json('ip_whitelist')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expired_at')->nullable();
        $table->string('status')->default(ApiKey::STATUS_ACTIVE);
        $table->timestamps();
    });

    Schema::create('api_logs', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('api_key_id');
        $table->string('endpoint')->nullable();
        $table->string('method')->nullable();
        $table->integer('status_code')->nullable();
        $table->longText('request_payload')->nullable();
        $table->longText('response_payload')->nullable();
        $table->string('ip', 45)->nullable();
        $table->timestamps();
    });
});

function createApiKeyOwner(): User
{
    $user = User::query()->create([
        'username' => 'api-owner',
        'email' => 'api-owner@example.com',
        'password' => 'password',
    ]);

    $package = Package::query()->create([
        'name' => 'API Package',
        'slug' => 'api-package',
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

    UserSubscription::query()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => 1,
        'extra_account_limit' => 0,
        'used_account' => 0,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(30),
        'status' => SubscriptionStatus::Active,
    ]);

    return $user;
}

test('creating api key queues a user log', function () {
    $user = createApiKeyOwner();
    Sanctum::actingAs($user);

    $this->postJson('/api/client/api-keys', [
        'name' => 'Primary',
        'permissions' => ['profile.read'],
        'ip_whitelist' => ['127.0.0.1'],
    ])->assertCreated();

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_create');
});

test('rotating api key queues a user log', function () {
    $user = createApiKeyOwner();
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Rotate',
        'api_key' => 'ntd_rotate_key',
        'api_secret' => bcrypt('secret'),
        'permissions' => ['profile.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/client/api-keys/{$apiKey->id}/rotate-secret")
        ->assertOk();

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_rotate');
});

test('updating and revoking api key queue user logs', function () {
    $user = createApiKeyOwner();
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Manage',
        'api_key' => 'ntd_manage_key',
        'api_secret' => bcrypt('secret'),
        'permissions' => ['profile.read'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/client/api-keys/{$apiKey->id}", [
        'ip_whitelist' => ['127.0.0.1', '10.0.0.2'],
    ])->assertOk();

    $this->deleteJson("/api/client/api-keys/{$apiKey->id}")
        ->assertOk();

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_update');
    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_key_revoke');
});
