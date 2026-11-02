<?php

use App\Models\AdSlot;
use App\Models\SeoPost;
use App\Models\SeoPostSource;
use App\Support\SettingStore;

beforeEach(function (): void {
    $this->withoutVite();
});

it('places home advertising after editorial content and outside the lookup result', function (): void {
    AdSlot::factory()->create(['name' => 'lookup_result_bottom', 'enabled' => true, 'code' => '<div data-test-ad>Ad</div>']);

    $response = $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['data-lookup-result', 'how-it-works-title', 'latest-posts-title', 'faq-title', 'data-test-ad', 'site-information-title'], false)
        ->assertDontSee('data-lookup-result-ad', false)
        ->assertSee('không phải website của cơ quan nhà nước');

    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);

    expect($xpath->query('//*[@data-lookup-result]//*[@data-ad-slot]')->length)->toBe(0);
});

it('does not render advertising on the legacy lookup landing or empty blog search', function (string $path): void {
    foreach (['lookup_result_bottom', 'article_top', 'article_middle', 'article_bottom'] as $name) {
        AdSlot::factory()->create(['name' => $name, 'enabled' => true, 'code' => '<div data-test-ad>Ad</div>']);
    }

    $this->get($path)->assertOk()->assertDontSee('data-test-ad', false);
})->with(['/tra-cuu-phat-nguoi', '/blog?q=not-found', '/lien-he', '/chinh-sach-bao-mat']);

it('shows only configured contact details with correction guidance', function (): void {
    app(SettingStore::class)->putMany([
        'support_email' => 'support@example.com',
        'hotline' => '0123456789',
        'address' => 'Địa chỉ đã công bố',
    ]);

    $this->get('/lien-he')->assertOk()
        ->assertSee('href="mailto:support@example.com"', false)
        ->assertSee('0123456789')
        ->assertSee('Địa chỉ đã công bố')
        ->assertSee('Góp ý hoặc yêu cầu sửa thông tin')
        ->assertSee('Không gửi mật khẩu, API key, mã OTP');
});

it('explains advertising cookies and offers external personalization controls', function (): void {
    $this->get('/chinh-sach-bao-mat')->assertOk()
        ->assertSee('Google và cookie quảng cáo')
        ->assertSee('https://myadcenter.google.com/', false)
        ->assertSee('https://policies.google.com/technologies/partner-sites', false);
});

it('keeps unfinished information pages out of search indexing', function (): void {
    app(SettingStore::class)->putMany(['about_page_content' => []]);
    $this->get('/gioi-thieu')->assertOk()
        ->assertSee('<meta name="robots" content="noindex,follow">', false);
});

it('renders article navigation sources and a single advertisement after substantive content', function (): void {
    foreach (['article_top', 'article_middle', 'article_bottom'] as $name) {
        AdSlot::factory()->create(['name' => $name, 'enabled' => true, 'code' => '<div data-test-ad>Ad</div>']);
    }

    $post = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
        'content' => [
            ['type' => 'heading', 'level' => 2, 'children' => [['text' => 'Cách đối chiếu kết quả']]],
            ['type' => 'paragraph', 'children' => [['text' => str_repeat('Hãy đối chiếu dữ liệu với cơ quan có thẩm quyền. ', 50)]]],
        ],
    ]);
    SeoPostSource::factory()->for($post, 'post')->create(['title' => 'Nguồn chính thức', 'url' => 'https://www.csgt.vn/']);
    SeoPostSource::factory()->for($post, 'post')->create(['title' => 'Unsafe source', 'url' => 'javascript:alert(1)']);

    $response = $this->get('/blog/'.$post->slug)->assertOk()
        ->assertSee('Mục lục bài viết')
        ->assertSee('Xuất bản bởi XemPhatNguoi.vn')
        ->assertSeeInOrder(['Cách đối chiếu kết quả', 'Nguồn tham khảo của bài viết', 'Báo thông tin chưa chính xác', 'data-ad-slot="article_bottom"'], false)
        ->assertDontSee('Unsafe source')
        ->assertDontSee('data-ad-slot="article_top"', false)
        ->assertDontSee('data-ad-slot="article_middle"', false);

    expect(substr_count($response->getContent(), 'data-test-ad'))->toBe(1);
});

it('suppresses article ads for short content or noindex pages', function (bool $longContent, string $robots): void {
    AdSlot::factory()->create(['name' => 'article_bottom', 'enabled' => true, 'code' => '<div data-test-ad>Ad</div>']);
    $post = SeoPost::factory()->create([
        'status' => SeoPost::STATUS_PUBLISHED,
        'published_at' => now()->subDay(),
        'robots' => $robots,
        'content' => [['type' => 'paragraph', 'children' => [['text' => str_repeat('Nội dung tham khảo. ', $longContent ? 100 : 1)]]]],
    ]);

    $this->get('/blog/'.$post->slug)->assertOk()->assertDontSee('data-test-ad', false);
})->with([[false, 'index,follow'], [true, 'noindex,follow']]);

it('gives topic pages navigation official sources and a useful next step', function (): void {
    $this->get('/muc-phat/loi-vuot-den-do')->assertOk()
        ->assertSee('id="topic-section-1"', false)
        ->assertSee('href="#topic-section-1"', false)
        ->assertSee('Làm gì sau khi đọc kết quả?')
        ->assertSee('https://www.csgt.vn/', false)
        ->assertSee('https://dichvucong.bocongan.gov.vn/', false)
        ->assertDontSee('data-ad-slot', false);
});
