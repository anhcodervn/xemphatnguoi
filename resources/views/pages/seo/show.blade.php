@extends('layouts.landing')

@php
    $articleSchemaPayload = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post->title,
        'description' => $pageMetaDescription,
        'datePublished' => optional($post->published_at)->toIso8601String(),
        'dateModified' => optional($post->updated_at)->toIso8601String(),
        'mainEntityOfPage' => $pageMetaCanonical,
        'image' => $coverImage,
        'author' => [
            '@type' => 'Organization',
            'name' => $systemSettings['site_name'] ?? config('app.name'),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $systemSettings['site_name'] ?? config('app.name'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $systemSettings['light_logo'] ?: ($systemSettings['dark_logo'] ?: null),
            ],
        ],
    ];

    $breadcrumbSchemaPayload = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Trang chủ',
                'item' => url('/'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Blog & kiến thức',
                'item' => route('seo.index'),
            ],
            $post->category ? [
                '@type' => 'ListItem',
                'position' => 3,
                'name' => $post->category->name,
                'item' => route('seo.index', ['category' => $post->category->slug]),
            ] : null,
            [
                '@type' => 'ListItem',
                'position' => $post->category ? 4 : 3,
                'name' => $post->title,
                'item' => url('/blog/'.$post->slug),
            ],
        ])),
    ];
@endphp

@section('head')
    @if ($post->article_schema)
        <script type="application/ld+json">{!! json_encode($articleSchemaPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif

    @if ($post->breadcrumb_schema)
        <script type="application/ld+json">{!! json_encode($breadcrumbSchemaPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
@endsection

@section('main')
    <section class="bg-slate-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="transition hover:text-slate-900">Trang chủ</a>
                <span>/</span>
                <a href="{{ route('seo.index') }}" class="transition hover:text-slate-900">Blog</a>
                @if ($post->category)
                    <span>/</span>
                    <a href="{{ route('seo.index', ['category' => $post->category->slug]) }}" class="transition hover:text-slate-900">{{ $post->category->name }}</a>
                @endif
            </div>

            <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                <article class="rounded-[10px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="border-b border-slate-100 pb-6">
                        <div class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                            {{ $post->category?->name ?: ($post->focus_keyword ?: 'API Banking') }}
                        </div>

                        <h1 class="font-tech mt-4 max-w-4xl text-3xl font-black tracking-tight text-slate-950 sm:text-5xl">
                            {{ $post->title }}
                        </h1>

                        <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                            {{ $post->excerpt ?: $pageMetaDescription }}
                        </p>

                        <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                            <span>{{ optional($post->published_at)->format('d/m/Y') }}</span>
                            <span>•</span>
                            <span>{{ $readingMinutes }} phút đọc</span>
                            @if ($post->focus_keyword)
                                <span>•</span>
                                <span>{{ $post->focus_keyword }}</span>
                            @endif
                            <span>•</span>
                            <span>Public SEO</span>
                        </div>
                    </div>

                    @if ($coverImage)
                        <div class="mt-8 overflow-hidden rounded-[10px] border border-slate-200 bg-slate-950">
                            <img src="{{ $coverImage }}" alt="{{ $post->cover_alt ?: $post->title }}" class="h-auto w-full object-cover">
                        </div>
                    @endif

                    <div class="article-content mt-8">
                        {!! $contentHtml !!}
                    </div>
                </article>

                <aside class="space-y-5">
                    @if ($headingIndex !== [])
                        <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="font-tech text-lg font-semibold text-slate-950">Mục lục</h3>
                            <div class="mt-4 space-y-2">
                                @foreach ($headingIndex as $heading)
                                    <a
                                        href="#{{ $heading['id'] }}"
                                        class="{{ $heading['level'] > 2 ? 'pl-4' : '' }} block text-sm leading-6 text-slate-600 transition hover:text-sky-700"
                                    >
                                        {{ $heading['text'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-tech text-lg font-semibold text-slate-950">Chia sẻ bài viết</h3>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a
                                href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($pageMetaCanonical) }}"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <i class='bx bxl-facebook text-lg'></i>
                            </a>
                            <a
                                href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($pageMetaCanonical) }}"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <i class='bx bxl-linkedin text-lg'></i>
                            </a>
                            <a
                                href="mailto:?subject={{ rawurlencode($post->title) }}&body={{ rawurlencode($pageMetaCanonical) }}"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <i class='bx bx-envelope text-lg'></i>
                            </a>
                            <a
                                href="{{ $pageMetaCanonical }}"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
                            >
                                <i class='bx bx-link text-lg'></i>
                            </a>
                        </div>
                    </div>

                    <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-mono-tech text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">Danh mục</h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($sidebarCategories as $category)
                                <a
                                    href="{{ route('seo.index', ['category' => $category->slug]) }}"
                                    class="{{ $post->category?->slug === $category->slug ? 'text-sky-700' : 'text-slate-700 hover:text-sky-700' }} flex items-center justify-between gap-4 text-sm transition"
                                >
                                    <span>{{ $category->name }}</span>
                                    <span class="text-sky-600">{{ $category->posts_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if ($relatedPosts->isNotEmpty())
                        <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="font-tech text-lg font-semibold text-slate-950">Bài viết liên quan</h3>
                            <div class="mt-4 space-y-4">
                                @foreach ($relatedPosts as $related)
                                    <article class="flex gap-3">
                                        <a href="{{ $related['url'] }}" class="block h-20 w-24 shrink-0 overflow-hidden rounded-[12px] bg-slate-950">
                                            @if ($related['cover_image'])
                                                <img src="{{ $related['cover_image'] }}" alt="{{ $related['title'] }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="h-full w-full bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.25),transparent_36%),linear-gradient(135deg,#020617,#0f172a,#1d4ed8)]"></div>
                                            @endif
                                        </a>
                                        <div class="min-w-0">
                                            <h4 class="line-clamp-2 text-sm font-semibold leading-6 text-slate-900">
                                                <a href="{{ $related['url'] }}" class="transition hover:text-sky-700">{{ $related['title'] }}</a>
                                            </h4>
                                            <p class="mt-1 text-xs text-slate-500">{{ $related['published_label'] }}</p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
