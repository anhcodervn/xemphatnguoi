@extends('layouts.public')

@section('content')
    <article class="site-container site-section">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => $topic['title']]]" />

        <header class="mt-4 max-w-2xl">
            <p class="site-eyebrow">{{ $topic['eyebrow'] }}</p>
            <h1 class="mt-2 text-2xl font-extrabold leading-tight tracking-[-0.025em] text-navy sm:text-3xl">{{ $topic['title'] }}</h1>
            <p class="mt-2.5 text-sm leading-7 text-slate-600">{{ $topic['description'] }}</p>
        </header>

        @if (! empty($topic['vehicle_type']))
            <section class="mt-6 max-w-2xl rounded-lg border border-slate-200 bg-page p-4" aria-labelledby="topic-lookup-title">
                <h2 id="topic-lookup-title" class="text-base font-bold text-navy">Kiểm tra biển số ngay</h2>
                <div class="mt-3">
                    <x-lookup-form :vehicle-types="$vehicleTypes" :vehicle-type="$topic['vehicle_type']" :turnstile="$turnstile" />
                </div>
            </section>
        @endif

        <div class="mt-8 grid gap-7 lg:grid-cols-[minmax(0,700px)_240px] lg:gap-10">
            <div class="grid gap-7">
                @foreach ($topic['sections'] as $section)
                    <section>
                        <h2 class="text-xl font-bold tracking-tight text-navy">{{ $section['heading'] }}</h2>
                        <div class="mt-2.5 grid gap-3 text-sm leading-7 text-slate-700">
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <aside class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-950">
                    Thông tin trên website có tính chất hỗ trợ tra cứu. Khi cần xác nhận chính thức, hãy đối chiếu với nguồn hoặc cơ quan có thẩm quyền.
                </aside>
            </div>

            <aside class="border-t border-slate-200 pt-6 lg:border-l lg:border-t-0 lg:pl-7 lg:pt-0" aria-labelledby="related-topic-title">
                <h2 id="related-topic-title" class="text-base font-bold text-navy">Nội dung liên quan</h2>
                <nav class="mt-4 grid divide-y divide-slate-200 border-y border-slate-200" aria-label="Nội dung liên quan">
                    @foreach ($topic['related_routes'] as $link)
                        <a href="{{ route($link['route']) }}" class="site-focus flex min-h-12 items-center justify-between gap-3 py-3 text-sm font-semibold text-slate-700 hover:text-brand">
                            <span>{{ $link['label'] }}</span>
                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0"><path d="m7 4 6 6-6 6" /></svg>
                        </a>
                    @endforeach
                </nav>
            </aside>
        </div>
    </article>
@endsection

@if (! empty($topic['vehicle_type']))
    @push('scripts')
        @vite('resources/js/public-lookup.ts')
    @endpush
@endif
