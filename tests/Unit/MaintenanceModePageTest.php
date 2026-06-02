<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('packages');
    Schema::dropIfExists('settings');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('packages', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->nullable();
        $table->text('description')->nullable();
        $table->decimal('price', 16, 2)->default(0);
        $table->integer('duration_days')->default(30);
        $table->integer('account_limit')->default(1);
        $table->boolean('can_buy_extra_account')->default(false);
        $table->decimal('extra_account_price', 16, 2)->default(0);
        $table->integer('request_limit')->default(0);
        $table->integer('request_per_minute')->default(0);
        $table->integer('concurrent_limit')->default(0);
        $table->longText('features')->nullable();
        $table->string('status')->default('active');
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('users', function ($table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->unique();
        $table->string('phone')->unique();
        $table->string('full_name')->nullable();
        $table->string('avatar')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamp('last_login_at')->nullable();
        $table->string('last_login_ip')->nullable();
        $table->string('referral_code')->nullable();
        $table->unsignedBigInteger('referred_by')->nullable();
        $table->rememberToken();
        $table->softDeletes();
        $table->timestamps();
    });

    Schema::create('wallets', function ($table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('settings', function ($table): void {
        $table->id();
        $table->string('key')->unique();
        $table->longText('value')->nullable();
        $table->string('type')->nullable();
        $table->timestamps();
    });
});

test('guest sees maintenance page when site is inactive', function () {
    DB::table('settings')->insert([
        ['key' => 'site_active', 'value' => '0', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'site_name', 'value' => 'ApibankVN', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'system_status_title', 'value' => 'Bao tri he thong', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'system_status_excerpt', 'value' => 'He thong dang nang cap dich vu.', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'support_email', 'value' => 'support@example.com', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'hotline', 'value' => '1900 1234', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $response = $this->get('/');

    $response->assertStatus(503);
    $response->assertSee('Bao tri he thong');
    $response->assertSee('support@example.com');
    $response->assertSee('1900 1234');
});

test('admin can still access the site while maintenance mode is enabled', function () {
    DB::table('settings')->insert([
        ['key' => 'site_active', 'value' => '0', 'type' => 'boolean', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'site_name', 'value' => 'ApibankVN', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get('/');

    $response->assertOk();
    $response->assertSee('id="app"', false);
});
