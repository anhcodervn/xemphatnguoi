<?php

use App\Models\SeoPost;
use App\Models\TrafficFineResult;
use App\Models\User;
use App\Support\SettingStore;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders the public lookup website with Blade and no Vue application bundle', function (): void {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('Tra cứu phạt nguội toàn quốc')
        ->assertSee('data-lookup-form', false)
        ->assertSee('data-mobile-priority-lookup', false)
        ->assertSee('name="vehicle_type"', false)
        ->assertSee('Kết quả tra cứu')
        ->assertSee('Tra cứu phạt nguội là gì?')
        ->assertSee('Chọn loại phương tiện bạn muốn tra cứu')
        ->assertSee('Chỉ 3 bước để tra cứu phạt nguội')
        ->assertSee('Giải đáp thắc mắc về tra cứu phạt nguội')
        ->assertSee('>API</a>', false)
        ->assertSee('href="'.route('partners.api').'"', false)
        ->assertDontSee('id="app"', false);

    expect(substr_count($response->getContent(), '<h1'))->toBe(1)
        ->and(substr_count($response->getContent(), 'data-home-accent-icon'))->toBeGreaterThanOrEqual(20);
});

it('renders administrator custom head and script content exactly once in public and spa layouts', function (): void {
    $customHeader = '<meta name="google-site-verification" content="xpn-verification-token">';
    $customScript = '<script data-custom-script>window.__xpnCustomLoaded=true;</script>';
    $admin = User::factory()->create(['role' => 'admin']);
    config()->set(
        'system_settings.defaults.seo.custom_header',
        '<meta name="google-site-verification" content="fallback-token">',
    );

    Sanctum::actingAs($admin);
    $this->patchJson('/api/admin-api/settings/seo', [
        'custom_header' => $customHeader,
        'custom_script' => $customScript,
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.custom_header', $customHeader)
        ->assertJsonPath('data.settings.custom_script', $customScript);

    foreach (['/', '/gioi-thieu', '/blog'] as $path) {
        $response = $this->get($path)->assertOk();
        $html = $response->getContent();

        expect(substr_count($html, $customHeader))->toBe(1)
            ->and(Str::before($html, '</head>'))->toContain($customHeader)
            ->and(Str::after($html, '</head>'))->not->toContain($customHeader)
            ->and(substr_count($html, $customScript))->toBe(1);
    }

    $dashboard = $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk();
    $dashboardHtml = $dashboard->getContent();

    expect(substr_count($dashboardHtml, $customHeader))->toBe(1)
        ->and(Str::before($dashboardHtml, '</head>'))->toContain($customHeader)
        ->and(Str::after($dashboardHtml, '</head>'))->not->toContain($customHeader)
        ->and(substr_count($dashboardHtml, $customScript))->toBe(1);
});

it('uses the configured custom header when no database override exists', function (): void {
    $customHeader = '<meta name="google-site-verification" content="configured-token">';
    config()->set('system_settings.defaults.seo.custom_header', $customHeader);

    $response = $this->get('/')->assertOk();
    $html = $response->getContent();

    expect(substr_count($html, $customHeader))->toBe(1)
        ->and(Str::before($html, '</head>'))->toContain($customHeader);
});

it('defines an empty custom header configuration default', function (): void {
    expect(config('system_settings.defaults.seo.custom_header'))->toBe('');
});

it('drops unsafe custom header markup supplied through configuration', function (): void {
    $unsafeHeader = '<script data-unsafe-config>alert(1)</script>';
    config()->set('system_settings.defaults.seo.custom_header', $unsafeHeader);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('data-unsafe-config', false);
});

it('limits custom header payload size', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/seo', [
        'custom_header' => str_repeat('x', 10001),
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['data' => ['errors' => ['custom_header']]]);
});

it('rejects unsafe or application-owned custom header markup', function (string $customHeader): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/seo', [
        'custom_header' => $customHeader,
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['data' => ['errors' => ['custom_header']]]);
})->with([
    'script' => '<script>alert(1)</script>',
    'stylesheet' => '<link rel="stylesheet" href="https://example.com/style.css">',
    'http redirect' => '<meta http-equiv="refresh" content="0;url=https://example.com">',
    'event attribute' => '<meta name="google-site-verification" content="token" onload="alert(1)">',
    'system robots' => '<meta name="robots" content="noindex">',
    'referrer policy' => '<meta name="referrer" content="unsafe-url">',
]);

