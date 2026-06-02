@extends('layouts.landing')

@section('main')
    <section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="transition hover:text-slate-900">Trang chủ</a>
                <span>/</span>
                <span class="text-slate-700">Blog & kiến thức</span>
            </div>

            <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div>
                    <p class="font-mono-tech text-sm uppercase tracking-[0.24em] text-sky-600">Trung tâm kiến thức</p>
                    <h1 class="font-tech mt-4 text-4xl font-black tracking-tight text-slate-950 sm:text-5xl">
                        {{ $pageTitle }}
                    </h1>
                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600 sm:text-lg">
                        {{ $pageDescription }}
                    </p>

                    @if ($featuredPost)
                        <article class="mt-8 overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
                            <div class="grid gap-0 lg:grid-cols-[340px_minmax(0,1fr)]">
                                <a href="{{ $featuredPost['url'] }}" class="block bg-slate-950">
                                    @if ($featuredPost['cover_image'])
                                        <img src="{{ $featuredPost['cover_image'] }}" alt="{{ $featuredPost['title'] }}" class="h-full min-h-[260px] w-full object-cover">
                                    @else
                                        <div class="flex h-full min-h-[260px] items-center justify-center bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.25),transparent_36%),linear-gradient(135deg,#020617,#0f172a,#1d4ed8)] p-8 text-center text-sm text-slate-300">
                                            Bài viết nổi bật
                                        </div>
                                    @endif
                                </a>

                                <div class="flex flex-col justify-between p-6 sm:p-7">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-sky-600">
                                            <span class="rounded-full bg-sky-50 px-3 py-1 text-[11px] tracking-[0.22em] text-sky-700">Nổi bật</span>
                                            @if ($featuredPost['category_name'])
                                                <span>{{ $featuredPost['category_name'] }}</span>
                                            @elseif ($featuredPost['focus_keyword'])
                                                <span>{{ $featuredPost['focus_keyword'] }}</span>
                                            @endif
                                        </div>

                                        <h2 class="font-tech mt-4 text-2xl font-bold tracking-tight text-slate-950 sm:text-[2rem]">
                                            <a href="{{ $featuredPost['url'] }}" class="transition hover:text-sky-700">
                                                {{ $featuredPost['title'] }}
                                            </a>
                                        </h2>

                                        <p class="mt-4 text-base leading-8 text-slate-600">
                                            {{ $featuredPost['excerpt'] }}
                                        </p>
                                    </div>

                                    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="text-sm text-slate-500">
                                            <span>{{ $featuredPost['published_label'] }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ $featuredPost['reading_minutes'] }} phút đọc</span>
                                        </div>

                                        <a href="{{ $featuredPost['url'] }}" class="inline-flex items-center justify-center rounded-full border border-sky-200 px-5 py-2.5 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-50">
                                            Đọc tiếp
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endif

                    <div class="mt-10">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="font-tech text-2xl font-bold tracking-tight text-slate-950">Bài viết mới nhất</h2>
                            @if ($activeCategorySlug || $search)
                                <a href="{{ route('seo.index') }}" class="text-sm font-semibold text-sky-700 transition hover:text-sky-800">
                                    Xóa bộ lọc
                                </a>
                            @endif
                        </div>

                        @if ($latestPosts->isEmpty() && ! $featuredPost)
                        <div class="mt-6 rounded-[10px] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-sm text-slate-500">
                                Chưa có bài viết SEO nào được xuất bản.
                            </div>
                        @else
                            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($latestPosts as $post)
                                    <article class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                                        <a href="{{ $post['url'] }}" class="block bg-slate-950">
                                            @if ($post['cover_image'])
                                                <img src="{{ $post['cover_image'] }}" alt="{{ $post['title'] }}" class="h-52 w-full object-cover">
                                            @else
                                                <div class="flex h-52 items-center justify-center bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.25),transparent_36%),linear-gradient(135deg,#020617,#0f172a,#1d4ed8)] p-6 text-center text-sm text-slate-300">
                                                    {{ $post['category_name'] ?: 'SEO Post' }}
                                                </div>
                                            @endif
                                        </a>

                                        <div class="p-5">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-600">
                                                {{ $post['category_name'] ?: ($post['focus_keyword'] ?: 'API Banking') }}
                                            </p>
                                            <h3 class="font-tech mt-3 text-xl font-bold tracking-tight text-slate-950">
                                                <a href="{{ $post['url'] }}" class="transition hover:text-sky-700">
                                                    {{ $post['title'] }}
                                                </a>
                                            </h3>
                                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $post['excerpt'] }}</p>
                                            <div class="mt-4 text-sm text-slate-500">
                                                <span>{{ $post['published_label'] }}</span>
                                                <span class="mx-2">•</span>
                                                <span>{{ $post['reading_minutes'] }} phút đọc</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <aside class="space-y-5">
                    <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                        <form method="GET" action="{{ route('seo.index') }}" class="relative">
                            @if ($activeCategorySlug)
                                <input type="hidden" name="category" value="{{ $activeCategorySlug }}">
                            @endif
                            <input
                                type="text"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Tìm kiếm bài viết..."
                                class="w-full rounded-[12px] border border-slate-200 bg-slate-50 px-4 py-3 pr-11 text-sm outline-none transition focus:border-sky-300 focus:bg-white"
                            >
                            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-sky-700">
                                <i class='bx bx-search text-xl'></i>
                            </button>
                        </form>
                    </div>

                    <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-mono-tech text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">Danh mục</h3>
                        <div class="mt-4 space-y-3">
                            @foreach ($categories as $category)
                                <a
                                    href="{{ route('seo.index', array_filter(['category' => $category->slug, 'q' => $search ?: null])) }}"
                                    class="{{ $activeCategorySlug === $category->slug ? 'text-sky-700' : 'text-slate-700 hover:text-sky-700' }} flex items-center justify-between gap-4 text-sm transition"
                                >
                                    <span>{{ $category->name }}</span>
                                    <span class="text-sky-600">{{ $category->posts_count }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if ($sidebarPosts->isNotEmpty())
                        <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="font-mono-tech text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">Bài viết nổi bật</h3>
                            <div class="mt-4 space-y-4">
                                @foreach ($sidebarPosts as $post)
                                    <article class="flex gap-3">
                                        <a href="{{ $post['url'] }}" class="block h-20 w-24 shrink-0 overflow-hidden rounded-[12px] bg-slate-950">
                                            @if ($post['cover_image'])
                                                <img src="{{ $post['cover_image'] }}" alt="{{ $post['title'] }}" class="h-full w-full object-cover">
                                            @else
                                                <div class="h-full w-full bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.25),transparent_36%),linear-gradient(135deg,#020617,#0f172a,#1d4ed8)]"></div>
                                            @endif
                                        </a>
                                        <div class="min-w-0">
                                            <h4 class="line-clamp-2 text-sm font-semibold leading-6 text-slate-900">
                                                <a href="{{ $post['url'] }}" class="transition hover:text-sky-700">{{ $post['title'] }}</a>
                                            </h4>
                                            <p class="mt-1 text-xs text-slate-500">{{ $post['published_label'] }}</p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($popularTags->isNotEmpty())
                        <div class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="font-mono-tech text-sm font-semibold uppercase tracking-[0.2em] text-sky-600">Tag phổ biến</h3>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($popularTags as $tag)
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
