@if ($adSlot && filled($adSlot->code))
    <aside
        aria-label="Quảng cáo"
        class="min-h-[280px] overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-4 text-center {{ $adSlot->device === 'mobile' ? 'md:hidden' : '' }} {{ $adSlot->device === 'desktop' ? 'hidden md:block' : '' }}"
        data-ad-slot="{{ $adSlot->name }}"
    >
        <p class="mb-3 text-xs font-medium text-slate-600">Quảng cáo</p>
        {!! $adSlot->code !!}
    </aside>
@endif