it('escapes custom meta values before rendering them in the document head', function (): void {
    $customHeader = '<meta name="google-site-verification" content="token &quot; &lt;unsafe&gt;">';
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/seo', [
        'custom_header' => $customHeader,
    ])->assertOk();

    $response = $this->get('/')->assertOk();
    $html = $response->getContent();

    expect(Str::before($html, '</head>'))->toContain($customHeader)
        ->and($html)->not->toContain('<unsafe>');
});

it('preserves custom header when a legacy system update omits the field', function (): void {
    $customHeader = '<meta name="google-site-verification" content="persistent-token">';
    $settingStore = app(SettingStore::class);
    $settingStore->putString('custom_header', $customHeader);
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/system', [
        'site_name' => 'XemPhatNguoi.vn',
        'site_active' => true,
        'allow_register' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.custom_header', $customHeader);

    expect($settingStore->getString('custom_header'))->toBe($customHeader);
});

it('introduces the partner api publicly before requiring login for documentation', function (): void {
    app(SettingStore::class)->putString('traffic_fine_api_request_price', '35');

    $this->get('/doi-tac')
        ->assertOk()
        ->assertSee('API dành cho đối tác')
        ->assertSee('35đ')
        ->assertSee('Xem tài liệu và thuê API')
        ->assertSee('href="'.route('dashboard', ['any' => 'api']).'"', false)
        ->assertSee('<link rel="canonical" href="'.route('partners.api').'">', false)
        ->assertDontSee('id="app"', false)
        ->assertDontSee('api.xephatnguoi.com');

    $this->get('/dashboard/api')
        ->assertRedirect('/login')
        ->assertSessionHas('url.intended', url('/dashboard/api'));

    $this->actingAs(User::factory()->create())
        ->get('/dashboard/api')
        ->assertOk();
});

it('renders canonical metadata and crawlable topic links on the home page', function (): void {
    $this->get('/?utm_source=campaign')
        ->assertOk()
        ->assertSee('<title>Tra Cứu Phạt Nguội Ô Tô, Xe Máy Toàn Quốc | '.config('app.name').'</title>', false)
        ->assertSee('<meta name="description"', false)
        ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:image"', false)
        ->assertSee('href="'.url('/tra-cuu-phat-nguoi-o-to').'"', false)
        ->assertSee('href="'.url('/tra-cuu-phat-nguoi-xe-may').'"', false)
        ->assertSee('href="'.url('/tra-cuu-phat-nguoi-xe-may-dien').'"', false)
        ->assertSee('href="'.url('/muc-phat/loi-vuot-den-do').'"', false)
        ->assertSee('href="'.url('/muc-phat/loi-qua-toc-do').'"', false)
        ->assertSee('href="'.url('/muc-phat/loi-sai-lan').'"', false)
        ->assertSee('href="'.url('/phat-nguoi-la-gi').'"', false)
        ->assertSee('href="'.url('/huong-dan-tra-cuu-phat-nguoi').'"', false)
        ->assertSee('<details', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertDontSee('utm_source=campaign');
});

it('keeps the public home compact, centered and vertical without a sidebar', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('site-container', false)
        ->assertSee('data-home-layout="vertical-centered"', false)
        ->assertSee('max-w-[720px]', false)
        ->assertSee('h-12 w-full', false)
        ->assertSee('h-[58px]', false)
        ->assertDontSee('lg:grid-cols-[minmax(0,780px)_320px]', false)
        ->assertDontSee('max-w-[1180px]', false)
        ->assertDontSee('max-w-[820px]', false)
        ->assertDontSee('h-[52px]', false)
        ->assertDontSee('min-h-[205px]', false)
        ->assertDontSee('<aside', false)
        ->assertDontSee('lg:min-h-screen', false)
        ->assertDontSee('text-6xl', false);
});

