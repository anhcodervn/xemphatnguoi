<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = $systemSettings ?? [];
        $landingSectionLinks = [
            'features' => url('/#features'),
            'services' => url('/#services'),
            'faq' => url('/#faq'),
        ];
        $siteName = $settings['site_name'] ?? config('app.name', 'DailyProxy.vn');
        $siteDescription = $settings['meta_description'] ?? ($settings['site_description'] ?? 'Nền tảng proxy API thương mại với bảng dịch vụ rõ ràng, tài liệu ngắn gọn và hệ thống ví cho khách hàng.');
        $metaTitle = $pageMetaTitle ?? ($settings['meta_title'] ?? $siteName);
        $favicon = $settings['favicon'] ?? null;
        $shareImage = $pageMetaImage ?? ($settings['og_image'] ?? ($settings['dark_logo'] ?? ($settings['light_logo'] ?? null)));
        $shareUrl = $pageMetaUrl ?? request()->url();
        $canonicalUrl = $pageMetaCanonical ?? $shareUrl;
        $pageDescription = $pageMetaDescription ?? $siteDescription;
        $supportEmail = $settings['support_email'] ?? '';
        $hotline = $settings['hotline'] ?? '';
        $address = $settings['address'] ?? '';
        $facebook = $settings['facebook'] ?? '';
        $zalo = $settings['zalo'] ?? '';
        $youtube = $settings['youtube'] ?? '';
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $shareUrl }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if (! empty($shareImage))
        <meta property="og:image" content="{{ $shareImage }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">

    @yield('head')

    @if (! empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800|space-grotesk:500,700&display=swap" rel="stylesheet">
    @include('components.boxicon')
    @vite(['resources/css/app.css'])
</head>

<body class="bg-slate-50 font-['Be_Vietnam_Pro'] text-slate-900">
    <div class="min-h-screen">
        <header class="sticky top-0 z-30 border-b border-sky-100/80 bg-white/90 backdrop-blur-xl">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex shrink-0 items-center gap-3">
                    @php
                        $headerLogo = $settings['dark_logo'] ?: ($settings['light_logo'] ?: null);
                    @endphp

                    @if (! empty($headerLogo))
                        <img src="{{ $headerLogo }}" alt="{{ $siteName }}" class="h-11 w-auto object-contain">
                    @else
                        <div class="flex h-11 w-11 items-center justify-center rounded-[14px] bg-[linear-gradient(135deg,#2563eb,#22d3ee)] text-lg font-bold text-white shadow-lg shadow-sky-200/70">
                            {{ \Illuminate\Support\Str::substr($siteName, 0, 1) }}
                        </div>
                        <div>
                            <div class="text-lg font-extrabold tracking-tight text-slate-950">{{ $siteName }}</div>
                            <div class="text-[11px] uppercase tracking-[0.22em] text-blue-600">Proxy Infrastructure</div>
                        </div>
                    @endif
                </a>

                <nav class="hidden items-center gap-7 text-[15px] font-semibold text-slate-600 md:flex">
                    @if (Auth::check())
                        <a href="/" class="transition hover:text-sky-600">Dashboard</a>
                        <a href="/services" class="transition hover:text-sky-600">Dịch vụ</a>
                        <a href="/api-docs" class="transition hover:text-sky-600">Tài liệu API</a>
                    @else
                        <a href="{{ $landingSectionLinks['features'] }}" class="transition hover:text-sky-600">Tính năng</a>
                        <a href="{{ $landingSectionLinks['services'] }}" class="transition hover:text-sky-600">Bảng giá</a>
                        <a href="{{ route('seo.index') }}" class="transition hover:text-sky-600">Blog</a>
                        <a href="{{ $landingSectionLinks['faq'] }}" class="transition hover:text-sky-600">FAQ</a>
                        <a href="{{ route('content.contact') }}" class="transition hover:text-sky-600">Liên hệ</a>
                    @endif
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @if (Auth::check())
                        <a href="/" class="inline-flex items-center justify-center rounded-full bg-[linear-gradient(135deg,#2563eb,#06b6d4)] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-sky-200/70 transition hover:opacity-95">
                            Về dashboard
                        </a>
                    @else
                        <a href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-full px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:text-slate-950">
                            Đăng nhập
                        </a>
                        <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-[linear-gradient(135deg,#2563eb,#06b6d4)] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-sky-200/70 transition hover:opacity-95">
                            Tạo tài khoản
                        </a>
                    @endif
                </div>
            </div>
        </header>

        <main>
            @yield('main')
        </main>

        <footer class="border-t border-sky-100 bg-white">
            <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_repeat(3,minmax(0,0.7fr))] lg:px-8">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-sky-600">{{ $siteName }}</p>
                        <h2 class="mt-2 max-w-md text-2xl font-extrabold tracking-tight text-slate-950">
                            Hạ tầng API mua proxy sẵn sàng để tích hợp và mở rộng.
                        </h2>
                    </div>
                    <p class="max-w-xl text-sm leading-7 text-slate-600">
                        Dùng một chuẩn API thống nhất để mua proxy, theo dõi bàn giao, quản lý ví và kiểm tra lịch sử đơn hàng trong cùng một hệ thống.
                    </p>

                    <div class="flex flex-wrap gap-3 text-sm text-slate-600">
                        @if ($supportEmail)
                            <span class="rounded-full bg-sky-50 px-3 py-2">{{ $supportEmail }}</span>
                        @endif
                        @if ($hotline)
                            <span class="rounded-full bg-sky-50 px-3 py-2">{{ $hotline }}</span>
                        @endif
                    </div>

                    @if ($address)
                        <p class="text-sm text-slate-500">{{ $address }}</p>
                    @endif
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-900">Khám phá</h3>
                    <div class="mt-4 flex flex-col gap-3 text-sm text-slate-600">
                        <a href="{{ url('/#features') }}" class="transition hover:text-sky-600">Tính năng</a>
                        <a href="{{ url('/#services') }}" class="transition hover:text-sky-600">Dịch vụ proxy</a>
                        <a href="{{ route('seo.index') }}" class="transition hover:text-sky-600">Blog</a>
                        <a href="{{ route('content.faq') }}" class="transition hover:text-sky-600">Câu hỏi thường gặp</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-900">Pháp lý</h3>
                    <div class="mt-4 flex flex-col gap-3 text-sm text-slate-600">
                        <a href="{{ route('content.terms') }}" class="transition hover:text-sky-600">Điều khoản sử dụng</a>
                        <a href="{{ route('content.privacy') }}" class="transition hover:text-sky-600">Chính sách bảo mật</a>
                        <a href="{{ route('content.api-usage') }}" class="transition hover:text-sky-600">Chính sách dịch vụ</a>
                        <a href="{{ route('content.payment') }}" class="transition hover:text-sky-600">Chính sách thanh toán</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-[0.16em] text-slate-900">Kết nối</h3>
                    <div class="mt-4 flex flex-col gap-3 text-sm text-slate-600">
                        <a href="{{ route('content.contact') }}" class="transition hover:text-sky-600">Liên hệ hỗ trợ</a>
                        @if ($facebook)
                            <a href="{{ $facebook }}" class="transition hover:text-sky-600">Facebook</a>
                        @endif
                        @if ($zalo)
                            <a href="{{ $zalo }}" class="transition hover:text-sky-600">Zalo</a>
                        @endif
                        @if ($youtube)
                            <a href="{{ $youtube }}" class="transition hover:text-sky-600">YouTube</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-sky-100">
                <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-5 text-sm text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <p>© {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
                    <p>API proxy thương mại cho tích hợp nhanh, gọn và ổn định.</p>
                </div>
            </div>
        </footer>
    </div>

    {!! $settings['custom_script'] ?? '' !!}
</body>

</html>
