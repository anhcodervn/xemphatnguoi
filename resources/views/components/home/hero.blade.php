@props(['vehicleTypes' => [], 'lookupMode' => false, 'turnstile' => []])

<section id="tra-cuu" class="relative scroll-mt-20 overflow-hidden border-b border-slate-200 bg-sky-50/50" data-home-layout="vertical-centered">
    <svg aria-hidden="true" viewBox="0 0 220 360" fill="none" class="pointer-events-none absolute -left-6 top-16 hidden h-[310px] w-[190px] text-brand opacity-[0.045] md:block">
        <path d="M92 15c18 13 15 28 27 39 13 12 31 12 30 29-1 14-20 20-17 36 3 17 24 18 25 36 1 20-22 27-29 43-8 18 3 31-4 47-8 18-30 22-35 41-5 17 8 31 2 48-5 14-22 20-31 31" stroke="currentColor" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="132" cy="83" r="17" stroke="currentColor" stroke-width="6"/><circle cx="132" cy="83" r="5" fill="currentColor"/>
        <path d="M22 230c48-18 103-13 160 16M15 250c52-17 111-11 173 20" stroke="currentColor" stroke-width="3" stroke-dasharray="5 8"/>
    </svg>
    <svg aria-hidden="true" viewBox="0 0 260 320" fill="none" class="pointer-events-none absolute -right-8 top-12 hidden h-[300px] w-[240px] text-brand opacity-[0.045] md:block">
        <rect x="62" y="45" width="126" height="82" rx="16" stroke="currentColor" stroke-width="14"/><circle cx="126" cy="86" r="25" stroke="currentColor" stroke-width="12"/>
        <path d="M184 86h37v42h-24m-54-1 25 34v112M74 184h188M74 205h188M74 226h188" stroke="currentColor" stroke-width="9" stroke-linecap="round"/>
        <path d="M30 250h190M50 275h170" stroke="currentColor" stroke-width="4" stroke-dasharray="5 9"/>
    </svg>

    <div class="site-container relative py-5 text-center sm:py-7">
        <p class="inline-flex items-center gap-1.5 rounded-full border border-sky-200 bg-white px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[0.12em] text-brand">
            <svg data-home-accent-icon aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path d="M10 18s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z"/><circle cx="10" cy="8" r="2"/></svg>
            Công cụ tra cứu giao thông
        </p>
        <h1 class="mx-auto mt-2.5 max-w-3xl text-[1.65rem] font-extrabold leading-[1.2] tracking-[-0.03em] text-navy sm:text-[2rem] lg:text-[2.25rem]">
            {{ $lookupMode ? 'Tra cứu phạt nguội theo biển số xe' : 'Tra cứu phạt nguội toàn quốc' }}
        </h1>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">Nhập biển số xe để kiểm tra thông tin vi phạm giao thông nhanh chóng, rõ ràng và dễ đối chiếu.</p>

        <div data-mobile-priority-lookup class="relative mx-auto mt-4 w-full max-w-[720px] overflow-hidden rounded-xl border border-slate-200 bg-white p-3 text-left shadow-[0_18px_42px_-34px_rgba(7,26,51,0.38)] sm:p-4">
            <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-brand"></span>
            <x-lookup-form :vehicle-types="$vehicleTypes" :turnstile="$turnstile" />
            <p class="mt-2 flex items-center justify-center gap-1.5 text-center text-[11px] leading-5 text-slate-500">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 text-success"><path d="M10 2.5 16 5v4.5c0 3.7-2.5 6.5-6 8-3.5-1.5-6-4.3-6-8V5l6-2.5Z"/><path d="m7 10 2 2 4-4"/></svg>
                Không yêu cầu đăng nhập để tra cứu công khai
            </p>
        </div>

        <ul class="mx-auto mt-3 flex max-w-4xl flex-wrap justify-center gap-1.5" aria-label="Thông tin nhanh về công cụ">
            @foreach ([
                ['Tra cứu toàn quốc', 'map'],
                ['Ô tô và xe máy', 'vehicle'],
                ['Kết quả rõ ràng', 'result'],
                ['Dữ liệu theo nguồn', 'database'],
                ['Không cần đăng nhập', 'shield'],
                ['Dùng trực tiếp trên web', 'browser'],
            ] as [$label, $icon])
                <li class="inline-flex min-h-8 items-center gap-1.5 rounded-full border border-slate-200 bg-white/90 px-2.5 text-[11px] font-semibold text-slate-700 sm:text-xs">
                    <span class="text-brand" aria-hidden="true">
                        @switch($icon)
                            @case('map')<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path d="M10 18s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z"/><circle cx="10" cy="8" r="2"/></svg>@break
                            @case('vehicle')<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path d="M3 13V8l2-3h10l2 3v5M5 13h10M6 16h.01M14 16h.01"/></svg>@break
                            @case('result')<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path d="M4 3h12v14H4zM7 7h6M7 10h4"/><path d="m11 14 1.5 1.5L16 12"/></svg>@break
                            @case('database')<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><ellipse cx="10" cy="5" rx="6" ry="2.5"/><path d="M4 5v5c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5V5M4 10v5c0 1.4 2.7 2.5 6 2.5s6-1.1 6-2.5v-5"/></svg>@break
                            @case('shield')<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path d="M10 2.5 16 5v4.5c0 3.7-2.5 6.5-6 8-3.5-1.5-6-4.3-6-8V5l6-2.5Z"/><path d="m7 10 2 2 4-4"/></svg>@break
                            @default<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><rect x="2.5" y="3.5" width="15" height="13" rx="2"/><path d="M2.5 7h15"/></svg>
                        @endswitch
                    </span>
                    {{ $label }}
                </li>
            @endforeach
        </ul>
    </div>
</section>
