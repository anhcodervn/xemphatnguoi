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
        'light_logo' => 'https://cdn.example.com/logo-light.png',
        'dark_logo' => 'https://cdn.example.com/logo-dark.png',
        'favicon' => 'https://cdn.example.com/favicon.png',
        'og_image' => 'https://cdn.example.com/share.png',
        'color_primary' => '#0F172A',
        'color_accent' => '#2563EB',
        'color_surface' => '#F8FAFC',
    ];

    $response = $this
        ->actingAs($admin)
        ->patchJson('/api/admin-api/settings/branding', $payload);

    $response
        ->assertOk()
        ->assertJsonPath('data.tab', 'branding')
        ->assertJsonPath('data.settings.light_logo', 'https://cdn.example.com/logo-light.png')
        ->assertJsonPath('data.settings.og_image', 'https://cdn.example.com/share.png');

    $this->assertDatabaseHas('settings', [
        'key' => 'light_logo',
        'value' => 'https://cdn.example.com/logo-light.png',
        'type' => 'string',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'dark_logo',
        'value' => 'https://cdn.example.com/logo-dark.png',
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

    $optionPayload = [
        'recharge_syntax' => 'NAP',
        'terms_of_use' => [['type' => 'paragraph', 'children' => [['text' => 'Điều khoản mẫu']]]],
        'privacy_policy' => [['type' => 'paragraph', 'children' => [['text' => 'Bảo mật mẫu']]]],
        'refund_policy' => [['type' => 'paragraph', 'children' => [['text' => 'Hoàn tiền mẫu']]]],
    ];

    $response = $this
        ->actingAs($admin)
        ->patchJson('/api/admin-api/settings/options', $optionPayload);

    $response
        ->assertOk()
        ->assertJsonPath('data.tab', 'options')
        ->assertJsonPath('data.settings.recharge_syntax', 'NAP')
        ->assertJsonPath('data.settings.terms_of_use.0.type', 'paragraph')
        ->assertJsonPath('data.settings.privacy_policy.0.type', 'paragraph')
        ->assertJsonPath('data.settings.refund_policy.0.type', 'paragraph');

    $this->assertDatabaseHas('settings', [
        'key' => 'recharge_syntax',
        'value' => 'NAP',
        'type' => 'string',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'terms_of_use',
        'type' => 'json',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'privacy_policy',
        'type' => 'json',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'refund_policy',
        'type' => 'json',
    ]);

    $this->assertDatabaseMissing('settings', [
        'key' => 'options',
    ]);

    $systemResponse = $this
        ->actingAs($admin)
        ->patchJson('/api/admin-api/settings/system', [
            'site_name' => 'ApibankVN',
            'site_domain' => 'https://apibankvn.com',
            'site_description' => 'Hệ thống API banking',
            'site_active' => true,
            'allow_register' => true,
            'light_logo' => 'https://cdn.example.com/light-logo.webp',
            'dark_logo' => 'https://cdn.example.com/dark-logo.webp',
            'favicon' => 'https://cdn.example.com/favicon.webp',
            'og_image' => 'https://cdn.example.com/og-image.webp',
            'color_primary' => '#0F172A',
            'color_accent' => '#2563EB',
            'color_surface' => '#F8FAFC',
            'support_email' => 'support@example.com',
            'hotline' => '1900 1234',
            'address' => '123 Đường ABC',
            'facebook' => 'https://facebook.com/example',
            'zalo' => 'https://zalo.me/example',
            'youtube' => 'https://youtube.com/example',
            'meta_title' => 'ApibankVN',
            'meta_description' => 'Mô tả hệ thống',
            'robots' => 'index,follow',
            'gtm_id' => 'GTM-123456',
            'meta_pixel_id' => '1234567890',
            'custom_script' => '<script></script>',
            'recharge_syntax' => 'NAP',
        ]);

    $systemResponse->assertOk();

    $this->assertDatabaseHas('settings', [
        'key' => 'terms_of_use',
        'type' => 'json',
    ]);
});

