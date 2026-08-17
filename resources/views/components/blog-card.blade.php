@props(['post', 'compact' => false])

@if ($compact)
    <article class="group min-w-0">
        <a href="{{ url('/blog/'.$post['slug']) }}" class="site-focus flex h-full flex-col rounded-lg border border-slate-200 bg-white p-2.5 transition-colors hover:border-sky-300">
            @if (! empty($post['thumbnail']))
                <img src="{{ $post['thumbnail'] }}" alt="" width="360" height="203" loading="lazy" decoding="async" class="aspect-[16/9] w-full rounded-md bg-slate-100 object-cover">
            @else
                <div class="flex aspect-[16/9] items-center justify-center rounded-lg bg-sky-50 text-brand" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-8 w-8"><path d="M4 5h12v15H4zM16 8h4v12h-4M7 9h6M7 13h6M7 17h4"/></svg></div>
            @endif
            <div class="mt-2 flex flex-wrap items-center gap-2 px-0.5 text-[11px] text-slate-500">
                @if (! empty($post['category']))<span class="font-semibold text-brand">{{ $post['category'] }}</span>@endif
                @if (! empty($post['published_at']))<time datetime="{{ $post['published_at']->toDateString() }}">{{ $post['published_at']->format('d/m/Y') }}</time>@endif
            </div>
            <h3 class="mt-1.5 line-clamp-2 px-0.5 text-sm font-bold leading-5 text-navy group-hover:text-brand">{{ $post['title'] }}</h3>
            <p class="mt-1 line-clamp-2 px-0.5 pb-0.5 text-xs leading-5 text-slate-600">{{ $post['excerpt'] }}</p>
        </a>
    </article>
@else
    <article class="group border-t border-slate-200 pt-4 first:border-t-0 first:pt-0">
        <a href="{{ url('/blog/'.$post['slug']) }}" class="site-focus grid gap-3 rounded-lg sm:grid-cols-[140px_1fr]">
            @if (! empty($post['thumbnail']))
                <img src="{{ $post['thumbnail'] }}" alt="" width="320" height="180" loading="lazy" decoding="async" class="aspect-[16/9] w-full rounded-lg border border-slate-200 object-cover">
            @else
                <div class="flex aspect-[16/9] items-center justify-center rounded-lg bg-page text-xs font-bold uppercase tracking-wider text-slate-400">Bài viết</div>
            @endif
            <div>
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    @if (! empty($post['category']))<span>{{ $post['category'] }}</span>@endif
                    @if (! empty($post['published_at']))<time datetime="{{ $post['published_at']->toDateString() }}">{{ $post['published_at']->format('d/m/Y') }}</time>@endif
                </div>
                <h3 class="mt-1.5 text-base font-bold leading-snug text-navy group-hover:text-brand">{{ $post['title'] }}</h3>
                <p class="mt-1.5 line-clamp-2 text-xs leading-5 text-slate-600">{{ $post['excerpt'] }}</p>
            </div>
        </a>
    </article>
@endif
