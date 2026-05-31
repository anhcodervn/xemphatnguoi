<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function () {
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');
    Schema::dropIfExists('settings');

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

test('admin can update branding settings and values are stored per row', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $payload = [
        'logo' => 'https://cdn.example.com/logo.png',
        'favicon' => 'https://cdn.example.com/favicon.png',
        'og_image' => 'https://cdn.example.com/share.png',
        'color_primary' => '#0F172A',
        'color_accent' => '#2563EB',
        'color_surface' => '#F8FAFC',
    ];

    $response = $this
        ->actingAs($admin)
        ->patchJson('/admin-api/settings/branding', $payload);

    $response
        ->assertOk()
        ->assertJsonPath('data.tab', 'branding')
        ->assertJsonPath('data.settings.og_image', 'https://cdn.example.com/share.png');

    $this->assertDatabaseHas('settings', [
        'key' => 'logo',
        'value' => 'https://cdn.example.com/logo.png',
        'type' => 'string',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'og_image',
        'value' => 'https://cdn.example.com/share.png',
        'type' => 'string',
    ]);

    $this->assertDatabaseMissing('settings', [
        'key' => 'branding',
    ]);
});

test('admin can update option settings without grouped json rows', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $payload = [
        'recharge_syntax' => 'NAP',
        'terms_of_use' => [['type' => 'paragraph', 'children' => [['text' => 'Điều khoản']]]],
        'privacy_policy' => [['type' => 'paragraph', 'children' => [['text' => 'Bảo mật']]]],
        'refund_policy' => [['type' => 'paragraph', 'children' => [['text' => 'Hoàn tiền']]]],
    ];

    $response = $this
        ->actingAs($admin)
        ->patchJson('/admin-api/settings/options', $payload);

    $response
        ->assertOk()
        ->assertJsonPath('data.tab', 'options')
        ->assertJsonPath('data.settings.recharge_syntax', 'NAP');

    $this->assertDatabaseHas('settings', [
        'key' => 'recharge_syntax',
        'value' => 'NAP',
        'type' => 'string',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'terms_of_use',
        'type' => 'json',
    ]);

    $this->assertDatabaseMissing('settings', [
        'key' => 'options',
    ]);
});
