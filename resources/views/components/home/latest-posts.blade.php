@props(['posts' => []])

<section class="border-y border-slate-200 bg-slate-50" aria-labelledby="latest-posts-title">
    <div class="site-container site-section">
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="site-eyebrow">Kiến thức &amp; tin tức giao thông</p>
                <h2 id="latest-posts-title" class="site-section-title">Bài viết mới nhất</h2>
            </div>
            <a href="{{ route('seo.index') }}" class="site-focus hidden min-h-11 items-center text-sm font-bold text-brand hover:text-sky-800 sm:inline-flex">Xem tất cả bài viết <span aria-hidden="true">→</span></a>
        </div>

        @if ($posts !== [])
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                @foreach ($posts as $post)
                    <x-blog-card :post="$post" compact />
                @endforeach
            </div>
        @else
            <div class="mt-5 flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 text-xs text-slate-600">
                <span data-home-accent-icon class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-brand" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M6 3h9l3 3v15H6zM15 3v4h4M9 12h6M9 16h5"/></svg></span>
                <p>Chưa có bài viết được xuất bản. Nội dung mới sẽ xuất hiện tại đây.</p>
            </div>
        @endif

        <a href="{{ route('seo.index') }}" class="site-focus mt-4 inline-flex min-h-11 items-center text-sm font-bold text-brand hover:text-sky-800 sm:hidden">Xem tất cả bài viết <span aria-hidden="true">→</span></a>
    </div>
</section>
