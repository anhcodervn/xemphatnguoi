@extends('layouts.public')

@section('content')
    <article class="site-container py-8 sm:py-12">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => 'Blog', 'url' => route('seo.index')], ['label' => $post->title]]" />

        <header class="mt-6 max-w-3xl border-b border-slate-200 pb-6">
            @if ($post->category)
                <a href="{{ route('seo.index', ['category' => $post->category->slug]) }}" class="site-focus inline-flex min-h-11 items-center rounded text-sm font-bold text-brand">{{ $post->category->name }}</a>
            @endif
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-navy sm:text-4xl">{{ $post->title }}</h1>
            @if ($post->excerpt)<p class="mt-4 text-base leading-8 text-slate-600">{{ $post->excerpt }}</p>@endif
            <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm leading-6 text-slate-600">
                <a href="{{ route('content.about') }}" class="site-focus inline-flex min-h-11 items-center rounded font-semibold text-navy">Xuất bản bởi XemPhatNguoi.vn</a>
                @if ($post->published_at)
                    <span>Đăng ngày <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->format('d/m/Y') }}</time></span>
                @endif
                @if ($post->updated_at && $post->published_at && $post->updated_at->gt($post->published_at))
                    <span>Cập nhật <time datetime="{{ $post->updated_at->toAtomString() }}">{{ $post->updated_at->format('d/m/Y') }}</time></span>
                @endif
                <span>{{ $readingMinutes }} phút đọc</span>
            </div>
        </header>

        <div class="mt-8 grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_220px] lg:gap-10">
            <div class="min-w-0">
                @if ($coverImage)
                    <img src="{{ $coverImage }}" alt="{{ $post->cover_alt ?: $post->title }}" width="1200" height="675" class="mb-8 aspect-[16/9] w-full rounded-xl border border-slate-200 object-cover">
                @endif

                @if ($headingIndex !== [])
                    <details class="mb-8 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:hidden" open>
                        <summary class="site-focus min-h-11 cursor-pointer rounded font-bold text-navy">Mục lục bài viết</summary>
                        <nav aria-label="Mục lục trên điện thoại" class="mt-2">
                            <ol class="grid gap-1 text-sm text-slate-700">
                                @foreach ($headingIndex as $heading)
                                    <li><a href="#{{ $heading['id'] }}" class="site-focus flex min-h-11 items-center rounded py-2 hover:text-brand">{{ $heading['text'] }}</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    </details>
                @endif

                <div class="article-content">{!! $contentHtml !!}</div>

                @if ($faq !== [])
                    <section class="mt-10 border-t border-slate-200 pt-8" aria-labelledby="article-faq-title">
                        <h2 id="article-faq-title" class="text-2xl font-bold text-navy">Câu hỏi thường gặp</h2>
                        <div class="mt-4 divide-y divide-slate-200">
                            @foreach ($faq as $item)
                                @if (filled($item['question'] ?? null) && filled($item['answer'] ?? null))
                                    <details class="py-3">
                                        <summary class="site-focus min-h-11 cursor-pointer rounded py-2 text-base font-semibold text-navy">{{ $item['question'] }}</summary>
                                        <p class="mt-2 text-base leading-8 text-slate-700">{{ $item['answer'] }}</p>
                                    </details>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($articleSources->isNotEmpty())
                    <section class="mt-10 rounded-xl border border-slate-200 bg-slate-50 p-5" aria-labelledby="article-sources-title">
                        <h2 id="article-sources-title" class="text-lg font-bold text-navy">Nguồn tham khảo của bài viết</h2>
                        <ul class="mt-3 grid gap-2 text-sm leading-6">
                            @foreach ($articleSources as $source)
                                <li><a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer" class="site-focus inline-flex min-h-11 items-center break-words rounded text-brand underline underline-offset-4">{{ $source['title'] ?: $source['url'] }}</a></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <aside class="mt-8 border-l-4 border-brand bg-sky-50 p-5 text-sm leading-7 text-slate-700" aria-label="Lưu ý và góp ý nội dung">
                    <p>Nội dung mang tính tham khảo. Khi xác định mức xử lý hoặc thực hiện thủ tục, hãy đối chiếu văn bản có hiệu lực và hướng dẫn của cơ quan có thẩm quyền.</p>
                    <a href="{{ route('content.contact') }}" class="site-focus mt-2 inline-flex min-h-11 items-center rounded font-semibold text-brand underline underline-offset-4">Báo thông tin chưa chính xác hoặc cần cập nhật →</a>
                </aside>

                @if ($canShowArticleAd)
                    <div class="mt-12" data-editorial-ad><x-ad-slot name="article_bottom" /></div>
                @endif
            </div>

            <aside class="hidden lg:block">
                <div class="sticky top-24 grid gap-6">
                    @if ($headingIndex !== [])
                        <nav aria-label="Mục lục" class="border-l-2 border-slate-200 pl-5">
                            <h2 class="font-bold text-navy">Trong bài viết</h2>
                            <ol class="mt-3 grid max-h-[60vh] gap-1 overflow-y-auto text-sm leading-6 text-slate-600">
                                @foreach ($headingIndex as $heading)
                                    <li><a href="#{{ $heading['id'] }}" class="site-focus block rounded py-2 hover:text-brand">{{ $heading['text'] }}</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    <a href="{{ route('traffic-fines.knowledge.guide') }}" class="site-focus rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm font-semibold leading-7 text-navy hover:border-brand">Hướng dẫn tra cứu và đọc kết quả phạt nguội →</a>
                </div>
            </aside>
        </div>

        @if ($relatedPosts->isNotEmpty())
            <section class="mt-12 border-t border-slate-200 pt-8" aria-labelledby="related-articles-title">
                <h2 id="related-articles-title" class="text-2xl font-bold text-navy">Đọc tiếp theo chủ đề</h2>
                <div class="mt-5 grid gap-5 md:grid-cols-3">@foreach ($relatedPosts as $relatedPost)<x-blog-card :post="$relatedPost" compact />@endforeach</div>
            </section>
        @endif
    </article>
@endsection
