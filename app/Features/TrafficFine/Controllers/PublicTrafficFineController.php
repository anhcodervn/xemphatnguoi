<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\InvalidLicensePlateException;
use App\Features\TrafficFine\Exceptions\UnsupportedVehicleTypeException;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\CloudflareTurnstileService;
use App\Features\TrafficFine\Services\TrafficFineLookupService;
use App\Features\TrafficFine\Services\TrafficFineTurnstileSettingsService;
use App\Http\Controllers\Controller;
use App\Models\SeoPost;
use App\Models\User;
use App\Support\EditorContentRenderer;
use App\Support\SettingStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicTrafficFineController extends Controller
{
    public function __construct(
        private readonly EditorContentRenderer $contentRenderer,
        private readonly TrafficFineTurnstileSettingsService $turnstileSettings,
    ) {}

    public function home(Request $request, SettingStore $settingStore): View
    {
        $pageData = $this->publicPageData($request, $settingStore);
        $siteName = ($pageData['systemSettings']['site_name'] ?? null) ?: config('app.name', 'XemPhatNguoi.vn');

        return view('pages.public.home', [
            ...$pageData,
            'pageMetaTitle' => "Tra Cứu Phạt Nguội Ô Tô, Xe Máy Toàn Quốc | {$siteName}",
            'pageMetaDescription' => 'Tra cứu phạt nguội ô tô, xe máy theo biển số. Kiểm tra thông tin vi phạm, thời gian, địa điểm và trạng thái xử lý nhanh chóng, dễ dàng.',
            'pageMetaCanonical' => url('/'),
            'pageMetaUrl' => url('/'),
            'latestPosts' => $this->latestPosts(),
            'lookupMode' => false,
        ]);
    }

    public function lookupPage(Request $request, SettingStore $settingStore): View
    {
        return view('pages.public.home', [
            ...$this->publicPageData($request, $settingStore),
            'pageMetaTitle' => 'Tra cứu phạt nguội theo biển số xe',
            'pageMetaDescription' => 'Nhập biển số và loại phương tiện để kiểm tra dữ liệu phạt nguội hiện có. Không cần đăng nhập.',
            'pageMetaCanonical' => url('/'),
            'pageMetaRobots' => 'noindex,follow',
            'latestPosts' => $this->latestPosts(),
            'lookupMode' => true,
        ]);
    }

    public function result(
        string $plate,
        Request $request,
        SettingStore $settingStore,
        TrafficFineLookupService $lookupService,
        CloudflareTurnstileService $turnstile,
    ): View {
        $vehicleType = $request->string('vehicle_type', VehicleType::Car->value)->toString();
        $lookup = null;
        $errorMessage = null;

        try {
            if (! $turnstile->mayViewResult($request, $plate, $vehicleType)) {
                $errorMessage = 'Vui lòng hoàn tất xác minh bảo mật và tra cứu lại biển số này.';
            } else {
                $lookup = $lookupService->findCachedResult($plate, $vehicleType);

                if ($lookup === null) {
                    $errorMessage = 'Chưa có kết quả gần đây cho biển số này. Vui lòng tra cứu lại.';
                }
            }
        } catch (InvalidLicensePlateException|UnsupportedVehicleTypeException) {
            abort(404);
        }

        $displayPlate = $lookup?->data->displayPlate ?? mb_strtoupper($plate);

        return view('pages.public.result', [
            ...$this->publicPageData($request, $settingStore),
            'pageMetaTitle' => "Tra cứu phạt nguội biển số {$displayPlate}",
            'pageMetaDescription' => "Kết quả tra cứu dữ liệu phạt nguội hiện có cho biển số {$displayPlate}.",
            'pageMetaRobots' => 'noindex,follow',
            'lookup' => $lookup,
            'errorMessage' => $errorMessage,
            'displayPlate' => $displayPlate,
            'vehicleType' => VehicleType::tryFrom($vehicleType) ?? VehicleType::Car,
        ]);
    }

    public function pricing(
        Request $request,
        SettingStore $settingStore,
        ApiLookupBillingService $billingService,
    ): View {
        return view('pages.public.pricing', [
            ...$this->publicPageData($request, $settingStore),
            'pageMetaTitle' => 'Bảng giá API tra cứu phạt nguội theo lượt',
            'pageMetaDescription' => 'API tra cứu phạt nguội tính phí minh bạch theo từng request thành công, không cần mua gói.',
            'apiRequestPrice' => $billingService->pricePerRequest(),
        ]);
    }

    public function partners(
        Request $request,
        SettingStore $settingStore,
        ApiLookupBillingService $billingService,
    ): View {
        return view('pages.public.partners', [
            ...$this->publicPageData($request, $settingStore),
            'pageMetaTitle' => 'Đối tác API tra cứu phạt nguội',
            'pageMetaDescription' => 'Tích hợp API tra cứu phạt nguội cho phần mềm, website và hệ thống doanh nghiệp với chi phí minh bạch theo lượt dùng.',
            'apiRequestPrice' => $billingService->pricePerRequest(),
        ]);
    }

    public function guide(Request $request, SettingStore $settingStore): View
    {
        return view('pages.public.guide', [
            ...$this->publicPageData($request, $settingStore),
            'pageMetaTitle' => 'Hướng dẫn tra cứu phạt nguội',
            'pageMetaDescription' => 'Hướng dẫn nhập biển số, chọn đúng loại phương tiện và đọc kết quả tra cứu phạt nguội.',
        ]);
    }

    public function topic(Request $request, SettingStore $settingStore, string $topic): View
    {
        $content = config("traffic-fine-content.topics.{$topic}");

        abort_unless(is_array($content), 404);

        $pageData = $this->publicPageData($request, $settingStore);
        $siteName = ($pageData['systemSettings']['site_name'] ?? null) ?: config('app.name', 'XemPhatNguoi.vn');

        return view('pages.public.topic', [
            ...$pageData,
            'topic' => $content,
            'pageMetaTitle' => $content['title'].' | '.$siteName,
            'pageMetaDescription' => $content['description'],
        ]);
    }

    public function sitemap(): Response
    {
        $posts = SeoPost::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('updated_at')
            ->get(['slug', 'updated_at']);

        $staticUrls = collect(config('traffic-fine-content.sitemap_route_names', []))
            ->map(fn (string $routeName): string => route($routeName))
            ->all();

        return response()
            ->view('sitemap', ['posts' => $posts, 'staticUrls' => $staticUrls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /dashboard',
            'Disallow: /admin',
            'Disallow: /auth',
            'Disallow: /login',
            'Disallow: /api',
            'Disallow: /tra-cuu/',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]);

        return response($content)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @return array<string, mixed>
     */
    private function publicPageData(Request $request, SettingStore $settingStore): array
    {
        $systemSettings = $settingStore->getMany([
            'site_name' => config('app.name', 'XemPhatNguoi.vn'),
            'site_domain' => '',
            'site_description' => '',
            'support_email' => '',
            'hotline' => '',
            'address' => '',
            'facebook' => '',
            'zalo' => '',
            'youtube' => '',
            'meta_title' => '',
            'meta_description' => '',
            'light_logo' => '',
            'dark_logo' => '',
            'favicon' => '',
            'og_image' => '',
            'gtm_id' => '',
            'meta_pixel_id' => '',
        ]);

        return [
            'systemSettings' => $systemSettings,
            'pageMetaUrl' => $request->url(),
            'pageMetaCanonical' => $request->url(),
            'pageMetaImage' => $systemSettings['og_image'] ?? '',
            'pageMetaRobots' => 'index,follow',
            'vehicleTypes' => collect(VehicleType::cases())
                ->filter(fn (VehicleType $type): bool => $type->isEnabled())
                ->mapWithKeys(fn (VehicleType $type): array => [$type->value => $type->label()])
                ->all(),
            'turnstile' => $this->turnstileSettings->publicConfiguration($request->user() instanceof User ? $request->user() : null),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function latestPosts(): array
    {
        return SeoPost::query()
            ->with('category:id,name,slug')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(function (SeoPost $post): array {
                $content = is_array($post->content) ? $post->content : [];

                return [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt ?: $this->contentRenderer->extractText($content),
                    'thumbnail' => $post->thumbnail ?: $this->contentRenderer->firstImage($content),
                    'category' => $post->category?->name,
                    'published_at' => $post->published_at,
                ];
            })
            ->all();
    }
}
