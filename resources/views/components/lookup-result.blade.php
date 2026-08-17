@props(['lookup' => null, 'errorMessage' => null])

<section
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm outline-none focus:ring-2 focus:ring-sky-600 focus:ring-offset-2']) }}
    data-lookup-result
    aria-live="polite"
    aria-busy="false"
    tabindex="-1"
>
    @if ($errorMessage)
        <div class="border-l-4 border-amber-500 bg-amber-50/70 p-3 text-slate-950 sm:p-4" role="alert">
            <div class="flex gap-3 sm:gap-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 sm:h-11 sm:w-11" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6"><path d="M12 3 3 20h18L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-base font-black text-amber-950 sm:text-lg">Chưa thể trả kết quả</p>
                    <p class="mt-1 text-sm leading-6 text-amber-900">{{ $errorMessage }}</p>
                    <a href="#tra-cuu" class="site-focus mt-3 inline-flex min-h-11 items-center font-bold text-amber-950 underline decoration-amber-400 underline-offset-4">Kiểm tra lại biển số</a>
                </div>
            </div>
        </div>
    @elseif ($lookup)
        @php
            $hasViolations = $lookup->data->violationCount > 0;
            $processedCount = $lookup->data->processedCount();
            $unprocessedCount = $lookup->data->unprocessedCount();
            $unknownStatusCount = $lookup->data->unknownStatusCount();
            $vehicleLabel = config('traffic-fines.vehicle_types.'.$lookup->data->vehicleType.'.label', $lookup->data->vehicleType);
        @endphp

        <div class="px-3 py-3 text-center sm:px-4 sm:py-4" data-result-visual data-result-header data-result-toolbar data-result-tone="{{ $hasViolations ? 'violation' : 'clear' }}">
            <h2 class="sr-only">Kết quả tra cứu biển số {{ $lookup->data->displayPlate }}</h2>

            @if ($hasViolations && $lookup->data->violations !== [])
                <a href="#danh-sach-loi" class="site-focus inline-flex min-h-11 touch-manipulation items-center justify-center gap-2 rounded-lg bg-red-500 px-5 text-sm font-black text-white shadow-[0_8px_20px_-10px_rgba(239,68,68,0.8)] transition-colors hover:bg-red-600">
                    Tra cứu vi phạm
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M4 10h12m-4-4 4 4-4 4"/></svg>
                </a>
            @elseif ($hasViolations)
                <span class="inline-flex min-h-10 items-center rounded-lg bg-red-50 px-4 text-sm font-black text-red-700">Có {{ $lookup->data->violationCount }} vi phạm</span>
            @else
                <span class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-emerald-50 px-4 text-sm font-black text-emerald-700">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="m4 10 4 4 8-9"/></svg>
                    Chưa ghi nhận vi phạm
                </span>
            @endif

            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm text-slate-500">
                <span class="inline-flex items-center gap-1.5">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-slate-400"><circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2"/></svg>
                    Cập nhật: <time class="font-bold tabular-nums text-slate-700">{{ $lookup->data->checkedAt->setTimezone(config('app.timezone'))->format('H:i, d/m/Y') }}</time>
                </span>
                <span class="hidden text-slate-300 sm:inline" aria-hidden="true">|</span>
                <a href="#tra-cuu" class="site-focus inline-flex min-h-11 touch-manipulation items-center font-bold text-indigo-600 underline decoration-indigo-200 underline-offset-4 hover:text-indigo-800">Tra cứu lại</a>
            </div>

            <p class="mt-1 text-xs font-semibold text-slate-500"><span class="font-mono text-slate-700">{{ $lookup->data->displayPlate }}</span> · {{ $vehicleLabel }}</p>

            <dl class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs font-bold" data-result-metrics>
                <div class="inline-flex items-center gap-1 text-slate-600" data-result-count-pill><dt>Tổng:</dt><dd class="text-base font-black tabular-nums text-slate-800">{{ $lookup->data->violationCount }} <span class="text-xs text-slate-600">Lỗi</span></dd></div>
                <div class="inline-flex items-center gap-1 rounded-md bg-red-500 px-2.5 py-1.5 text-white shadow-sm" data-result-count-pill><dt>Chưa xử phạt</dt><dd class="order-first font-black tabular-nums">{{ $unprocessedCount }}</dd></div>
                <div class="inline-flex items-center gap-1 rounded-md bg-emerald-500 px-2.5 py-1.5 text-white shadow-sm" data-result-count-pill><dt>Đã xử phạt</dt><dd class="order-first font-black tabular-nums">{{ $processedCount }}</dd></div>
                @if ($unknownStatusCount > 0)
                    <div class="inline-flex items-center gap-1 rounded-md bg-slate-200 px-2.5 py-1.5 text-slate-700" data-result-count-pill><dt>Chưa rõ</dt><dd class="order-first font-black tabular-nums">{{ $unknownStatusCount }}</dd></div>
                @endif
            </dl>
        </div>

        <div data-result-ad-target aria-live="off"></div>

        @if ($lookup->data->violations === [])
            <div class="border-t border-slate-200 bg-slate-50 px-3 py-4 text-center" data-result-empty>
                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6"><path d="M12 3 19 6v5c0 4.5-3 7.8-7 10-4-2.2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-5"/></svg></span>
                <h3 class="mt-2 text-base font-black text-slate-950">Chưa có thông tin vi phạm</h3>
                <p class="mt-1 text-sm leading-5 text-slate-600">Kết quả phản ánh dữ liệu hiện có tại thời điểm kiểm tra.</p>
            </div>
        @else
            <section id="danh-sach-loi" class="scroll-mt-24 border-t border-slate-200 bg-slate-50 p-2.5 sm:p-3" aria-labelledby="violation-list-title">
                <div class="flex items-center justify-between gap-3 pb-2">
                    <h3 id="violation-list-title" class="text-base font-black text-slate-900">Danh sách vi phạm</h3>
                    <span class="text-xs font-semibold text-slate-500">{{ count($lookup->data->violations) }} chi tiết</span>
                </div>

                <div class="grid gap-3" data-violation-list>
                    @foreach ($lookup->data->violations as $violation)
                        @php
                            $violationTime = filled($violation['time'])
                                ? rescue(
                                    fn () => \Carbon\CarbonImmutable::parse($violation['time'])->setTimezone(config('app.timezone'))->format('H:i, d/m/Y'),
                                    $violation['time'],
                                    false,
                                )
                                : 'Chưa có dữ liệu';
                            $resolutionStatus = \App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto::resolutionStatus($violation['status'] ?? null);
                        @endphp
                        <article class="overflow-hidden rounded-lg border border-slate-300 bg-white" data-violation-card>
                            <header class="flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2" data-violation-header>
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-red-500 text-xs font-black text-white">{{ $loop->iteration }}</span>
                                    <h4 class="truncate text-base font-black text-slate-800">{{ $lookup->data->displayPlate }}</h4>
                                </div>
                                <span @class([
                                    'inline-flex max-w-[55%] rounded-md px-2 py-1 text-right text-xs font-bold leading-4',
                                    'bg-emerald-100 text-emerald-700' => $resolutionStatus === 'processed',
                                    'bg-red-100 text-red-700' => $resolutionStatus === 'unprocessed',
                                    'bg-slate-200 text-slate-700' => $resolutionStatus === 'unknown',
                                ])>{{ $violation['status'] ?: 'Chưa rõ trạng thái' }}</span>
                            </header>

                            <div class="grid gap-2 px-3 py-2 text-sm text-slate-700" data-violation-meta>
                                <p class="flex items-start gap-2"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500"><circle cx="10" cy="10" r="7"/><path d="M10 6v4l3 2"/></svg><strong class="shrink-0 text-slate-700">Thời gian:</strong><span class="font-semibold">{{ $violationTime }}</span></p>
                                <p class="flex items-start gap-2"><svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500"><path d="M10 18s6-5.3 6-10a6 6 0 1 0-12 0c0 4.7 6 10 6 10Zm0-7.5A2.5 2.5 0 1 1 10 5a2.5 2.5 0 0 1 0 5.5Z"/></svg><strong class="shrink-0 text-slate-700">Địa điểm:</strong><span class="break-words [overflow-wrap:anywhere]">{{ $violation['location'] ?: 'Chưa có dữ liệu' }}</span></p>
                            </div>

                            <div class="mx-3 rounded-lg border border-red-200 bg-red-50 p-2.5" data-violation-behavior>
                                <p class="flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.08em] text-red-700"><svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 2 1.5 17h17L10 2Zm0 5v4m0 3h.01"/></svg>Nội dung vi phạm</p>
                                <p class="mt-2 break-words [overflow-wrap:anywhere] text-sm font-semibold leading-5 text-slate-700">{{ $violation['behavior'] ?: 'Vi phạm giao thông' }}</p>
                                <a href="{{ route('traffic-fines.penalties.index') }}" class="site-focus mt-3 inline-flex min-h-11 touch-manipulation items-center gap-1.5 rounded-md bg-red-500 px-3 text-xs font-black text-white transition-colors hover:bg-red-600">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><path d="m3 14 8-8 3 3-8 8H3v-3Zm9-9 2-2 3 3-2 2"/></svg>
                                    Xem mức phạt
                                </a>
                            </div>

                            <dl class="mt-3 border-t border-slate-100 text-sm" data-violation-details>
                                @if ($violation['plate_color'])<div class="grid grid-cols-[92px_minmax(0,1fr)] gap-3 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]"><dt class="font-semibold text-slate-500">Màu biển</dt><dd class="break-words text-right font-semibold text-slate-700 [overflow-wrap:anywhere]">{{ $violation['plate_color'] }}</dd></div>@endif
                                <div class="grid grid-cols-[92px_minmax(0,1fr)] gap-3 border-t border-slate-100 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]"><dt class="font-semibold text-slate-500">Loại xe</dt><dd class="text-right font-semibold text-slate-700">{{ $vehicleLabel }}</dd></div>
                                <div class="grid grid-cols-[92px_minmax(0,1fr)] gap-3 border-t border-slate-100 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]"><dt class="font-semibold text-slate-500">Đơn vị phát hiện</dt><dd class="break-words text-right font-semibold text-slate-700 [overflow-wrap:anywhere]">{{ $violation['agency'] ?: 'Chưa có dữ liệu' }}</dd></div>
                                @if ($violation['resolution_agency'])<div class="grid grid-cols-[92px_minmax(0,1fr)] gap-3 border-t border-slate-100 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]"><dt class="font-semibold text-slate-500">Nơi giải quyết</dt><dd class="break-words text-right font-semibold text-slate-700 [overflow-wrap:anywhere]">{{ $violation['resolution_agency'] }}</dd></div>@endif
                                @if ($violation['resolution_address'])<div class="grid grid-cols-[92px_minmax(0,1fr)] gap-3 border-t border-slate-100 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]"><dt class="font-semibold text-slate-500">Địa chỉ</dt><dd class="break-words text-right font-semibold text-slate-700 [overflow-wrap:anywhere]">{{ $violation['resolution_address'] }}</dd></div>@endif
                                @if ($violation['resolution_phone'])<div class="grid grid-cols-[92px_minmax(0,1fr)] gap-3 border-t border-slate-100 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]"><dt class="font-semibold text-slate-500">Điện thoại</dt><dd class="break-words text-right font-semibold text-slate-700 [overflow-wrap:anywhere]">{{ $violation['resolution_phone'] }}</dd></div>@endif
                            </dl>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="flex gap-2.5 border-t border-slate-200 bg-white px-3 py-2.5 text-[11px] leading-5 text-slate-500 sm:px-4">
            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="mt-0.5 h-4 w-4 shrink-0 text-sky-600"><path d="M10 2.5 16 5v4.5c0 3.7-2.5 6.5-6 8-3.5-1.5-6-4.3-6-8V5l6-2.5Z"/><path d="M10 7v3m0 3h.01"/></svg>
            <p>Kết quả không thay thế xác nhận từ cơ quan có thẩm quyền.</p>
        </div>
    @else
        <div class="grid items-center gap-3 p-3 sm:grid-cols-[160px_1fr] sm:gap-4 sm:p-4">
            <div class="relative mx-auto hidden h-24 w-40 items-center justify-center sm:flex" aria-hidden="true">
                <span class="absolute h-24 w-44 rounded-full bg-sky-50"></span>
                <span class="relative rounded-xl border-[3px] border-slate-800 bg-white px-4 py-2.5 font-mono text-lg font-black tracking-wide text-slate-950 shadow-sm">30A-123.45</span>
                <svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="5" class="absolute bottom-0 right-0 h-14 w-14 text-sky-700"><circle cx="32" cy="32" r="21"/><path d="m47 47 20 20"/></svg>
            </div>
            <div class="text-center sm:text-left">
                <p class="text-base font-black text-slate-950 sm:text-lg">Kết quả sẽ hiển thị tại đây</p>
                <p class="mt-1 text-sm leading-6 text-slate-600">Nhập biển số và chọn đúng loại phương tiện để bắt đầu kiểm tra.</p>
                <a href="{{ route('traffic-fines.knowledge.guide') }}" class="site-focus mt-2 inline-flex min-h-11 items-center font-bold text-sky-700 hover:text-sky-900">Cách đọc kết quả <span class="ml-1" aria-hidden="true">→</span></a>
            </div>
        </div>
    @endif
</section>
