<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('seo_posts');
    Schema::dropIfExists('seo_categories');
    Schema::dropIfExists('settings');

    Schema::create('settings', function (Blueprint $table): void {
        $table->id();
        $table->string('key')->unique();
        $table->longText('value')->nullable();
        $table->string('type')->nullable();
        $table->timestamps();
    });

    Schema::create('seo_categories', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->string('seo_title')->nullable();
        $table->text('seo_description')->nullable();
        $table->string('robots')->default('index,follow');
        $table->boolean('is_active')->default(true);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('seo_posts', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('seo_category_id')->nullable();
        $table->string('title');
        $table->string('slug')->unique();
        $table->text('excerpt')->nullable();
        $table->longText('content')->nullable();
        $table->string('seo_title')->nullable();
        $table->text('seo_description')->nullable();
        $table->string('canonical_url')->nullable();
        $table->string('robots')->default('index,follow');
        $table->string('focus_keyword')->nullable();
        $table->string('cover_alt')->nullable();
        $table->boolean('article_schema')->default(true);
        $table->boolean('breadcrumb_schema')->default(true);
        $table->string('status')->default('draft');
        $table->timestamp('published_at')->nullable();
        $table->timestamp('scheduled_at')->nullable();
        $table->timestamps();
    });

    DB::table('settings')->insert([
        ['key' => 'site_name', 'value' => 'Apibankvn.com', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['key' => 'light_logo', 'value' => 'https://cdn.example.com/logo-light.png', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

test('public seo index renders published posts and categories', function () {
    $categoryId = DB::table('seo_categories')->insertGetId([
        'name' => 'API Banking',
        'slug' => 'api-banking',
        'robots' => 'index,follow',
        'is_active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('seo_posts')->insert([
        'seo_category_id' => $categoryId,
        'title' => 'API Banking là gì?',
        'slug' => 'api-banking-la-gi',
        'excerpt' => 'Bài viết giải thích API Banking và luồng nạp tự động.',
        'content' => json_encode([
            [
                'type' => 'paragraph',
                'children' => [
                    ['text' => 'Nội dung bài viết về API Banking.'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'seo_title' => 'API Banking là gì? | Apibankvn.com',
        'seo_description' => 'Tổng quan về API Banking cho doanh nghiệp.',
        'canonical_url' => 'https://apibankvn.com/blog/api-banking-la-gi',
        'robots' => 'index,follow',
        'focus_keyword' => 'API Banking',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/blog');

    $response->assertOk();
    $response->assertSee('Blog &amp; kiến thức API Banking', false);
    $response->assertSee('API Banking là gì?');
    $response->assertSee('/blog/api-banking-la-gi', false);
    $response->assertSee('API Banking');
});

test('public seo detail renders published article content and canonical', function () {
    $categoryId = DB::table('seo_categories')->insertGetId([
        'name' => 'Webhook',
        'slug' => 'webhook',
        'robots' => 'index,follow',
        'is_active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('seo_posts')->insert([
        'seo_category_id' => $categoryId,
        'title' => 'Hướng dẫn webhook callback',
        'slug' => 'huong-dan-webhook-callback',
        'excerpt' => 'Checklist bảo mật webhook callback.',
        'content' => json_encode([
            [
                'type' => 'heading',
                'level' => 2,
                'children' => [
                    ['text' => 'Webhook callback'],
                ],
            ],
            [
                'type' => 'paragraph',
                'children' => [
                    ['text' => 'Nội dung chi tiết về webhook callback.'],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'seo_title' => 'Hướng dẫn webhook callback | Apibankvn.com',
        'seo_description' => 'Nội dung chi tiết về webhook callback.',
        'canonical_url' => 'https://apibankvn.com/blog/huong-dan-webhook-callback',
        'robots' => 'index,follow',
        'focus_keyword' => 'webhook callback',
        'cover_alt' => 'Webhook callback',
        'article_schema' => true,
        'breadcrumb_schema' => true,
        'status' => 'published',
        'published_at' => now()->subHours(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->get('/blog/huong-dan-webhook-callback');

    $response->assertOk();
    $response->assertSee('Hướng dẫn webhook callback | Apibankvn.com');
    $response->assertSee('Nội dung chi tiết về webhook callback.');
    $response->assertSee('rel="canonical"', false);
    $response->assertSee('https://apibankvn.com/blog/huong-dan-webhook-callback', false);
});
