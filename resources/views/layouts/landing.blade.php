<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = $systemSettings ?? [];
        $landingSectionLinks = [
            'features' => url('/#features'),
            'pricing' => url('/#pricing'),
            'faq' => url('/#faq'),
        ];
        $siteName = $settings['site_name'] ?? config('app.name', 'Api bank Việt Nam');
        $siteDescription =
            $settings['meta_description'] ??
            ($settings['site_description'] ?? 'Hệ thống tích hợp nạp tiền và đối soát giao dịch tự động.');
        $metaTitle = $pageMetaTitle ?? ($settings['meta_title'] ?? $siteName);
        $favicon = $settings['favicon'] ?? null;
        $shareImage =
            $pageMetaImage ?? ($settings['og_image'] ?? ($settings['dark_logo'] ?? ($settings['light_logo'] ?? null)));
        $shareUrl = $pageMetaUrl ?? request()->url();
        $canonicalUrl = $pageMetaCanonical ?? $shareUrl;
        $pageDescription = $pageMetaDescription ?? $siteDescription;

        $footerLinkGroups = [
            [
                'title' => 'Nội dung',
                'links' => [
                    ['label' => 'Giới thiệu', 'href' => url('/gioi-thieu')],
                    ['label' => 'Tin tức', 'href' => route('seo.index')],
                    ['label' => 'Liên hệ', 'href' => url('/lien-he')],
                    ['label' => 'Câu hỏi thường gặp', 'href' => url('/cau-hoi-thuong-gap')],
                    ['label' => 'Trạng thái hệ thống', 'href' => url('/trang-thai-he-thong')],
                    ['label' => 'Cập nhật hệ thống', 'href' => url('/cap-nhat-he-thong')],
                ],
            ],
            [
                'title' => 'Điều khoản',
                'links' => [
                    ['label' => 'Điều khoản sử dụng', 'href' => url('/dieu-khoan-su-dung')],
                    ['label' => 'Chính sách bảo mật', 'href' => url('/chinh-sach-bao-mat')],
                    ['label' => 'Chính sách hoàn tiền', 'href' => url('/chinh-sach-hoan-tien')],
                    ['label' => 'Chính sách thanh toán', 'href' => url('/chinh-sach-thanh-toan')],
                    ['label' => 'Chính sách sử dụng API', 'href' => url('/chinh-sach-su-dung-api')],
                    ['label' => 'Miễn trừ trách nhiệm', 'href' => url('/mien-tru-trach-nhiem')],
                ],
            ],
        ];
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $shareUrl }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if (!empty($shareImage))
        <meta property="og:image" content="{{ $shareImage }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">

    <!-- Google Tag Manager -->
    @if (!empty($settings['gtm_id']))
        <script>
            (function(w, d, s, l, i) {
                w[l] = w[l] || [];
                w[l].push({
                    'gtm.start': new Date().getTime(),
                    event: 'gtm.js'
                });
                var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s),
                    dl = l != 'dataLayer' ? '&l=' + l : '';
                j.async = true;
                j.src =
                    'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
                f.parentNode.insertBefore(j, f);
            })(window, document, 'script', 'dataLayer', '{{ $settings['gtm_id'] }}');
        </script>
    @endif
    <!-- End Google Tag Manager -->

    <!-- Meta Pixel ID -->
    @if (!empty($settings['meta_pixel_id']))
        <!-- Meta Pixel Code -->
        <script>
            ! function(f, b, e, v, n, t, s) {
                if (f.fbq) return;
                n = f.fbq = function() {
                    n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
                };
                if (!f._fbq) f._fbq = n;
                n.push = n;
                n.loaded = !0;
                n.version = '2.0';
                n.queue = [];
                t = b.createElement(e);
                t.async = !0;
                t.src = v;
                s = b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t, s)
            }(window, document, 'script',
                'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $settings['meta_pixel_id'] }}');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id={{ $settings['meta_pixel_id'] }}&ev=PageView&noscript=1" /></noscript>
        <!-- End Meta Pixel Code -->
    @endif


    @yield('head')

    @if (!empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
        <link rel="shortcut icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|jetbrains-mono:400,600|ibm-plex-sans:400,500,600,700"
        rel="stylesheet">

    @include('components.boxicon')
    @vite(['resources/css/app.css'])

    <style>
        body {
            font-family: 'IBM Plex Sans', sans-serif;
        }

        .font-tech {
            font-family: 'Space Grotesk', sans-serif;
        }

        .font-mono-tech {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>

<body class="bg-white text-slate-900">
    <div class="min-h-screen">
        <header data-landing-header
            class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/88 backdrop-blur-xl transition duration-200">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="shrink-0">
                    @php
                        $headerLogo = $settings['dark_logo'] ?: ($settings['light_logo'] ?: null);
                    @endphp
                    @if (!empty($headerLogo))
                        <img src="{{ $headerLogo }}" alt="{{ $siteName }}"
                            class="h-10 w-auto object-contain sm:h-11">
                    @else
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-[10px] border border-white/10 bg-white/5 text-sm font-bold text-white">
                                {{ \Illuminate\Support\Str::substr($siteName, 0, 2) }}
                            </div>
                            <div>
                                <div class="font-tech text-lg font-bold tracking-tight text-white">{{ $siteName }}
                                </div>
                                <div class="font-mono-tech text-[11px] uppercase tracking-[0.24em] text-sky-300">API
                                    Banking</div>
                            </div>
                        </div>
                    @endif
                </a>

                <nav class="hidden items-center gap-8 text-[15px] font-semibold text-slate-700 md:flex">
                    @if (Auth::check())
                        <a href="/" class="transition hover:text-sky-700">Dashboard</a>
                        <a href="/package" class="transition hover:text-sky-700">Quản lý gói</a>
                        <a href="/bank-manager" class="transition hover:text-sky-700">Quản lý thẻ</a>
                        <a href="/profile" class="transition hover:text-sky-700">Quản lý tài khoản</a>
                    @else
                        <a href="{{ $landingSectionLinks['features'] }}" class="transition hover:text-sky-700">Tính
                            năng</a>
                        <a href="{{ $landingSectionLinks['pricing'] }}" class="transition hover:text-sky-700">Gói dịch
                            vụ</a>
                        <a href="{{ route('seo.index') }}" class="transition hover:text-sky-700">Tin tức</a>
                        <a href="{{ $landingSectionLinks['faq'] }}" class="transition hover:text-sky-700">FAQ</a>
                        <a href="{{ url('/lien-he') }}" class="transition hover:text-sky-700">Liên hệ</a>
                    @endif
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @if (Auth::check())
                        <a href="/"
                            class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Về trang chủ
                        </a>
                    @else
                        <a href="{{ route('auth.login') }}"
                            class="inline-flex items-center justify-center rounded-full px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:text-slate-950">
                            Đăng nhập
                        </a>
                        <a href="{{ route('auth.register') }}"
                            class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Tạo tài khoản
                        </a>
                    @endif
                </div>

                <details class="group relative md:hidden">
                    <summary
                        class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-[10px] border border-slate-200 bg-white text-slate-700">
                        <svg class="h-5 w-5 group-open:hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                            stroke-linejoin="round">
                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg class="hidden h-5 w-5 group-open:block" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 6l12 12M18 6L6 18"></path>
                        </svg>
                    </summary>

                    <div
                        class="absolute right-0 top-12 z-20 w-72 rounded-[14px] border border-slate-200 bg-white p-3 shadow-[0_20px_50px_rgba(15,23,42,0.12)] backdrop-blur-xl">
                        <nav class="grid gap-1 text-sm font-semibold text-slate-700">
                            @if (Auth::check())
                                <a href="/"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Dashboard</a>
                                <a href="/package"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Quản
                                    lý gói</a>
                                <a href="/bank-manager"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Quản
                                    lý thẻ</a>
                                <a href="/profile"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Quản
                                    lý tài khoản</a>
                            @else
                                <a href="{{ $landingSectionLinks['features'] }}"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Tính
                                    năng</a>
                                <a href="{{ $landingSectionLinks['pricing'] }}"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Gói
                                    dịch vụ</a>
                                <a href="{{ route('seo.index') }}"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Tin
                                    tức</a>
                                <a href="{{ $landingSectionLinks['faq'] }}"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">FAQ</a>
                                <a href="{{ url('/lien-he') }}"
                                    class="rounded-[10px] px-4 py-3 transition hover:bg-slate-50 hover:text-sky-700">Liên
                                    hệ</a>
                            @endif
                        </nav>

                        <div class="mt-3 grid gap-2 border-t border-slate-200 pt-3">
                            @if (Auth::check())
                                <a href="/"
                                    class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-3 text-sm font-semibold text-white">
                                    Về trang chủ
                                </a>
                            @else
                                <a href="{{ route('auth.login') }}"
                                    class="inline-flex items-center justify-center rounded-full px-4 py-3 text-sm font-semibold text-slate-700">
                                    Đăng nhập
                                </a>
                                <a href="{{ route('auth.register') }}"
                                    class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-3 text-sm font-semibold text-white">
                                    Tạo tài khoản
                                </a>
                            @endif
                        </div>
                    </div>
                </details>
            </div>
        </header>

        <main>
            @yield('main')
        </main>

        <footer id="footer" class="border-t border-slate-200 bg-white">
            <div
                class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1.2fr)_repeat(2,minmax(0,1fr))] lg:px-8">
                <div class="max-w-md">
                    <div class="font-tech text-xl font-bold text-slate-950">{{ $siteName }}</div>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        {{ $settings['site_description'] ?? 'Hệ thống hỗ trợ tích hợp nạp tiền tự động, theo dõi giao dịch và đối soát chuyển khoản cho đối tác.' }}
                    </p>
                    <div class="mt-4 space-y-1 text-sm text-slate-500">
                        @if (!empty($settings['hotline']))
                            <p>Hotline: {{ $settings['hotline'] }}</p>
                        @endif
                        @if (!empty($settings['support_email']))
                            <p>Email: {{ $settings['support_email'] }}</p>
                        @endif
                    </div>
                </div>

                @foreach ($footerLinkGroups as $group)
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-900">
                            {{ $group['title'] }}</h3>
                        <div class="grid gap-2 text-sm text-slate-600">
                            @foreach ($group['links'] as $link)
                                <a href="{{ $link['href'] }}" class="transition hover:text-slate-950">
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-200/80 bg-slate-50">
                <div
                    class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-4 text-sm text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <p>© {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ url('/dieu-khoan-su-dung') }}" class="transition hover:text-slate-900">Điều
                            khoản</a>
                        <a href="{{ url('/chinh-sach-bao-mat') }}" class="transition hover:text-slate-900">Bảo
                            mật</a>
                        <a href="{{ url('/chinh-sach-su-dung-api') }}" class="transition hover:text-slate-900">Chính
                            sách API</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    <script>
        (() => {
            const header = document.querySelector("[data-landing-header]");

            if (!header) {
                return;
            }

            const syncHeaderState = () => {
                if (window.scrollY > 12) {
                    header.classList.add("bg-white", "shadow-sm");
                    header.classList.remove("bg-white/88");
                } else {
                    header.classList.remove("bg-white", "shadow-sm");
                    header.classList.add("bg-white/88");
                }
            };

            syncHeaderState();
            window.addEventListener("scroll", syncHeaderState, {
                passive: true
            });
        })();
    </script>

    <!-- Google Tag Manager (noscript) -->
    @if (!empty($settings['gtm_id']))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ $settings['gtm_id'] }}" height="0"
                width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif
    <!-- End Google Tag Manager (noscript) -->

    <!-- custom js -->
    {!! $settings['custom_script'] ?? '' !!}
</body>

</html>