test('admin can update content page settings and values are stored per row', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $payload = [
        'contact_page_title' => 'Liên hệ với chúng tôi',
        'contact_page_excerpt' => 'Thông tin liên hệ và các kênh hỗ trợ.',
        'contact_page_content' => [['type' => 'paragraph', 'children' => [['text' => 'Liên hệ']]]],
        'contact_page_seo_title' => 'Liên hệ - Hỗ trợ khách hàng',
        'contact_page_seo_description' => 'Liên hệ và nhận hỗ trợ nhanh chóng qua email, hotline hoặc các kênh trực tuyến.',
        'contact_page_is_published' => true,
        'terms_page_title' => 'Điều khoản sử dụng',
        'terms_page_excerpt' => '',
        'terms_page_content' => [['type' => 'paragraph', 'children' => [['text' => 'Điều khoản']]]],
        'terms_page_seo_title' => '',
        'terms_page_seo_description' => '',
        'terms_page_is_published' => true,
        'faq_page_title' => 'Câu hỏi thường gặp',
        'faq_page_excerpt' => '',
        'faq_page_content' => [['type' => 'paragraph', 'children' => [['text' => 'FAQ']]]],
        'faq_page_seo_title' => '',
        'faq_page_seo_description' => '',
        'faq_page_is_published' => true,
        'privacy_page_title' => 'Chính sách bảo mật',
        'privacy_page_excerpt' => '',
        'privacy_page_content' => [['type' => 'paragraph', 'children' => [['text' => 'Bảo mật']]]],
        'privacy_page_seo_title' => '',
        'privacy_page_seo_description' => '',
        'privacy_page_is_published' => true,
        'about_page_title' => 'Giới thiệu',
        'about_page_excerpt' => '',
        'about_page_content' => [['type' => 'paragraph', 'children' => [['text' => 'Giới thiệu']]]],
        'about_page_seo_title' => '',
        'about_page_seo_description' => '',
        'about_page_is_published' => true,
        'refund_policy_title' => 'Chính sách hoàn tiền',
        'refund_policy_excerpt' => '',
        'refund_policy_content' => [['type' => 'paragraph', 'children' => [['text' => 'Hoàn tiền']]]],
        'refund_policy_seo_title' => '',
        'refund_policy_seo_description' => '',
        'refund_policy_is_published' => true,
        'payment_policy_title' => 'Chính sách thanh toán',
        'payment_policy_excerpt' => '',
        'payment_policy_content' => [['type' => 'paragraph', 'children' => [['text' => 'Thanh toán']]]],
        'payment_policy_seo_title' => '',
        'payment_policy_seo_description' => '',
        'payment_policy_is_published' => true,
        'api_usage_policy_title' => 'Chính sách sử dụng API',
        'api_usage_policy_excerpt' => '',
        'api_usage_policy_content' => [['type' => 'paragraph', 'children' => [['text' => 'Sử dụng API']]]],
        'api_usage_policy_seo_title' => '',
        'api_usage_policy_seo_description' => '',
        'api_usage_policy_is_published' => true,
        'disclaimer_title' => 'Miễn trừ trách nhiệm',
        'disclaimer_excerpt' => '',
        'disclaimer_content' => [['type' => 'paragraph', 'children' => [['text' => 'Miễn trừ trách nhiệm']]]],
        'disclaimer_seo_title' => '',
        'disclaimer_seo_description' => '',
        'disclaimer_is_published' => true,
        'system_status_title' => 'Trạng thái hệ thống',
        'system_status_excerpt' => '',
        'system_status_content' => [['type' => 'paragraph', 'children' => [['text' => 'Hệ thống ổn định']]]],
        'system_status_seo_title' => '',
        'system_status_seo_description' => '',
        'system_status_is_published' => true,
        'system_updates_title' => 'Cập nhật hệ thống',
        'system_updates_excerpt' => '',
        'system_updates_content' => [['type' => 'paragraph', 'children' => [['text' => 'Đã cập nhật webhook']]]],
        'system_updates_seo_title' => '',
        'system_updates_seo_description' => '',
        'system_updates_is_published' => true,
    ];

    $response = $this
        ->actingAs($admin)
        ->patchJson('/api/admin-api/settings/content-pages', $payload);

    $response
        ->assertOk()
        ->assertJsonPath('data.tab', 'content-pages')
        ->assertJsonPath('data.settings.contact_page_title', 'Liên hệ với chúng tôi')
        ->assertJsonPath('data.settings.faq_page_content.0.type', 'paragraph')
        ->assertJsonPath('data.settings.contact_page_is_published', true);

    $this->assertDatabaseHas('settings', [
        'key' => 'contact_page_title',
        'value' => 'Liên hệ với chúng tôi',
        'type' => 'string',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'contact_page_content',
        'type' => 'json',
    ]);

    $this->assertDatabaseHas('settings', [
        'key' => 'contact_page_is_published',
        'value' => '1',
        'type' => 'boolean',
    ]);
});
