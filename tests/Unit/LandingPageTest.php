<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('packages');
    Schema::dropIfExists('settings');

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

    Schema::create('settings', function ($table): void {
        $table->id();
        $table->string('key')->unique();
        $table->longText('value')->nullable();
        $table->string('type')->nullable();
        $table->timestamps();
    });
});

test('landing page renders successfully with configured logo setting', function () {
    DB::table('settings')->insert([
        ['key' => 'site_name', 'value' => 'Apibankvn', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'light_logo', 'value' => 'https://cdn.example.com/logo-light.png', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('packages')->insert([
        'name' => 'Gói tháng',
        'slug' => 'goi-thang',
        'description' => 'Mô tả gói tháng',
        'price' => 100000,
        'duration_days' => 30,
        'account_limit' => 1,
        'can_buy_extra_account' => 0,
        'extra_account_price' => 0,
        'request_limit' => 1000,
        'request_per_minute' => 60,
        'concurrent_limit' => 1,
        'features' => json_encode(['API key', 'Webhook'], JSON_UNESCAPED_UNICODE),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('https://cdn.example.com/logo-light.png', false);
    $response->assertSee('Tạo tài khoản');
    $response->assertSee('Tạo lệnh nạp, quét giao dịch và xác nhận chuyển khoản tự động');
    $response->assertSee('/gioi-thieu', false);
    $response->assertSee('/chinh-sach-bao-mat', false);
});
