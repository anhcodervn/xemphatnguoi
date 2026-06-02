<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('seo_posts');
    Schema::dropIfExists('seo_categories');
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
});

function createSeoAdmin(): User
{
    return User::query()->create([
        'username' => 'seo-admin',
        'email' => 'seo-admin@example.com',
        'password' => 'secret',
        'role' => 'admin',
        'status' => 'active',
    ]);
}

test('admin can create category and view overview', function () {
    $admin = createSeoAdmin();
    Sanctum::actingAs($admin);

    $this->postJson('/api/admin-api/seo/categories', [
        'name' => 'Hướng dẫn tích hợp API',
        'slug' => 'huong-dan-tich-hop-api',
        'seo_title' => 'Hướng dẫn tích hợp API | ApibankVN',
        'seo_description' => 'Cluster nội dung hướng dẫn tích hợp API banking.',
        'robots' => 'index,follow',
        'is_active' => true,
    ])->assertCreated()
        ->assertJsonPath('data.slug', 'huong-dan-tich-hop-api');

    $this->getJson('/api/admin-api/seo/categories')
        ->assertOk()
        ->assertJsonPath('data.categories.0.name', 'Hướng dẫn tích hợp API');

    $this->getJson('/api/admin-api/seo/overview')
        ->assertOk()
        ->assertJsonPath('data.summary.total_categories', 1)
        ->assertJsonPath('data.summary.indexed_categories', 1);
});

test('admin can create update post and read sitemap summary', function () {
    $admin = createSeoAdmin();
    Sanctum::actingAs($admin);

    $categoryResponse = $this->postJson('/api/admin-api/seo/categories', [
        'name' => 'Vận hành hệ thống',
        'slug' => 'van-hanh-he-thong',
        'robots' => 'index,follow',
        'is_active' => true,
    ])->assertCreated();

    $categoryId = $categoryResponse->json('data.id');

    $postResponse = $this->postJson('/api/admin-api/seo/posts', [
        'seo_category_id' => $categoryId,
        'title' => 'Checklist webhook callback an toàn cho API bank',
        'slug' => 'checklist-webhook-callback-an-toan-cho-api-bank',
        'excerpt' => 'Checklist bảo mật callback và đối soát giao dịch.',
        'content' => [
            [
                'type' => 'paragraph',
                'children' => [
                    ['text' => 'Nội dung hướng dẫn cấu hình webhook callback an toàn.'],
                ],
            ],
        ],
        'seo_title' => 'Checklist webhook callback an toàn | ApibankVN',
        'seo_description' => 'Hướng dẫn canonical, webhook, sign và callback cho API bank.',
        'canonical_url' => 'https://apibankvn.com/blog/checklist-webhook-callback-an-toan-cho-api-bank',
        'robots' => 'index,follow',
        'status' => 'published',
        'article_schema' => true,
        'breadcrumb_schema' => true,
    ])->assertCreated();

    $postId = $postResponse->json('data.id');

    $this->patchJson("/api/admin-api/seo/posts/{$postId}", [
        'seo_category_id' => $categoryId,
        'title' => 'Checklist webhook callback an toàn cho API bank',
        'slug' => 'checklist-webhook-callback-an-toan-cho-api-bank',
        'excerpt' => 'Checklist bảo mật callback và đối soát giao dịch đã cập nhật.',
        'content' => [],
        'seo_title' => 'Checklist webhook callback an toàn | ApibankVN',
        'seo_description' => 'Bản cập nhật hướng dẫn callback và sign.',
        'canonical_url' => 'https://apibankvn.com/blog/checklist-webhook-callback-an-toan-cho-api-bank',
        'robots' => 'index,follow',
        'status' => 'published',
        'article_schema' => true,
        'breadcrumb_schema' => true,
    ])->assertOk()
        ->assertJsonPath('data.excerpt', 'Checklist bảo mật callback và đối soát giao dịch đã cập nhật.');

    $this->getJson('/api/admin-api/seo/posts')
        ->assertOk()
        ->assertJsonPath('data.posts.0.slug', 'checklist-webhook-callback-an-toan-cho-api-bank')
        ->assertJsonPath('data.categories.0.id', $categoryId);

    $this->getJson('/api/admin-api/seo/sitemaps')
        ->assertOk()
        ->assertJsonPath('data.entries.1.included_count', '1 URL');
});
