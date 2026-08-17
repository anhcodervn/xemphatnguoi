@if ($adSlot && filled($adSlot->code))
    <aside
        aria-label="Quảng cáo"
        class="min-h-24 overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3 text-center {{ $adSlot->device === 'mobile' ? 'md:hidden' : '' }} {{ $adSlot->device === 'desktop' ? 'hidden md:block' : '' }}"
        data-ad-slot="{{ $adSlot->name }}"
    >
        <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Quảng cáo</p>
        {!! $adSlot->code !!}
    </aside>
@endif
