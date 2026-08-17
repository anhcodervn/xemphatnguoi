@props(['settings' => []])

@php
    $siteName = ($settings['site_name'] ?? null) ?: config('app.name', 'XemPhatNguoi.vn');
    $logo = $settings['light_logo'] ?? null;
    $navigation = [
        ['label' => 'Trang chủ', 'href' => route('traffic-fines.home'), 'active' => request()->routeIs('traffic-fines.home')],
        ['label' => 'Tra cứu phạt nguội', 'href' => route('traffic-fines.home').'#tra-cuu', 'active' => request()->routeIs('traffic-fines.lookup*')],
        ['label' => 'Mức phạt', 'href' => route('traffic-fines.penalties.index'), 'active' => request()->routeIs('traffic-fines.penalties.*')],
        ['label' => 'Kiến thức', 'href' => route('traffic-fines.knowledge.what-is'), 'active' => request()->routeIs('traffic-fines.knowledge.*')],
        ['label' => 'Blog', 'href' => route('seo.index'), 'active' => request()->routeIs('seo.*')],
        ['label' => 'API', 'href' => route('partners.api'), 'active' => request()->routeIs('partners.api*')],
        ['label' => 'Hỗ trợ', 'href' => route('content.contact'), 'active' => request()->routeIs('content.contact')],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
    <div class="site-container flex h-[58px] items-center justify-between gap-3">
        <a href="{{ route('traffic-fines.home') }}" class="site-focus flex min-h-11 shrink-0 items-center gap-2 rounded-lg text-sm font-extrabold tracking-tight text-navy" aria-label="{{ $siteName }} - Trang chủ">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $siteName }}" width="100" height="64" class="h-[4rem] w-[100px] object-contain">
            @else
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand text-white" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" class="h-[18px] w-[18px]"><path d="M4 7.5h16v10H4zM8 7.5l1.3-3h5.4l1.3 3M8 20h.01M16 20h.01"/><circle cx="12" cy="12.5" r="2.5"/></svg>
                </span>
                <span>{{ $siteName }}</span>
            @endif
        </a>

        <nav aria-label="Điều hướng chính" class="hidden items-center xl:flex">
            @foreach ($navigation as $item)
                <a href="{{ $item['href'] }}" @class([
                    'site-focus inline-flex h-[58px] items-center border-b-2 px-2 text-[13px] font-semibold transition-colors',
                    'border-brand text-brand' => $item['active'],
                    'border-transparent text-slate-600 hover:border-slate-300 hover:text-navy' => ! $item['active'],
                ])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="hidden shrink-0 items-center gap-2 xl:flex">
            @auth
                <a href="{{ url('/dashboard') }}" class="site-focus inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-3 text-xs font-bold text-navy hover:border-brand hover:text-brand">Dashboard</a>
            @else
                <a href="{{ route('auth.login') }}" class="site-focus inline-flex min-h-11 items-center justify-center rounded-lg px-3 text-xs font-bold text-slate-700 hover:text-brand">Đăng nhập</a>
            @endauth
            <a href="{{ route('traffic-fines.home') }}#tra-cuu" class="site-focus inline-flex min-h-11 items-center justify-center rounded-lg bg-brand px-3 text-xs font-extrabold text-white hover:bg-sky-800">Tra cứu ngay</a>
        </div>

        <details class="group relative xl:hidden">
            <summary class="site-focus flex min-h-11 min-w-11 cursor-pointer list-none items-center justify-center rounded-lg border border-slate-300 text-slate-700 [&::-webkit-details-marker]:hidden" aria-label="Mở menu điều hướng">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
            </summary>
            <nav aria-label="Điều hướng di động" class="absolute right-0 top-12 grid w-64 gap-1 rounded-lg border border-slate-200 bg-white p-2 shadow-[0_14px_35px_-18px_rgba(7,26,51,0.35)]">
                @foreach ($navigation as $item)
                    <a href="{{ $item['href'] }}" @class([
                        'site-focus flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold',
                        'bg-sky-50 text-brand' => $item['active'],
                        'text-slate-700 hover:bg-page hover:text-brand' => ! $item['active'],
                    ])>{{ $item['label'] }}</a>
                @endforeach
                <div class="mt-1 grid grid-cols-2 gap-2 border-t border-slate-200 pt-2">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="site-focus flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-bold text-navy">Dashboard</a>
                    @else
                        <a href="{{ route('auth.login') }}" class="site-focus flex min-h-11 items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-bold text-navy">Đăng nhập</a>
                    @endauth
                    <a href="{{ route('traffic-fines.home') }}#tra-cuu" class="site-focus flex min-h-11 items-center justify-center rounded-lg bg-brand px-3 text-sm font-bold text-white">Tra cứu ngay</a>
                </div>
            </nav>
        </details>
    </div>
</header>
