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
                    <x-lookup-form :vehicle-types="$vehicleTypes" :vehicle-type="$topic['vehicle_type']" :turnstile="$turnstile" :v2-lookup-price="$v2LookupPrice" :is-authenticated="$isAuthenticated" />
                </div>
            </section>
        @endif

        <div class="mt-8 grid gap-7 lg:grid-cols-[minmax(0,700px)_240px] lg:gap-10">
            <div class="grid gap-7">
                @foreach ($topic['sections'] as $section)
                    <section id="topic-section-{{ $loop->iteration }}" class="scroll-mt-24">
                        <h2 class="text-xl font-bold tracking-tight text-navy">{{ $section['heading'] }}</h2>
                        <div class="mt-3 grid gap-4 text-base leading-8 text-slate-700">
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <section id="topic-next-steps" class="scroll-mt-24 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <h2 class="text-xl font-bold text-navy">Làm gì sau khi đọc kết quả?</h2>
                    <ol class="mt-4 grid list-decimal gap-3 pl-5 text-base leading-8 text-slate-700">
                        <li>Đối chiếu biển số và loại phương tiện. Lưu lại thời điểm tra cứu để phân biệt dữ liệu giữa các lần kiểm tra.</li>
                        <li>Đọc đầy đủ thời gian, địa điểm, mô tả hành vi và trạng thái xử lý. Nếu dữ liệu thiếu hoặc khác với thực tế, ghi lại điểm cần xác minh.</li>
                        <li>Kiểm tra thông tin với nguồn chính thức hoặc đơn vị xử lý. Không chuyển tiền cho người tự xưng hỗ trợ xóa vi phạm và không chia sẻ mã OTP.</li>
                    </ol>
                    <a href="{{ route('traffic-fines.knowledge.guide') }}" class="site-focus mt-3 inline-flex min-h-11 items-center rounded text-sm font-semibold text-brand underline underline-offset-4">Hướng dẫn tra cứu và đọc từng trường thông tin →</a>
                </section>

                <aside class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-950">
                    Thông tin trên website có tính chất hỗ trợ tra cứu. Khi cần xác nhận chính thức, hãy đối chiếu với nguồn hoặc cơ quan có thẩm quyền.
                </aside>
            </div>

            <aside class="border-t border-slate-200 pt-6 lg:border-l lg:border-t-0 lg:pl-7 lg:pt-0" aria-labelledby="related-topic-title">
                <nav aria-label="Mục lục chủ đề" class="mb-6 grid gap-1 text-sm text-slate-700">
                    <p class="font-bold text-navy">Trong trang này</p>
                    @foreach ($topic['sections'] as $section)
                        <a href="#topic-section-{{ $loop->iteration }}" class="site-focus flex min-h-11 items-center rounded py-2 hover:text-brand">{{ $section['heading'] }}</a>
                    @endforeach
                    <a href="#topic-next-steps" class="site-focus flex min-h-11 items-center rounded py-2 hover:text-brand">Các bước sau khi tra cứu</a>
                </nav>
                <h2 id="related-topic-title" class="text-base font-bold text-navy">Nội dung liên quan</h2>
                <nav class="mt-4 grid divide-y divide-slate-200 border-y border-slate-200" aria-label="Nội dung liên quan">
                    @foreach ($topic['related_routes'] as $link)
                        <a href="{{ route($link['route']) }}" class="site-focus flex min-h-12 items-center justify-between gap-3 py-3 text-sm font-semibold text-slate-700 hover:text-brand">
                            <span>{{ $link['label'] }}</span>
                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0"><path d="m7 4 6 6-6 6" /></svg>
                        </a>
                    @endforeach
                </nav>
                <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm leading-7 text-slate-600">
                    <h2 class="font-bold text-navy">Đối chiếu nguồn chính thức</h2>
                    <a href="https://www.csgt.vn/" target="_blank" rel="noopener noreferrer" class="site-focus mt-2 flex min-h-11 items-center rounded text-brand underline underline-offset-4">Cục Cảnh sát giao thông ↗</a>
                    <a href="https://dichvucong.bocongan.gov.vn/" target="_blank" rel="noopener noreferrer" class="site-focus flex min-h-11 items-center rounded text-brand underline underline-offset-4">Dịch vụ công Bộ Công an ↗</a>
                    <a href="{{ route('content.contact') }}" class="site-focus mt-3 flex min-h-11 items-center rounded font-semibold text-brand">Báo thông tin chưa chính xác →</a>
                </div>
            </aside>
        </div>
    </article>
@endsection

@if (! empty($topic['vehicle_type']))
    @push('scripts')
        @vite('resources/js/public-lookup.ts')
    @endpush
@endif
