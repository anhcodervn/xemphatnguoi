@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-5 sm:py-10">
        <header class="max-w-2xl">
            <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">Kiến thức hữu ích</p>
            <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Blog phạt nguội và giao thông</h1>
            <p class="mt-2.5 text-sm leading-6 text-slate-600">Hướng dẫn ngắn gọn để tra cứu, đọc kết quả và quản lý phương tiện hiệu quả hơn.</p>
        </header>

        <form method="get" action="{{ route('seo.index') }}" class="mt-5 grid gap-2.5 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:grid-cols-[1fr_200px_auto]">
            <div><label for="blog-search" class="sr-only">Tìm bài viết</label><input id="blog-search" name="q" value="{{ $search }}" placeholder="Tìm bài viết" class="app-focus h-11 w-full rounded-lg border border-slate-300 bg-white px-4 text-sm"></div>
            <div><label for="blog-category" class="sr-only">Danh mục</label><select id="blog-category" name="category" class="app-focus h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"><option value="">Tất cả danh mục</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected($activeCategorySlug === $category->slug)>{{ $category->name }} ({{ $category->posts_count }})</option>@endforeach</select></div>
            <button class="app-focus min-h-11 rounded-lg bg-slate-950 px-5 text-sm font-bold text-white hover:bg-slate-800">Tìm kiếm</button>
        </form>

        @if ($featuredPost)
            <section class="mt-7 grid gap-5 border-b border-slate-200 pb-7 md:grid-cols-[1.05fr_0.95fr] md:items-center">
                @if ($featuredPost['thumbnail'])<img src="{{ $featuredPost['thumbnail'] }}" alt="" width="800" height="450" class="aspect-[16/9] w-full rounded-xl border border-slate-200 object-cover">@else<div class="flex aspect-[16/9] items-center justify-center rounded-xl bg-slate-100 text-sm font-bold uppercase tracking-wider text-slate-400">Bài viết nổi bật</div>@endif
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">{{ $featuredPost['category'] ?: 'Bài viết mới' }}</p>
                    <h2 class="mt-2 text-xl font-black leading-tight tracking-tight text-slate-950"><a href="{{ $featuredPost['url'] }}" class="hover:text-sky-700">{{ $featuredPost['title'] }}</a></h2>
                    <p class="mt-2.5 line-clamp-3 text-sm leading-6 text-slate-600">{{ $featuredPost['excerpt'] }}</p>
                    <div class="mt-3 flex gap-3 text-[11px] text-slate-500"><span>{{ $featuredPost['published_label'] }}</span><span>{{ $featuredPost['reading_minutes'] }} phút đọc</span></div>
                </div>
            </section>
        @endif

        <div class="mt-8 grid gap-7 lg:grid-cols-[1fr_240px]">
            <section>
                <h2 class="text-xl font-black tracking-tight text-slate-950">Bài viết mới nhất</h2>
                <div class="mt-4 grid gap-4">
                    @forelse ($latestPosts as $post)<x-blog-card :post="$post" />@empty<p class="rounded-lg bg-slate-50 p-4 text-xs text-slate-600">Chưa có bài viết phù hợp.</p>@endforelse
                </div>
            </section>
            <aside class="space-y-5">
                <x-ad-slot name="article_top" />
                @if ($popularTags->isNotEmpty())
                    <div><h2 class="font-bold text-slate-950">Chủ đề</h2><div class="mt-4 flex flex-wrap gap-2">@foreach($popularTags as $tag)<span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $tag }}</span>@endforeach</div></div>
                @endif
            </aside>
        </div>
    </div>
@endsection
