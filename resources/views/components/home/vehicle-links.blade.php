<section class="border-y border-slate-200 bg-slate-50" aria-labelledby="vehicle-links-title">
    <div class="site-container site-section">
        <p class="site-eyebrow">Tra cứu theo nhu cầu</p>
        <h2 id="vehicle-links-title" class="site-section-title">Chọn loại phương tiện bạn muốn tra cứu</h2>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['route' => 'traffic-fines.lookup.car', 'title' => 'Ô tô', 'description' => 'Tra cứu phạt nguội ô tô theo biển số xe.', 'icon' => 'car', 'cta' => 'Tra cứu ngay'],
                ['route' => 'traffic-fines.lookup.motorbike', 'title' => 'Xe máy', 'description' => 'Kiểm tra dữ liệu phạt nguội của xe máy.', 'icon' => 'motorbike', 'cta' => 'Tra cứu ngay'],
                ['route' => 'traffic-fines.lookup.electric-motorbike', 'title' => 'Xe máy điện', 'description' => 'Tra cứu theo đúng nhóm phương tiện xe máy điện.', 'icon' => 'electric', 'cta' => 'Tra cứu ngay'],
                ['route' => 'traffic-fines.home', 'title' => 'Phạm vi toàn quốc', 'description' => 'Kiểm tra biển số phương tiện ở nhiều khu vực.', 'icon' => 'map', 'cta' => 'Bắt đầu tra cứu'],
            ] as $item)
                <a href="{{ route($item['route']).($item['route'] === 'traffic-fines.home' ? '#tra-cuu' : '') }}" class="site-focus group flex flex-col rounded-lg border border-slate-200 bg-white p-4 transition-colors hover:border-sky-300 hover:bg-sky-50/40">
                    <span data-home-accent-icon class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-brand" aria-hidden="true">
                        @switch($item['icon'])
                            @case('car')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="M4 15V9l2-4h12l2 4v6M6 15h12M7 19h.01M17 19h.01"/></svg>@break
                            @case('motorbike')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><circle cx="7" cy="17" r="2.5"/><circle cx="17" cy="17" r="2.5"/><path d="M9.5 17h5M8 14l3-5h4l3 5M13 9l-1-3h3"/></svg>@break
                            @case('electric')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><circle cx="7" cy="17" r="2.5"/><circle cx="17" cy="17" r="2.5"/><path d="M9.5 17h5M8 14l3-5h4l3 5M13 9l-1-3h3M11 11h3l-2 3h3"/></svg>@break
                            @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
                        @endswitch
                    </span>
                    <h3 class="mt-3 text-base font-bold text-navy group-hover:text-brand">{{ $item['title'] }}</h3>
                    <p class="mt-1.5 text-xs leading-5 text-slate-600">{{ $item['description'] }}</p>
                    <span class="mt-auto inline-flex min-h-11 items-end gap-1.5 pt-2 text-xs font-bold text-brand">{{ $item['cta'] }} <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
