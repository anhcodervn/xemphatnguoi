<?php

use App\Models\Setting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('settings');

    Schema::create('settings', function (Blueprint $table): void {
        $table->id();
        $table->string('key')->unique();
        $table->longText('value')->nullable();
        $table->string('type')->default('string');
        $table->timestamps();
    });
});

test('public content page renders content and metadata from settings', function () {
    Setting::query()->create([
        'key' => 'site_name',
        'value' => 'Apibankvn.com',
        'type' => 'string',
    ]);

    Setting::query()->create([
        'key' => 'about_page_title',
        'value' => 'Về chúng tôi',
        'type' => 'string',
    ]);

    Setting::query()->create([
        'key' => 'about_page_excerpt',
        'value' => 'Thông tin giới thiệu tổng quan về hệ thống.',
        'type' => 'string',
    ]);

    Setting::query()->create([
        'key' => 'about_page_seo_title',
        'value' => 'Giới thiệu | Apibankvn.com',
        'type' => 'string',
    ]);

    Setting::query()->create([
        'key' => 'about_page_content',
        'value' => json_encode([
            [
                'type' => 'heading',
                'level' => 2,
                'children' => [
                    ['text' => 'Về chúng tôi'],
                ],
            ],
            [
                'type' => 'paragraph',
                'children' => [
                    ['text' => 'ApibankVN là nền tảng hỗ trợ API banking.'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'type' => 'json',
    ]);

    $response = $this->get('/gioi-thieu');

    $response->assertOk();
    $response->assertSee('Giới thiệu | Apibankvn.com');
    $response->assertSee('Về chúng tôi');
    $response->assertSee('Thông tin giới thiệu tổng quan về hệ thống.');
    $response->assertSee('ApibankVN là nền tảng hỗ trợ API banking.');
    $response->assertSee('/dieu-khoan-su-dung', false);
});
