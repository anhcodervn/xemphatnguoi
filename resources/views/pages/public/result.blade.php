@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-4 sm:px-5 sm:py-6">
        <x-breadcrumb :items="[['label' => 'Trang chủ', 'url' => route('traffic-fines.home')], ['label' => 'Tra cứu', 'url' => route('traffic-fines.lookup-page')], ['label' => $displayPlate]]" />

        <header class="mt-3 sm:mt-4">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">Thông tin theo biển số</p>
                <h1 class="mt-1 text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Kết quả tra cứu <span class="sr-only">phạt nguội biển số {{ $displayPlate }}</span></h1>
            </div>
        </header>

        <div class="mt-3 sm:mt-4"><x-lookup-result :lookup="$lookup" :error-message="$errorMessage" /></div>
        <div class="mt-3" data-lookup-result-ad><x-ad-slot name="lookup_result_bottom" /></div>

        <section id="tra-cuu" class="mt-5 scroll-mt-24 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:mt-6 sm:p-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-100 text-sky-700" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg></span>
                <div><p class="text-[10px] font-bold uppercase tracking-[0.12em] text-sky-700">Kiểm tra phương tiện khác</p><h2 class="mt-0.5 text-base font-black text-slate-950">Tra cứu biển số mới</h2></div>
            </div>
            <div class="mt-3"><x-lookup-form :vehicle-types="$vehicleTypes" :plate="$displayPlate" :vehicle-type="$vehicleType->value" :turnstile="$turnstile" /></div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/public-lookup.ts')
@endpush