it('serves the public traffic fine content cluster', function (string $url, string $heading): void {
    $this->get($url)
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('<link rel="canonical" href="'.url($url).'">', false);
})->with([
    'car lookup' => ['/tra-cuu-phat-nguoi-o-to', 'Tra cứu phạt nguội ô tô'],
    'motorbike lookup' => ['/tra-cuu-phat-nguoi-xe-may', 'Tra cứu phạt nguội xe máy'],
    'electric motorbike lookup' => ['/tra-cuu-phat-nguoi-xe-may-dien', 'Tra cứu phạt nguội xe máy điện'],
    'penalty overview' => ['/muc-phat', 'Mức phạt giao thông'],
    'red light penalty' => ['/muc-phat/loi-vuot-den-do', 'Lỗi vượt đèn đỏ'],
    'speeding penalty' => ['/muc-phat/loi-qua-toc-do', 'Lỗi chạy quá tốc độ'],
    'wrong lane penalty' => ['/muc-phat/loi-sai-lan', 'Lỗi đi sai làn đường'],
    'traffic fine explainer' => ['/phat-nguoi-la-gi', 'Phạt nguội là gì?'],
    'lookup guide' => ['/huong-dan-tra-cuu-phat-nguoi', 'Hướng dẫn tra cứu phạt nguội'],
]);

it('keeps the legacy guide url as a permanent redirect', function (): void {
    $this->get('/huong-dan')
        ->assertMovedPermanently()
        ->assertRedirectToRoute('traffic-fines.knowledge.guide');
});

it('presents api pay per request pricing without service packages', function (): void {
    app(SettingStore::class)->putString('traffic_fine_api_request_price', '35');

    $this->get('/bang-gia')
        ->assertOk()
        ->assertSee('35đ')
        ->assertSee('Không cần mua gói')
        ->assertSee('request thành công')
        ->assertSee('href="'.route('dashboard', ['any' => 'api']).'"', false)
        ->assertDontSee('BASIC')
        ->assertDontSee('PRO')
        ->assertDontSee('/dashboard/packages', false);
});

it('canonicalizes the legacy lookup landing page to the home pillar page', function (): void {
    $this->get('/tra-cuu-phat-nguoi')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
        ->assertSee('<meta name="robots" content="noindex,follow">', false);
});

it('marks plate result pages as noindex follow', function (): void {
    $checkedAt = now();
    TrafficFineResult::factory()->create([
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'status' => 'success',
        'violation_count' => 1,
        'provider' => 'xephatnguoi',
        'response_json' => [
            'plate' => '30A12345',
            'display_plate' => '30A-123.45',
            'vehicle_type' => 'car',
            'status' => 'success',
            'violation_count' => 1,
            'violations' => [[
                'plate_color' => 'Nền trắng',
                'time' => '2026-08-17 09:04:00',
                'location' => 'Quốc lộ 1, Bắc Ninh',
                'behavior' => 'Điều khiển xe chạy quá tốc độ quy định',
                'status' => 'Chưa xử phạt',
                'agency' => 'Phòng Cảnh sát giao thông',
                'resolution_agency' => 'Đội CSGT số 2',
                'resolution_address' => 'Số 8A Xuân La, Hà Nội',
                'resolution_phone' => null,
            ]],
            'checked_at' => $checkedAt->toISOString(),
        ],
        'checked_at' => $checkedAt,
        'expires_at' => now()->addDay(),
    ]);

    $this->get('/tra-cuu/30A12345?vehicle_type=car')
        ->assertOk()
        ->assertSee('Tra cứu phạt nguội biển số 30A-123.45')
        ->assertSee('data-result-tone="violation"', false)
        ->assertSee('Tra cứu vi phạm')
        ->assertSee('Cập nhật:')
        ->assertSee('Đã xử phạt')
        ->assertSee('Chưa xử phạt')
        ->assertSee('href="#danh-sach-loi"', false)
        ->assertSee('Danh sách vi phạm')
        ->assertSee('Nội dung vi phạm')
        ->assertSee('Xem mức phạt')
        ->assertSee('href="'.route('traffic-fines.penalties.index').'"', false)
        ->assertSee('Nơi giải quyết')
        ->assertDontSee('Tự động thông báo')
        ->assertDontSee('Trang này được đặt noindex')
        ->assertDontSee('2026-08-17 09:04:00')
        ->assertSee('<meta name="robots" content="noindex,follow">', false);
});

