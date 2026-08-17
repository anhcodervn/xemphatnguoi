@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-5xl px-4 py-7 sm:px-5 sm:py-9">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => 'Blog', 'url' => route('seo.index')], ['label' => $post->title]]" />

        <header class="mx-auto mt-5 max-w-2xl text-center">
            @if ($post->category)<a href="{{ route('seo.index', ['category' => $post->category->slug]) }}" class="app-focus rounded text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">{{ $post->category->name }}</a>@endif
            <h1 class="mt-2 text-2xl font-black leading-tight tracking-[-0.03em] text-slate-950 sm:text-3xl">{{ $post->title }}</h1>
            <p class="mt-2.5 text-sm leading-6 text-slate-600">{{ $post->excerpt }}</p>
            <div class="mt-3 flex flex-wrap justify-center gap-2.5 text-xs text-slate-500">
                <span>{{ $systemSettings['site_name'] ?: config('app.name', 'XemPhatNguoi.vn') }}</span>
                @if ($post->published_at)<time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->format('d/m/Y') }}</time>@endif
                <span>{{ $readingMinutes }} phút đọc</span>
            </div>
        </header>

        @if ($coverImage)<img src="{{ $coverImage }}" alt="{{ $post->cover_alt ?: $post->title }}" width="1200" height="675" class="mx-auto mt-6 aspect-[16/9] w-full max-w-3xl rounded-lg border border-slate-200 object-cover">@endif

        <div class="mx-auto mt-6 max-w-2xl"><x-ad-slot name="article_top" /></div>

        <div class="mx-auto mt-7 grid max-w-4xl gap-7 lg:grid-cols-[minmax(0,1fr)_200px]">
            <div>
                <div class="article-content">{!! $contentHtml !!}</div>
                <div class="mt-7"><x-ad-slot name="article_middle" /></div>

                @if ($faq !== [])
                    <section class="mt-8 border-t border-slate-200 pt-6">
                        <h2 class="text-xl font-black text-slate-950">Câu hỏi thường gặp</h2>
                        <div class="mt-3 divide-y divide-slate-200 border-y border-slate-200">
                            @foreach ($faq as $item)
                                @if (filled($item['question'] ?? null) && filled($item['answer'] ?? null))
                                    <details class="py-3"><summary class="app-focus cursor-pointer list-none text-sm font-bold text-slate-950 [&::-webkit-details-marker]:hidden">{{ $item['question'] }}</summary><p class="mt-2 text-xs leading-6 text-slate-600">{{ $item['answer'] }}</p></details>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="mt-7"><x-ad-slot name="article_bottom" /></div>
            </div>
            @if ($headingIndex !== [])
                <aside class="hidden lg:block"><nav aria-label="Mục lục" class="sticky top-24 border-l border-slate-200 pl-5"><p class="text-sm font-bold text-slate-950">Trong bài viết</p><ol class="mt-3 grid gap-2 text-xs leading-5 text-slate-500">@foreach($headingIndex as $heading)<li><a href="#{{ $heading['id'] }}" class="hover:text-sky-700">{{ $heading['text'] }}</a></li>@endforeach</ol></nav></aside>
            @endif
        </div>

        @if ($relatedPosts->isNotEmpty())
            <section class="mx-auto mt-9 max-w-3xl border-t border-slate-200 pt-6"><h2 class="text-xl font-black text-slate-950">Bài viết liên quan</h2><div class="mt-4 grid gap-4">@foreach($relatedPosts as $relatedPost)<x-blog-card :post="$relatedPost" />@endforeach</div></section>
        @endif
    </article>
@endsection
