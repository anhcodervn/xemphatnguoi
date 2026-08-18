<?php

use App\Models\SeoCategory;
use App\Models\SeoPost;
use App\Models\SeoPostActivityLog;
use App\Models\SeoTag;

beforeEach(function (): void {
    config()->set('services.n8n_content.enabled', true);
    config()->set('services.n8n_content.key', 'test-n8n-private-key');
    config()->set('services.n8n_content.rate_limit_per_minute', 100);
});

function n8nHeaders(string $key = 'test-n8n-private-key'): array
{
    return [
        'X-N8N-API-KEY' => $key,
        'Accept' => 'application/json',
    ];
}

function n8nArticlePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'title' => 'Vượt đèn đỏ ô tô bị phạt bao nhiêu?',
        'slug' => 'vuot-den-do-o-to-bi-phat-bao-nhieu',
        'excerpt' => 'Mức phạt mới nhất.',
        'content' => '<h2>Mức phạt</h2><p>Thông tin <strong>tham khảo</strong>.</p>',
        'primary_keyword' => 'vượt đèn đỏ ô tô',
        'meta_title' => 'Mức phạt vượt đèn đỏ',
        'meta_description' => 'Thông tin mức phạt vượt đèn đỏ dành cho ô tô.',
        'source' => [
            'type' => 'official',
            'url' => 'https://example.com/quy-dinh-vuot-den-do',
            'title' => 'Quy định chính thức',
            'domain' => 'example.com',
            'external_id' => 'OFFICIAL-RED-LIGHT-1',
        ],
        'category' => [
            'name' => 'Mức phạt',
            'slug' => 'muc-phat',
        ],
        'tags' => ['Vượt đèn đỏ', 'Ô tô'],
        'sources' => [[
            'title' => 'Nguồn bổ sung',
            'url' => 'https://news.example.com/vuot-den-do',
            'domain' => 'news.example.com',
            'type' => 'news',
        ]],
    ], $overrides);
}

it('protects the n8n content API with enable flag and private key', function (): void {
    config()->set('services.n8n_content.enabled', false);

    $this->getJson('/api/internal/n8n/ping', n8nHeaders())
        ->assertForbidden()
        ->assertJsonPath('success', false);

    config()->set('services.n8n_content.enabled', true);

    $this->getJson('/api/internal/n8n/ping')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);

    $this->getJson('/api/internal/n8n/ping', n8nHeaders('wrong-key'))
        ->assertUnauthorized();

    $this->getJson('/api/internal/n8n/ping', n8nHeaders())
        ->assertOk()
        ->assertExactJson(['success' => true, 'service' => 'content-api']);
});

it('reuses categories and tags by normalized slug', function (): void {
    $firstCategory = $this->postJson('/api/internal/n8n/categories', [
        'name' => 'Phạt nguội',
    ], n8nHeaders())->assertCreated();

    $this->postJson('/api/internal/n8n/categories', [
        'name' => 'PHẠT NGUỘI',
        'slug' => 'phat-nguoi',
    ], n8nHeaders())
        ->assertOk()
        ->assertJsonPath('created', false)
        ->assertJsonPath('data.id', $firstCategory->json('data.id'));

    $firstTag = $this->postJson('/api/internal/n8n/tags', [
        'name' => 'Vượt Đèn Đỏ',
    ], n8nHeaders())->assertCreated();

    $this->postJson('/api/internal/n8n/tags', [
        'name' => 'vuot-den-do',
    ], n8nHeaders())
        ->assertOk()
        ->assertJsonPath('created', false)
        ->assertJsonPath('data.id', $firstTag->json('data.id'));

    expect(SeoCategory::query()->count())->toBe(1)
        ->and(SeoTag::query()->count())->toBe(1);
});

it('creates a pending review article and ignores publish fields from n8n', function (): void {
    $response = $this->postJson('/api/internal/n8n/articles', n8nArticlePayload([
        'status' => 'published',
        'published_at' => now()->toAtomString(),
        'published_by' => 999,
        'reviewed_by' => 999,
    ]), n8nHeaders())
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', SeoPost::STATUS_PENDING_REVIEW)
        ->assertJsonPath('data.created_by_type', SeoPost::CREATOR_N8N);

    $article = SeoPost::query()->findOrFail($response->json('data.id'));

    expect($article->published_at)->toBeNull()
        ->and($article->published_by)->toBeNull()
        ->and($article->reviewed_by)->toBeNull()
        ->and($article->content_hash)->toHaveLength(64)
        ->and($article->category?->slug)->toBe('muc-phat')
        ->and($article->seoTags()->count())->toBe(2)
        ->and($article->sources()->count())->toBe(2)
        ->and(SeoPostActivityLog::query()->where('action', 'created_by_n8n')->exists())->toBeTrue();

    $this->get('/blog/'.$article->slug)->assertNotFound();
});

it('detects duplicate articles and returns the existing article id', function (): void {
    $created = $this->postJson('/api/internal/n8n/articles', n8nArticlePayload(), n8nHeaders())
        ->assertCreated();
    $articleId = $created->json('data.id');

    $this->postJson('/api/internal/n8n/articles/check', [
        'external_id' => 'OFFICIAL-RED-LIGHT-1',
        'slug' => 'another-slug',
    ], n8nHeaders())
        ->assertOk()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('article_id', $articleId);

    $this->postJson('/api/internal/n8n/articles', n8nArticlePayload([
        'title' => 'Bài khác',
        'slug' => 'bai-khac',
    ]), n8nHeaders())
        ->assertConflict()
        ->assertJsonPath('success', false)
        ->assertJsonPath('code', 'DUPLICATE_ARTICLE')
        ->assertJsonPath('data.article_id', $articleId);
});

it('only lets n8n update its own unpublished articles and resubmits rejected content', function (): void {
    $article = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_REJECTED,
        'created_by_type' => SeoPost::CREATOR_N8N,
        'rejection_reason' => 'Cần bổ sung nguồn.',
    ]);

    $this->putJson("/api/internal/n8n/articles/{$article->id}", [
        'title' => 'Bài đã bổ sung nguồn',
        'status' => 'published',
    ], n8nHeaders())
        ->assertOk()
        ->assertJsonPath('data.status', SeoPost::STATUS_PENDING_REVIEW);

    expect($article->fresh()->rejection_reason)->toBeNull();

    $published = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_PUBLISHED,
        'created_by_type' => SeoPost::CREATOR_N8N,
        'published_at' => now(),
    ]);
    $manual = SeoPost::factory()->create(['created_by_type' => SeoPost::CREATOR_ADMIN]);

    $this->putJson("/api/internal/n8n/articles/{$published->id}", ['title' => 'Không được sửa'], n8nHeaders())
        ->assertForbidden()
        ->assertJsonPath('success', false);
    $this->putJson("/api/internal/n8n/articles/{$manual->id}", ['title' => 'Không được sửa'], n8nHeaders())
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

it('returns the standard validation response without storing untrusted data', function (): void {
    $this->postJson('/api/internal/n8n/articles', [
        'status' => 'published',
    ], n8nHeaders())
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validation failed')
        ->assertJsonStructure(['errors' => ['title', 'content']]);

    expect(SeoPost::query()->count())->toBe(0);
});