it('presents a calm and honest empty state when no violation is recorded', function (): void {
    $checkedAt = now();
    TrafficFineResult::factory()->create([
        'plate' => '30A67890',
        'vehicle_type' => 'car',
        'status' => 'no_violation',
        'violation_count' => 0,
        'provider' => 'xephatnguoi',
        'response_json' => [
            'plate' => '30A67890',
            'display_plate' => '30A-678.90',
            'vehicle_type' => 'car',
            'status' => 'no_violation',
            'violation_count' => 0,
            'violations' => [],
            'checked_at' => $checkedAt->toISOString(),
        ],
        'checked_at' => $checkedAt,
        'expires_at' => now()->addDay(),
    ]);

    $this->get('/tra-cuu/30A67890?vehicle_type=car')
        ->assertOk()
        ->assertSee('data-result-tone="clear"', false)
        ->assertSee('Chưa ghi nhận vi phạm')
        ->assertSee('Chưa có thông tin vi phạm')
        ->assertSee('Đã xử phạt')
        ->assertSee('Chưa xử phạt')
        ->assertDontSee('Kết quả an toàn')
        ->assertDontSee('Tra cứu vi phạm')
        ->assertDontSee('Danh sách vi phạm')
        ->assertDontSee('Tự động thông báo');
});

it('includes only published blog posts and public static pages in the sitemap', function (): void {
    SeoPost::query()->create([
        'title' => 'Bài đã xuất bản',
        'slug' => 'bai-da-xuat-ban',
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);
    SeoPost::query()->create([
        'title' => 'Bài nháp',
        'slug' => 'bai-nhap',
        'status' => 'draft',
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('/tra-cuu-phat-nguoi-o-to')
        ->assertSee('/tra-cuu-phat-nguoi-xe-may')
        ->assertSee('/tra-cuu-phat-nguoi-xe-may-dien')
        ->assertSee('/muc-phat/loi-vuot-den-do')
        ->assertSee('/phat-nguoi-la-gi')
        ->assertSee('/huong-dan-tra-cuu-phat-nguoi')
        ->assertSee('/bang-gia')
        ->assertSee('/doi-tac')
        ->assertSee('/blog/bai-da-xuat-ban')
        ->assertDontSee('/blog/bai-nhap')
        ->assertDontSee('<loc>'.url('/huong-dan').'</loc>', false)
        ->assertDontSee('/dashboard')
        ->assertDontSee('/admin')
        ->assertDontSee('/tra-cuu/30A12345');
});

it('keeps public assets and indexable content crawlable in robots rules', function (): void {
    $response = $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('Cache-Control', 'no-cache, public')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertSee('Allow: /')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /dashboard')
        ->assertSee('Disallow: /login')
        ->assertSee('Disallow: /api')
        ->assertSee('Sitemap: '.route('sitemap'))
        ->assertDontSee('Disallow: /build')
        ->assertDontSee('Disallow: /blog');

    expect(substr_count($response->getContent(), 'Sitemap:'))->toBe(1)
        ->and(public_path('robots.txt'))->not->toBeFile()
        ->and(public_path('ads.txt'))->not->toBeFile();
});

it('serves the configured ads file with plain text security headers by default', function (): void {
    $this->get('/ads.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('Cache-Control', 'no-cache, public')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertContent("google.com, pub-4352299256001618, DIRECT, f08c47fec0942fa0\n");
});

it('uses the default meta robots setting on public pages', function (): void {
    app(SettingStore::class)->putString('robots', 'noindex,nofollow');

    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
});

it('publishes administrator robots and ads text settings at the site root', function (): void {
    $robots = "User-agent: *\r\nDisallow: /private\r\nSitemap: https://wrong.example/sitemap.xml";
    $ads = "google.com, pub-123456789, DIRECT, f08c47fec0942fa0\r\nexample.com, seller-1, RESELLER";
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/seo', [
        'robots_txt' => $robots,
        'ads_txt' => $ads,
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.robots_txt', $robots)
        ->assertJsonPath('data.settings.ads_txt', $ads);

    $robotsResponse = $this->get('/robots.txt')
        ->assertOk()
        ->assertContent("User-agent: *\nDisallow: /private\nSitemap: ".route('sitemap')."\n")
        ->assertDontSee('wrong.example');

    expect(substr_count($robotsResponse->getContent(), 'Sitemap:'))->toBe(1);

    $this->get('/ads.txt')
        ->assertOk()
        ->assertContent("google.com, pub-123456789, DIRECT, f08c47fec0942fa0\nexample.com, seller-1, RESELLER\n");
});

it('preserves crawler file settings when a partial seo update omits them', function (): void {
    $settingStore = app(SettingStore::class);
    $settingStore->putString('robots_txt', 'User-agent: *');
    $settingStore->putString('ads_txt', 'google.com, pub-123, DIRECT');
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/seo', [
        'meta_title' => 'Tiêu đề mới',
    ])
        ->assertOk()
        ->assertJsonPath('data.settings.robots_txt', 'User-agent: *')
        ->assertJsonPath('data.settings.ads_txt', 'google.com, pub-123, DIRECT');

    expect($settingStore->getString('robots_txt'))->toBe('User-agent: *')
        ->and($settingStore->getString('ads_txt'))->toBe('google.com, pub-123, DIRECT');
});

it('rejects invalid crawler file settings', function (string $field, string $content): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/seo', [
        $field => $content,
    ])
        ->assertUnprocessable()
        ->assertJsonStructure(['data' => ['errors' => [$field]]]);
})->with([
    'oversized robots' => ['robots_txt', str_repeat('x', 20001)],
    'oversized ads' => ['ads_txt', str_repeat('x', 20001)],
    'robots control character' => ['robots_txt', "User-agent: *\0Disallow: /"],
    'ads control character' => ['ads_txt', "google.com\0, pub-123, DIRECT"],
]);

