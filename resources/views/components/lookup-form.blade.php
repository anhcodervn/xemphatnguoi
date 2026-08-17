@props(['vehicleTypes' => [], 'plate' => '', 'vehicleType' => 'car', 'turnstile' => []])

@php
    $turnstileRequired = (bool) ($turnstile['required'] ?? false);
    $turnstileAvailable = (bool) ($turnstile['available'] ?? true);
    $turnstileSiteKey = (string) ($turnstile['site_key'] ?? '');
@endphp

<form
    {{ $attributes->merge(['class' => 'grid gap-3']) }}
    data-lookup-form
    data-endpoint="{{ url('/api/lookup') }}"
    data-result-url="{{ url('/tra-cuu') }}/__PLATE__"
    data-penalties-url="{{ route('traffic-fines.penalties.index') }}"
    data-turnstile-required="{{ $turnstileRequired ? 'true' : 'false' }}"
>
    <fieldset>
        <legend class="sr-only">Loại phương tiện</legend>
        <div class="grid grid-cols-3 gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1">
            @foreach ($vehicleTypes as $value => $label)
                <label class="site-focus cursor-pointer rounded-md text-center text-[11px] font-bold text-slate-600 transition-colors hover:bg-white hover:text-brand has-[:checked]:bg-white has-[:checked]:text-brand has-[:checked]:shadow-sm has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand has-[:focus-visible]:ring-offset-2 sm:text-xs">
                    <input type="radio" name="vehicle_type" value="{{ $value }}" class="sr-only" @checked($vehicleType === $value)>
                    <span class="flex min-h-11 items-center justify-center gap-1 px-1.5 py-1.5 sm:gap-1.5 sm:px-2">
                        @switch($value)
                            @case('car')
                                <svg data-home-accent-icon aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px] shrink-0"><path d="M4 15V9l2-4h12l2 4v6M6 15h12M7 19h.01M17 19h.01"/></svg>
                                @break
                            @case('motorbike')
                                <svg data-home-accent-icon aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px] shrink-0"><circle cx="7" cy="17" r="2.5"/><circle cx="17" cy="17" r="2.5"/><path d="M9.5 17h5M8 14l3-5h4l3 5M13 9l-1-3h3"/></svg>
                                @break
                            @default
                                <svg data-home-accent-icon aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-[18px] w-[18px] shrink-0"><circle cx="7" cy="17" r="2.5"/><circle cx="17" cy="17" r="2.5"/><path d="M9.5 17h5M8 14l3-5h4l3 5M13 9l-1-3h3M11 11h3l-2 3h3"/></svg>
                        @endswitch
                        <span>{{ $label }}</span>
                    </span>
                </label>
            @endforeach
        </div>
    </fieldset>

    <div>
        <label for="lookup-plate" class="mb-1.5 block text-xs font-bold text-navy">Biển số xe</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center" aria-hidden="true">
                <svg viewBox="0 0 28 20" class="h-5 w-7 overflow-hidden rounded-sm shadow-sm"><rect width="28" height="20" class="fill-red-600"/><path d="m14 4 1.35 4.15h4.36l-3.53 2.56 1.35 4.15L14 12.3l-3.53 2.56 1.35-4.15-3.53-2.56h4.36L14 4Z" class="fill-yellow-300"/></svg>
            </span>
            <input
                id="lookup-plate"
                name="plate"
                type="text"
                value="{{ $plate }}"
                placeholder="30A-123.45"
                autocomplete="off"
                autocapitalize="characters"
                inputmode="text"
                maxlength="20"
                aria-describedby="lookup-plate-help lookup-plate-error"
                required
                class="site-focus h-12 w-full rounded-lg border border-slate-300 bg-white pl-14 pr-3 text-base font-bold uppercase tracking-wide text-navy placeholder:font-medium placeholder:normal-case placeholder:tracking-normal placeholder:text-slate-400 hover:border-slate-400"
            >
        </div>
        <p id="lookup-plate-help" class="mt-1 text-[11px] leading-5 text-slate-500">Nhập có hoặc không có dấu phân cách, ví dụ: <strong class="font-semibold text-slate-700">30A12345</strong> hoặc <strong class="font-semibold text-slate-700">30A-123.45</strong></p>
        <p id="lookup-plate-error" class="mt-2 hidden text-sm font-semibold text-danger" data-lookup-error role="alert"></p>
    </div>

    @if ($turnstileRequired)
        <div data-turnstile-field>
            @if ($turnstileAvailable && $turnstileSiteKey !== '')
                <div
                    class="min-h-[65px] w-full overflow-hidden"
                    data-turnstile-widget
                    data-site-key="{{ $turnstileSiteKey }}"
                    data-action="traffic_fine_lookup"
                    tabindex="-1"
                    aria-label="Xác minh bảo mật Cloudflare Turnstile"
                ></div>
                <input type="hidden" name="cf-turnstile-response" value="">
                <p class="mt-1 hidden text-xs font-semibold text-danger" data-turnstile-error role="alert"></p>
                <p class="mt-1 text-[10px] leading-4 text-slate-500">Biểu mẫu được bảo vệ bởi Cloudflare Turnstile.</p>
            @else
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-5 text-amber-900" data-turnstile-error role="alert">
                    Xác minh bảo mật đang được cấu hình. Vui lòng thử lại sau.
                </p>
            @endif
        </div>
    @endif

    <button type="submit" class="site-focus inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-brand px-5 text-xs font-extrabold text-white shadow-[0_8px_18px_-12px_rgba(8,117,190,0.9)] transition-colors hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-50" @disabled($turnstileRequired && ! $turnstileAvailable)>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span data-submit-label>TRA CỨU NGAY</span>
    </button>
</form>
