<?php

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('api_logs');
    Schema::dropIfExists('api_keys');
    Schema::dropIfExists('bank_accounts');
    Schema::dropIfExists('banks');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
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
        $table->rememberToken()->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('banks', function (Blueprint $table): void {
        $table->id();
        $table->string('code', 50)->unique();
        $table->string('name');
        $table->string('short_name')->nullable();
        $table->string('logo')->nullable();
        $table->string('bg_color', 20)->default('#FFFFFF');
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('sort_order')->default(0);
        $table->unsignedInteger('limit_request_per_minute')->default(6);
        $table->json('metadata')->nullable();
        $table->timestamps();
    });

    Schema::create('bank_accounts', function (Blueprint $table): void {
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

    Schema::create('wallets', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('api_keys', function (Blueprint $table): void {
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

    Schema::create('api_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('api_key_id')->nullable();
        $table->string('endpoint');
        $table->string('method', 10);
        $table->string('ip', 45)->nullable();
        $table->longText('request_data')->nullable();
        $table->longText('response_data')->nullable();
        $table->unsignedSmallInteger('status_code')->nullable();
        $table->unsignedInteger('response_time_ms')->nullable();
        $table->timestamp('created_at')->nullable();
    });
});

function createAdminUserForApiManagement(): User
{
    return User::query()->create([
        'username' => 'admin-user',
        'email' => 'admin@example.com',
        'password' => 'secret',
        'role' => 'admin',
        'status' => 'active',
    ]);
}

test('admin can create and list banks', function () {
    $admin = createAdminUserForApiManagement();
    Sanctum::actingAs($admin);

    $this->postJson('/api/admin-api/banks', [
        'code' => 'mb',
        'name' => 'MB Bank',
        'short_name' => 'MB',
        'limit_request_per_minute' => 10,
        'sort_order' => 1,
        'is_active' => true,
        'metadata' => ['provider' => 'mb'],
    ])->assertCreated()
        ->assertJsonPath('data.code', 'mb');

    $this->getJson('/api/admin-api/banks')
        ->assertOk()
        ->assertJsonPath('data.banks.data.0.code', 'mb')
        ->assertJsonPath('data.summary.total', 1);
});

test('admin can delete bank and related bank accounts by bank code', function () {
    $admin = createAdminUserForApiManagement();
    Sanctum::actingAs($admin);

    $bankId = DB::table('banks')->insertGetId([
        'code' => 'mb',
        'name' => 'MB Bank',
        'short_name' => 'MBBank',
        'bg_color' => '#FFFFFF',
        'is_active' => true,
        'sort_order' => 1,
        'limit_request_per_minute' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('bank_accounts')->insert([
        [
            'bank_name' => 'mb',
            'account_name' => 'Account One',
            'account_number' => '001',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'bank_name' => 'mb',
            'account_name' => 'Account Two',
            'account_number' => '002',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'bank_name' => 'vcb',
            'account_name' => 'Remain Account',
            'account_number' => '003',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->deleteJson("/api/admin-api/banks/{$bankId}")
        ->assertOk()
        ->assertJsonPath('data.deleted_bank_accounts', 2);

    expect(DB::table('banks')->where('id', $bankId)->exists())->toBeFalse();
    expect(DB::table('bank_accounts')->where('bank_name', 'mb')->count())->toBe(0);
    expect(DB::table('bank_accounts')->where('bank_name', 'vcb')->count())->toBe(1);
});

test('admin can list api keys and api logs', function () {
    $admin = createAdminUserForApiManagement();
    $customer = User::query()->create([
        'username' => 'customer-user',
        'email' => 'customer@example.com',
        'password' => 'secret',
        'role' => 'user',
        'status' => 'active',
    ]);

    $apiKey = ApiKey::query()->create([
        'user_id' => $customer->id,
        'name' => 'Primary Key',
        'api_key' => 'key_live_123',
        'api_secret' => 'hashed-secret',
        'permissions' => ['profile.read'],
        'ip_whitelist' => ['127.0.0.1'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    DB::table('api_logs')->insert([
        'user_id' => $customer->id,
        'api_key_id' => $apiKey->id,
        'endpoint' => '/api/v1/me',
        'method' => 'GET',
        'ip' => '127.0.0.1',
        'request_data' => json_encode(['foo' => 'bar']),
        'response_data' => json_encode(['status' => true]),
        'status_code' => 200,
        'response_time_ms' => 35,
        'created_at' => now(),
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/admin-api/api-keys')
        ->assertOk()
        ->assertJsonPath('data.api_keys.data.0.name', 'Primary Key')
        ->assertJsonPath('data.summary.total', 1);

    $this->getJson('/api/admin-api/api-logs')
        ->assertOk()
        ->assertJsonPath('data.api_logs.data.0.endpoint', '/api/v1/me')
        ->assertJsonPath('data.summary.total', 1);
});
