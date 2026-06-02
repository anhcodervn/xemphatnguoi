<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');
    Schema::dropIfExists('settings');

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

    Schema::create('settings', function (Blueprint $table): void {
        $table->id();
        $table->string('key')->unique();
        $table->longText('value')->nullable();
        $table->string('type')->default('string');
        $table->timestamps();
    });

    Schema::create('wallets', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });
});

test('authenticated app shell uses setting title and exposes site name for spa titles', function () {
    Setting::query()->create([
        'key' => 'site_name',
        'value' => 'Apibankvn.com',
        'type' => 'string',
    ]);

    Setting::query()->create([
        'key' => 'meta_title',
        'value' => 'Cổng quản lý Apibankvn.com',
        'type' => 'string',
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk();
    $response->assertSee('<title>Cổng quản lý Apibankvn.com</title>', false);
    $response->assertSee('data-site-name="Apibankvn.com"', false);
});

test('auth pages use vietnamese route title with site name', function () {
    Setting::query()->create([
        'key' => 'site_name',
        'value' => 'Apibankvn.com',
        'type' => 'string',
    ]);

    $response = $this->get('/forgot-password');

    $response->assertOk();
    $response->assertSee('<title>Quên mật khẩu - Apibankvn.com</title>', false);
});
