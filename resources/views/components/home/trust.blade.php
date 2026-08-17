<section class="border-b border-slate-200 bg-slate-50" aria-label="Lợi ích tra cứu">
    <ul class="site-container grid grid-cols-2 py-3 lg:grid-cols-4">
        @foreach ([
            ['Tra cứu trực tuyến', 'Thao tác ngay trên trình duyệt', 'globe'],
            ['Hỗ trợ toàn quốc', 'Kiểm tra biển số ở nhiều khu vực', 'map'],
            ['Dữ liệu theo nguồn', 'Hiển thị thông tin tại thời điểm tra cứu', 'shield'],
            ['Không cần cài ứng dụng', 'Sử dụng trực tiếp trên website', 'phone'],
        ] as [$title, $description, $icon])
            <li class="flex gap-2 border-slate-200 px-2 py-2 even:border-l sm:px-3 lg:border-l lg:first:border-l-0">
                <span data-home-accent-icon class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand shadow-sm" aria-hidden="true">
                    @switch($icon)
                        @case('globe')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.5 3 14.5 0 18M12 3c-3 3.5-3 14.5 0 18"/></svg>@break
                        @case('map')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 21s7-6.2 7-12a7 7 0 1 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>@break
                        @case('shield')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 3 19 6v5c0 4.5-3 7.8-7 10-4-2.2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-5"/></svg>@break
                        @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10 18h4"/></svg>
                    @endswitch
                </span>
                <div>
                    <p class="text-xs font-bold text-navy">{{ $title }}</p>
                    <p class="mt-0.5 hidden text-[11px] leading-4 text-slate-500 sm:block">{{ $description }}</p>
                </div>
            </li>
        @endforeach
    </ul>
</section>
