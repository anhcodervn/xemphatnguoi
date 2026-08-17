@php
    $violations = [
        ['route' => 'traffic-fines.penalties.red-light', 'title' => 'Vượt đèn đỏ', 'description' => 'Không chấp hành tín hiệu đèn.', 'icon' => 'light'],
        ['route' => 'traffic-fines.penalties.speeding', 'title' => 'Quá tốc độ', 'description' => 'Vượt giới hạn tốc độ cho phép.', 'icon' => 'speed'],
        ['route' => 'traffic-fines.penalties.wrong-lane', 'title' => 'Sai làn đường', 'description' => 'Đi không đúng làn hoặc phần đường.', 'icon' => 'lane'],
        ['route' => 'traffic-fines.penalties.parking', 'title' => 'Dừng, đỗ sai quy định', 'description' => 'Dừng hoặc đỗ không đúng nơi.', 'icon' => 'parking'],
        ['route' => 'traffic-fines.penalties.wrong-way', 'title' => 'Đi ngược chiều', 'description' => 'Đi ngược hướng lưu thông cho phép.', 'icon' => 'direction'],
        ['route' => 'traffic-fines.penalties.signs', 'title' => 'Không chấp hành biển báo', 'description' => 'Không tuân thủ hiệu lệnh biển báo.', 'icon' => 'sign'],
    ];
@endphp

<section class="bg-white" aria-labelledby="common-violations-title">
    <div class="site-container site-section">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="site-eyebrow">Các lỗi giao thông thường bị phạt nguội</p>
                <h2 id="common-violations-title" class="site-section-title">Những lỗi phổ biến</h2>
            </div>
            <a href="{{ route('traffic-fines.penalties.index') }}" class="site-focus inline-flex min-h-11 items-center text-sm font-bold text-brand hover:text-sky-800">Xem tất cả mức phạt <span aria-hidden="true">→</span></a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2.5 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($violations as $violation)
                <a href="{{ route($violation['route']) }}" class="site-focus group flex flex-col rounded-lg border border-slate-200 bg-white p-3 text-center transition-colors hover:border-sky-300 hover:bg-sky-50/40">
                    <span data-home-accent-icon class="mx-auto flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-brand transition-colors group-hover:bg-brand group-hover:text-white" aria-hidden="true">
                        @switch($violation['icon'])
                            @case('light')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><rect x="8" y="3" width="8" height="18" rx="3"/><circle cx="12" cy="7" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="17" r="1"/></svg>@break
                            @case('speed')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="M4 16a8 8 0 1 1 16 0M12 12l4-3"/><circle cx="12" cy="16" r="1"/></svg>@break
                            @case('lane')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="M7 21 9 3m8 18L15 3M12 7v3m0 4v3"/></svg>@break
                            @case('parking')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><circle cx="12" cy="12" r="9"/><path d="M10 17V7h3a3 3 0 0 1 0 6h-3"/></svg>@break
                            @case('direction')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="m9 5-5 5 5 5M4 10h11a5 5 0 0 1 5 5v4"/></svg>@break
                            @default<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="M12 3 3 20h18L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg>
                        @endswitch
                    </span>
                    <h3 class="mt-2.5 text-xs font-bold leading-5 text-navy group-hover:text-brand sm:text-sm">{{ $violation['title'] }}</h3>
                    <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-xs">{{ $violation['description'] }}</p>
                    <span class="mt-auto inline-flex min-h-11 items-end justify-center gap-1 pt-1.5 text-[11px] font-bold text-brand sm:text-xs">Xem mức phạt <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
    </div>
</section>
