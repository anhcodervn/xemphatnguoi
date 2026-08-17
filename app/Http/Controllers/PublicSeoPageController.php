<?php

namespace App\Http\Controllers;

use App\Models\SeoCategory;
use App\Models\SeoPost;
use App\Support\EditorContentRenderer;
use App\Support\SettingStore;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PublicSeoPageController extends Controller
{
    public function __construct(
        protected EditorContentRenderer $contentRenderer,
    ) {}

    public function index(Request $request, SettingStore $settingStore): View
    {
        $search = trim($request->string('q')->toString());
        $categorySlug = trim($request->string('category')->toString());

        $baseQuery = SeoPost::query()
            ->with(['category:id,name,slug'])
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%")
                        ->orWhere('focus_keyword', 'like', "%{$search}%");
                });
            })
            ->when($categorySlug !== '', function (Builder $builder) use ($categorySlug): void {
                $builder->whereHas('category', function (Builder $query) use ($categorySlug): void {
                    $query
                        ->where('slug', $categorySlug)
                        ->where('is_active', true);
                });
            });

        $posts = (clone $baseQuery)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $featuredPost = $posts->first();
        $latestPosts = $posts->skip($featuredPost ? 1 : 0)->take(6)->values();
        $sidebarPosts = $posts->take(3)->values();

        $categories = SeoCategory::query()
            ->where('is_active', true)
            ->withCount([
                'posts' => fn (Builder $query) => $query
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $systemSettings = $this->systemSettings($settingStore);
        $pageTitle = 'Blog phạt nguội và kiến thức giao thông';
        $pageDescription = 'Hướng dẫn tra cứu phạt nguội, đọc kết quả, quản lý phương tiện và cập nhật kiến thức giao thông hữu ích.';

        return view('pages.seo.index', [
            'systemSettings' => $systemSettings,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageMetaTitle' => $search !== ''
                ? "Tìm kiếm: {$search} | {$pageTitle}"
                : $pageTitle.' | '.($systemSettings['site_name'] ?: config('app.name', 'XemPhatNguoi.vn')),
            'pageMetaDescription' => $pageDescription,
            'pageMetaRobots' => $search !== '' ? 'noindex,follow' : 'index,follow',
            'pageMetaUrl' => $request->url().($request->getQueryString() ? '?'.$request->getQueryString() : ''),
            'featuredPost' => $featuredPost ? $this->transformPost($featuredPost) : null,
            'latestPosts' => $latestPosts->map(fn (SeoPost $post) => $this->transformPost($post)),
            'sidebarPosts' => $sidebarPosts->map(fn (SeoPost $post) => $this->transformPost($post)),
            'categories' => $categories,
            'activeCategorySlug' => $categorySlug,
            'search' => $search,
            'popularTags' => $posts
                ->flatMap(fn (SeoPost $post): array => [
                    ...($post->tags ?? []),
                    $post->focus_keyword,
                ])
                ->filter()
                ->map(fn (string $tag) => trim($tag))
                ->unique()
                ->take(8)
                ->values(),
        ]);
    }

    public function show(string $slug, Request $request, SettingStore $settingStore): View
    {
        $post = SeoPost::query()
            ->with('category:id,name,slug')
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $relatedPosts = SeoPost::query()
            ->with('category:id,name,slug')
            ->where('id', '!=', $post->id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($post->seo_category_id, fn (Builder $builder) => $builder->where('seo_category_id', $post->seo_category_id))
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        $sidebarCategories = SeoCategory::query()
            ->where('is_active', true)
            ->withCount([
                'posts' => fn (Builder $query) => $query
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $systemSettings = $this->systemSettings($settingStore);
        $content = is_array($post->content) ? $post->content : [];
        $contentHtml = $this->contentRenderer->renderNodes($content);
        $coverImage = $post->thumbnail ?: $this->contentRenderer->firstImage($content);
        $headingIndex = $this->contentRenderer->headingIndex($content);
        $faq = is_array($post->faq) ? $post->faq : [];
        $validFaq = collect($faq)
            ->filter(fn (mixed $item): bool => is_array($item) && filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->values();
        $structuredData = [];

        if ($post->article_schema) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->title,
                'description' => $post->seo_description ?: $post->excerpt,
                'datePublished' => $post->published_at?->toAtomString(),
                'dateModified' => $post->updated_at?->toAtomString(),
                'image' => $post->og_image ?: $coverImage,
                'author' => [
                    '@type' => 'Organization',
                    'name' => $systemSettings['site_name'] ?: config('app.name', 'XemPhatNguoi.vn'),
                ],
            ];
        }

        if ($post->breadcrumb_schema) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => url('/blog')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => $request->url()],
                ],
            ];
        }

        if ($validFaq->isNotEmpty()) {
            $structuredData[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $validFaq
                    ->map(fn (array $item): array => [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']],
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return view('pages.seo.show', [
            'systemSettings' => $systemSettings,
            'post' => $post,
            'contentHtml' => $contentHtml,
            'coverImage' => $coverImage,
            'readingMinutes' => $this->contentRenderer->estimateReadingMinutes($content),
            'headingIndex' => $headingIndex,
            'faq' => $faq,
            'relatedPosts' => $relatedPosts->map(fn (SeoPost $item) => $this->transformPost($item)),
            'sidebarCategories' => $sidebarCategories,
            'pageMetaTitle' => $post->seo_title ?: $post->title.' | '.($systemSettings['site_name'] ?: config('app.name', 'XemPhatNguoi.vn')),
            'pageMetaDescription' => $post->seo_description ?: ($post->excerpt ?: $this->contentRenderer->extractText($content)),
            'pageMetaCanonical' => $post->canonical_url ?: $request->url(),
            'pageMetaUrl' => $request->url(),
            'pageMetaImage' => $post->og_image ?: $coverImage,
            'pageMetaRobots' => $post->robots,
            'structuredData' => $structuredData,
        ]);
    }

    protected function systemSettings(SettingStore $settingStore): array
    {
        return $settingStore->getMany([
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
            'custom_header' => config('system_settings.defaults.seo.custom_header', ''),
            'custom_script' => '',
        ]);
    }

    protected function transformPost(SeoPost $post): array
    {
        $content = is_array($post->content) ? $post->content : [];
        $publishedAt = $post->published_at instanceof Carbon ? $post->published_at : null;

        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt ?: $this->contentRenderer->extractText($content),
            'thumbnail' => $post->thumbnail ?: $this->contentRenderer->firstImage($content),
            'focus_keyword' => $post->focus_keyword,
            'category' => $post->category?->name,
            'category_slug' => $post->category?->slug,
            'published_at' => $publishedAt,
            'published_label' => $publishedAt?->format('d/m/Y'),
            'reading_minutes' => $this->contentRenderer->estimateReadingMinutes($content),
            'url' => url('/blog/'.$post->slug),
        ];
    }
}
