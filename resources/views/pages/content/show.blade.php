@extends('layouts.landing')

@section('main')
    <section class="bg-slate-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="font-mono-tech text-xs uppercase tracking-[0.22em] text-sky-500">Trung tâm nội dung</p>
                <div class="mt-4 space-y-2">
                    @foreach ($contentLinks as $slug => $item)
                        <a
                            href="{{ url('/' . $slug) }}"
                            class="{{ $pageSlug === $slug ? 'border-sky-500 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-white' }} block rounded-[10px] border px-4 py-3 text-sm font-medium transition"
                        >
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </div>
            </aside>

            <article class="rounded-[16px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="border-b border-slate-100 pb-5">
                    <h1 class="font-tech mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
                        {{ $pageTitle }}
                    </h1>
                    <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
                        {{ $pageDescription }}
                    </p>
                </div>

                @if (count($contentBlocks) === 0)
                    <div class="rounded-[14px] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-sm text-slate-500">
                        Nội dung trang này chưa được cấu hình. Vui lòng cập nhật trong mục cấu hình điều khoản ở trang quản trị.
                    </div>
                @else
                    <div class="article-content mt-8">
                        {!! $contentHtml !!}
                    </div>
                @endif
            </article>
        </div>
    </section>
@endsection
