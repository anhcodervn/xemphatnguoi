<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = $systemSettings ?? [];
        $landingSectionLinks = [
            'features' => url('/#features'),
            'infrastructure' => url('/#infrastructure'),
            'services' => url('/#services'),
            'api' => url('/#api'),
            'faq' => url('/#faq'),
        ];
        $configuredSiteName = trim((string) ($settings['site_name'] ?? ''));
        $siteName = $configuredSiteName !== '' ? $configuredSiteName : config('app.name', 'DailyProxy.vn');
        $siteDescription = $settings['meta_description'] ?? ($settings['site_description'] ?? 'Hạ tầng proxy đa dạng, đa quốc gia được chọn lọc cho nhu cầu tích hợp và vận hành ổn định.');
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
        $headerLogo = ($settings['dark_logo'] ?? '') ?: (($settings['light_logo'] ?? '') ?: null);
        $configuredGtmId = trim((string) ($settings['gtm_id'] ?? ''));
        $gtmId = preg_match('/^GTM-[A-Z0-9]+$/i', $configuredGtmId) === 1 ? strtoupper($configuredGtmId) : '';
        $configuredMetaPixelId = trim((string) ($settings['meta_pixel_id'] ?? ''));
        $metaPixelId = preg_match('/^[0-9]+$/', $configuredMetaPixelId) === 1 ? $configuredMetaPixelId : '';
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

    @if ($gtmId !== '')
        <script>
            (function(w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({
                    'gtm.start': new Date().getTime(),
                    event: 'gtm.js'
                });
                var firstScript = d.getElementsByTagName(s)[0];
                var tagManagerScript = d.createElement(s);
                var dataLayerQuery = l !== 'dataLayer' ? '&l=' + l : '';
                tagManagerScript.async = true;
                tagManagerScript.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dataLayerQuery;
                firstScript.parentNode.insertBefore(tagManagerScript, firstScript);
            })(window, document, 'script', 'dataLayer', {{ Illuminate\Support\Js::from($gtmId) }});
        </script>
    @endif

    @if ($metaPixelId !== '')
        <script>
            !function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() {
                    n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = true;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = true;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s);
            }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', {{ Illuminate\Support\Js::from($metaPixelId) }});
            fbq('track', 'PageView');
        </script>
    @endif

    @yield('head')

    @if (! empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700,800|space-grotesk:500,700&display=swap" rel="stylesheet">
    @include('components.boxicon')
    @vite(['resources/css/app.css'])
</head>

<body class="bg-white font-['Be_Vietnam_Pro'] text-slate-900">
    @if ($gtmId !== '')
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0"
                class="hidden" title="Google Tag Manager"></iframe>
        </noscript>
    @endif

    @if ($metaPixelId !== '')
        <noscript>
            <img src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" height="1"
                width="1" class="hidden" alt="">
        </noscript>
    @endif

    <a href="#main-content" class="proxy-focus fixed left-4 top-3 z-50 -translate-y-20 rounded-md bg-[#071a3d] px-4 py-2 text-sm font-bold text-white transition focus:translate-y-0">Bỏ qua điều hướng</a>
    <div class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-blue-100/80 bg-white/95 backdrop-blur-xl">
            <div class="mx-auto flex min-h-[72px] w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="/" class="proxy-focus flex min-h-[48px] shrink-0 items-center gap-3 rounded-lg">
                    @if (! empty($headerLogo))
                        <img src="{{ $headerLogo }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-200"><x-landing-icon name="network" class="h-6 w-6" /></span>
                        <span class="text-lg font-extrabold tracking-tight text-[#071a3d]">{{ $siteName }}</span>
                    @endif
                </a>

                <nav aria-label="Điều hướng chính" class="hidden items-center gap-7 text-sm font-bold text-slate-700 lg:flex">
                    @if (Auth::check())
                        <a href="/" class="proxy-focus rounded transition hover:text-blue-600">Dashboard</a>
                        <a href="/services" class="proxy-focus rounded transition hover:text-blue-600">Dịch vụ</a>
                        <a href="/api-docs" class="proxy-focus rounded transition hover:text-blue-600">Tài liệu API</a>
                    @else
                        <a href="{{ $landingSectionLinks['features'] }}" class="proxy-focus rounded transition hover:text-blue-600">Tính năng</a>
                        <a href="{{ $landingSectionLinks['infrastructure'] }}" class="proxy-focus rounded transition hover:text-blue-600">Hạ tầng</a>
                        <a href="{{ $landingSectionLinks['services'] }}" class="proxy-focus rounded transition hover:text-blue-600">Bảng giá</a>
                        <a href="{{ $landingSectionLinks['api'] }}" class="proxy-focus rounded transition hover:text-blue-600">API</a>
                        <a href="{{ $landingSectionLinks['faq'] }}" class="proxy-focus rounded transition hover:text-blue-600">FAQ</a>
                        <a href="{{ route('content.contact') }}" class="proxy-focus rounded transition hover:text-blue-600">Liên hệ</a>
                    @endif
                </nav>

                <div class="hidden items-center gap-2 lg:flex">
                    @if (Auth::check())
                        <a href="/" class="proxy-focus inline-flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">Về dashboard</a>
                    @else
                        <a href="{{ route('auth.login') }}" class="proxy-focus inline-flex min-h-[44px] items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-[#071a3d] transition hover:bg-blue-50">Đăng nhập</a>
                        <a href="{{ route('auth.register') }}" class="proxy-focus inline-flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-200 transition hover:bg-blue-700">Tạo tài khoản</a>
                    @endif
                </div>

                <details class="group relative lg:hidden">
                    <summary class="proxy-focus flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-lg border border-blue-100 text-[#071a3d] [&::-webkit-details-marker]:hidden" aria-label="Mở menu điều hướng"><x-landing-icon name="menu" class="h-6 w-6 group-open:hidden" /><x-landing-icon name="close" class="hidden h-6 w-6 group-open:block" /></summary>
                    <nav aria-label="Điều hướng di động" class="absolute right-0 top-14 w-72 rounded-xl border border-blue-100 bg-white p-3 text-sm font-bold text-slate-700 shadow-xl">
                        <div class="grid gap-1">
                            @if (Auth::check())
                                <a href="/" class="rounded-lg px-4 py-3 hover:bg-blue-50">Dashboard</a>
                                <a href="/services" class="rounded-lg px-4 py-3 hover:bg-blue-50">Dịch vụ</a>
                                <a href="/api-docs" class="rounded-lg px-4 py-3 hover:bg-blue-50">Tài liệu API</a>
                            @else
                                <a href="{{ $landingSectionLinks['features'] }}" class="rounded-lg px-4 py-3 hover:bg-blue-50">Tính năng</a>
                                <a href="{{ $landingSectionLinks['infrastructure'] }}" class="rounded-lg px-4 py-3 hover:bg-blue-50">Hạ tầng</a>
                                <a href="{{ $landingSectionLinks['services'] }}" class="rounded-lg px-4 py-3 hover:bg-blue-50">Bảng giá</a>
                                <a href="{{ $landingSectionLinks['api'] }}" class="rounded-lg px-4 py-3 hover:bg-blue-50">API</a>
                                <a href="{{ $landingSectionLinks['faq'] }}" class="rounded-lg px-4 py-3 hover:bg-blue-50">FAQ</a>
                                <div class="mt-2 grid grid-cols-2 gap-2 border-t border-blue-100 pt-3"><a href="{{ route('auth.login') }}" class="flex min-h-[44px] items-center justify-center rounded-lg border border-blue-100">Đăng nhập</a><a href="{{ route('auth.register') }}" class="flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 text-white">Đăng ký</a></div>
                            @endif
                        </div>
                    </nav>
                </details>
            </div>
        </header>

        <main id="main-content">
            @yield('main')
        </main>

        <footer class="bg-[#061735] text-blue-100">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-2 lg:grid-cols-[minmax(0,1.3fr)_repeat(3,minmax(0,0.7fr))] lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        @if (! empty($headerLogo))
                            <span class="rounded-lg bg-white p-2"><img src="{{ $headerLogo }}" alt="{{ $siteName }}" class="h-7 w-auto object-contain"></span>
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white"><x-landing-icon name="network" class="h-6 w-6" /></span>
                            <span class="text-lg font-extrabold text-white">{{ $siteName }}</span>
                        @endif
                    </div>
                    <p class="mt-5 max-w-sm text-sm leading-7 text-blue-100/75">Hạ tầng proxy đa dạng, đa quốc gia được chọn lọc để bạn tích hợp nhanh và vận hành thuận tiện.</p>
                    @if ($supportEmail || $hotline)
                        <div class="mt-5 grid gap-2 text-sm text-blue-100/80">@if ($supportEmail)<span>{{ $supportEmail }}</span>@endif @if ($hotline)<span>{{ $hotline }}</span>@endif</div>
                    @endif
                </div>

                <div><h3 class="text-sm font-extrabold text-white">Sản phẩm</h3><div class="mt-4 flex flex-col gap-3 text-sm text-blue-100/70"><a href="{{ url('/#services') }}" class="transition hover:text-white">Danh mục proxy</a><a href="{{ url('/#infrastructure') }}" class="transition hover:text-white">Hạ tầng</a><a href="{{ url('/#api') }}" class="transition hover:text-white">API</a></div></div>
                <div><h3 class="text-sm font-extrabold text-white">Tài nguyên</h3><div class="mt-4 flex flex-col gap-3 text-sm text-blue-100/70"><a href="{{ route('seo.index') }}" class="transition hover:text-white">Blog</a><a href="{{ route('content.faq') }}" class="transition hover:text-white">FAQ</a><a href="{{ route('content.about') }}" class="transition hover:text-white">Giới thiệu</a></div></div>
                <div><h3 class="text-sm font-extrabold text-white">Hỗ trợ</h3><div class="mt-4 flex flex-col gap-3 text-sm text-blue-100/70"><a href="{{ route('content.contact') }}" class="transition hover:text-white">Liên hệ</a><a href="{{ route('content.terms') }}" class="transition hover:text-white">Điều khoản</a><a href="{{ route('content.privacy') }}" class="transition hover:text-white">Chính sách bảo mật</a></div></div>
            </div>
            <div class="border-t border-white/10"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-blue-100/60 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8"><p>© {{ now()->year }} {{ $siteName }}. All rights reserved.</p><p>Proxy infrastructure for modern teams.</p></div></div>
        </footer>
    </div>

    {!! $settings['custom_script'] ?? '' !!}
</body>

</html>
