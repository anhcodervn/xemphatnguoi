<?php

use App\Models\SeoCategory;
use App\Models\SeoPost;
use App\Models\SeoPostActivityLog;
use App\Models\SeoTag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('requires an admin for every content workflow action', function (): void {
    $article = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_PENDING_REVIEW,
        'created_by_type' => SeoPost::CREATOR_N8N,
    ]);

    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/admin-api/seo/posts/{$article->id}/approve")->assertForbidden();
    $this->postJson("/api/admin-api/seo/posts/{$article->id}/publish")->assertForbidden();
});

it('requires approval before an admin can publish', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $article = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_PENDING_REVIEW,
        'created_by_type' => SeoPost::CREATOR_N8N,
    ]);
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin-api/seo/posts/{$article->id}/publish")
        ->assertConflict();

    $this->postJson("/api/admin-api/seo/posts/{$article->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.status', SeoPost::STATUS_APPROVED);

    $this->postJson("/api/admin-api/seo/posts/{$article->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', SeoPost::STATUS_PUBLISHED);

    $article->refresh();

    expect($article->reviewed_by)->toBe($admin->id)
        ->and($article->reviewed_at)->not->toBeNull()
        ->and($article->published_by)->toBe($admin->id)
        ->and($article->published_at)->not->toBeNull()
        ->and($article->canonical_url)->toEndWith('/blog/'.$article->slug)
        ->and(SeoPostActivityLog::query()->where('action', 'approved')->exists())->toBeTrue()
        ->and(SeoPostActivityLog::query()->where('action', 'published')->exists())->toBeTrue();

    $this->get('/blog/'.$article->slug)->assertOk();
    $this->get('/sitemap.xml')->assertOk()->assertSee('/blog/'.$article->slug, false);
});

it('stores rejection feedback for a pending n8n article', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $article = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_PENDING_REVIEW,
        'created_by_type' => SeoPost::CREATOR_N8N,
    ]);
    Sanctum::actingAs($admin);

    $this->postJson("/api/admin-api/seo/posts/{$article->id}/reject", [
        'rejection_reason' => 'Nguồn chưa đủ độ tin cậy.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', SeoPost::STATUS_REJECTED)
        ->assertJsonPath('data.rejection_reason', 'Nguồn chưa đủ độ tin cậy.');

    expect($article->fresh()->reviewed_by)->toBe($admin->id)
        ->and($article->fresh()->reviewed_at)->not->toBeNull();
});

it('filters review articles and returns source taxonomy and audit fields', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = SeoCategory::factory()->create();
    $tag = SeoTag::factory()->create();
    $article = SeoPost::factory()->create([
        'seo_category_id' => $category->id,
        'title' => 'Bài n8n cần duyệt',
        'status' => SeoPost::STATUS_PENDING_REVIEW,
        'created_by_type' => SeoPost::CREATOR_N8N,
        'source_domain' => 'csgt.vn',
    ]);
    $article->seoTags()->attach($tag);
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin-api/seo/posts?status=pending_review&category_id='.$category->id.'&source=csgt.vn&created_by_type=n8n')
        ->assertOk()
        ->assertJsonCount(1, 'data.posts')
        ->assertJsonPath('data.posts.0.id', $article->id)
        ->assertJsonPath('data.posts.0.seo_tags.0.id', $tag->id);
});

it('only deletes unused taxonomy and can merge duplicates', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $sourceCategory = SeoCategory::factory()->create();
    $targetCategory = SeoCategory::factory()->create();
    $sourceTag = SeoTag::factory()->create();
    $targetTag = SeoTag::factory()->create();
    $article = SeoPost::factory()->create(['seo_category_id' => $sourceCategory->id]);
    $article->seoTags()->attach($sourceTag);

    $this->deleteJson("/api/admin-api/seo/categories/{$sourceCategory->id}")->assertConflict();
    $this->deleteJson("/api/admin-api/seo/tags/{$sourceTag->id}")->assertConflict();

    $this->postJson("/api/admin-api/seo/categories/{$sourceCategory->id}/merge", ['target_id' => $targetCategory->id])->assertOk();
    $this->postJson("/api/admin-api/seo/tags/{$sourceTag->id}/merge", ['target_id' => $targetTag->id])->assertOk();

    expect($article->fresh()->seo_category_id)->toBe($targetCategory->id)
        ->and($article->seoTags()->whereKey($targetTag->id)->exists())->toBeTrue()
        ->and(SeoCategory::query()->whereKey($sourceCategory->id)->exists())->toBeFalse()
        ->and(SeoTag::query()->whereKey($sourceTag->id)->exists())->toBeFalse();
});