it('protects crawler file settings from non-admin updates', function (): void {
    $payload = ['ads_txt' => 'google.com, pub-123, DIRECT'];

    $this->patchJson('/api/admin-api/settings/seo', $payload)->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create(['role' => 'user']));
    $this->patchJson('/api/admin-api/settings/seo', $payload)->assertForbidden();
});

it('reports the actual public sitemap file and url count to admins', function (): void {
    SeoPost::query()->create([
        'title' => 'Bài đã xuất bản',
        'slug' => 'bai-da-xuat-ban',
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/seo/overview')
        ->assertOk()
        ->assertJsonPath('data.summary.sitemap_files', 1);

    $this->getJson('/api/admin-api/seo/sitemaps')
        ->assertOk()
        ->assertJsonCount(1, 'data.entries')
        ->assertJsonPath('data.entries.0.path', '/sitemap.xml')
        ->assertJsonPath('data.entries.0.included_count', '17 URL');
});

it('renders published blog content safely with valid article and faq schema', function (): void {
    SeoPost::query()->create([
        'title' => 'Cách đọc kết quả phạt nguội',
        'slug' => 'cach-doc-ket-qua-phat-nguoi',
        'excerpt' => 'Hướng dẫn đọc kết quả.',
        'content' => [[
            'type' => 'paragraph',
            'children' => [['text' => '<script>alert("xss")</script>']],
        ]],
        'faq' => [[
            'question' => 'Kết quả có phải xác nhận chính thức?',
            'answer' => 'Không, cần đối chiếu với cơ quan có thẩm quyền.',
        ]],
        'article_schema' => true,
        'breadcrumb_schema' => true,
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    $this->get('/blog/cach-doc-ket-qua-phat-nguoi')
        ->assertOk()
        ->assertSee('Cách đọc kết quả phạt nguội')
        ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"@type":"BreadcrumbList"', false)
        ->assertSee('"@type":"FAQPage"', false);
});

it('redirects guests away from dashboard and admin', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/admin')->assertRedirect('/login');
});

it('redirects the legacy dashboard lookup page to the Blade lookup page', function (): void {
    $this->get('/dashboard/lookup')
        ->assertRedirectToRoute('traffic-fines.lookup-page');

    $this->actingAs(User::factory()->create())
        ->get('/dashboard/lookup')
        ->assertRedirectToRoute('traffic-fines.lookup-page');
});
