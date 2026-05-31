<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = $systemSettings ?? [];
        $siteName = $settings['site_name'] ?? config('app.name', 'Api bank Việt Nam');
        $siteDescription = $settings['meta_description'] ?? ($settings['site_description'] ?? 'Hệ thống tích hợp nạp tiền và đối soát giao dịch tự động.');
        $metaTitle = $settings['meta_title'] ?? $siteName;
        $favicon = $settings['favicon'] ?? null;
        $shareImage = $settings['og_image'] ?? ($settings['logo'] ?? null);
        $shareUrl = $settings['site_domain'] ?: request()->getSchemeAndHttpHost();
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $siteDescription }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $siteDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $shareUrl }}">
    @if (!empty($shareImage))
        <meta property="og:image" content="{{ $shareImage }}">
        <meta name="twitter:image" content="{{ $shareImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $siteDescription }}">

    @if (!empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
        <link rel="shortcut icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|jetbrains-mono:400,600|ibm-plex-sans:400,500,600,700" rel="stylesheet">

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
        <header class="bg-[#091022] sticky top-0 z-30 border-b border-slate-800/80 bg-slate-950/92 backdrop-blur-xl">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="shrink-0">
                    @if (!empty($settings['logo']))
                        <img src="{{ $settings['logo'] }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain sm:h-11">
                    @else
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-[10px] border border-white/10 bg-white/5 text-sm font-bold text-white">
                                {{ \Illuminate\Support\Str::substr($siteName, 0, 2) }}
                            </div>
                            <div>
                                <div class="font-tech text-lg font-bold tracking-tight text-white">{{ $siteName }}</div>
                                <div class="font-mono-tech text-[11px] uppercase tracking-[0.24em] text-sky-300">API Banking</div>
                            </div>
                        </div>
                    @endif
                </a>

                <nav class="hidden items-center gap-7 text-sm font-medium text-slate-300 md:flex">
                    <a href="#features" class="transition hover:text-white">Tính năng</a>
                    <a href="#pricing" class="transition hover:text-white">Gói dịch vụ</a>
                    <a href="#faq" class="transition hover:text-white">FAQ</a>
                    <a href="#footer" class="transition hover:text-white">Liên hệ</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <a href="{{ route('auth.login') }}" class="rounded-full border border-white/12 bg-white/5 px-4 py-2 text-sm font-medium text-white transition hover:border-white/25 hover:bg-white/10">
                        Đăng nhập
                    </a>
                    <a href="{{ route('auth.register') }}" class="rounded-full bg-cyan-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300 text-white">
                        Tạo tài khoản
                    </a>
                </div>

                <details class="group relative md:hidden">
                    <summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-[10px] border border-white/12 bg-white/5 text-white">
                        <svg class="h-5 w-5 group-open:hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg class="hidden h-5 w-5 group-open:block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 6l12 12M18 6L6 18"></path>
                        </svg>
                    </summary>

                    <div class="absolute right-0 top-12 z-20 w-72 rounded-[14px] border border-white/10 bg-slate-950/95 p-3 shadow-[0_20px_50px_rgba(2,6,23,0.45)] backdrop-blur-xl">
                        <nav class="grid gap-1 text-sm font-medium text-slate-200">
                            <a href="#features" class="rounded-[10px] px-4 py-3 transition hover:bg-white/5">Tính năng</a>
                            <a href="#pricing" class="rounded-[10px] px-4 py-3 transition hover:bg-white/5">Gói dịch vụ</a>
                            <a href="#faq" class="rounded-[10px] px-4 py-3 transition hover:bg-white/5">FAQ</a>
                            <a href="#footer" class="rounded-[10px] px-4 py-3 transition hover:bg-white/5">Liên hệ</a>
                        </nav>

                        <div class="mt-3 grid gap-2 border-t border-white/10 pt-3">
                            <a href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-full border border-white/12 bg-white/5 px-4 py-3 text-sm font-medium text-white">
                                Đăng nhập
                            </a>
                            <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-cyan-400 px-4 py-3 text-sm font-semibold text-slate-950">
                                Tạo tài khoản
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        </header>

        <main>
            @yield('main')
        </main>

        <footer id="footer" class="border-t border-slate-200 bg-white">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:px-8">
                <div class="max-w-md">
                    <div class="font-tech text-xl font-bold text-slate-950">{{ $siteName }}</div>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        {{ $settings['site_description'] ?? 'Hệ thống hỗ trợ tích hợp nạp tiền tự động, theo dõi giao dịch và đối soát chuyển khoản cho đối tác.' }}
                    </p>
                </div>

                <div class="grid gap-3 text-sm text-slate-600">
                    <a href="#features" class="transition hover:text-slate-950">Tính năng</a>
                    <a href="#pricing" class="transition hover:text-slate-950">Gói dịch vụ</a>
                    <a href="#faq" class="transition hover:text-slate-950">FAQ</a>
                </div>

                <div class="text-sm text-slate-500">
                    <p>© {{ now()->year }} {{ $siteName }}.</p>
                    @if (!empty($settings['hotline']))
                        <p class="mt-1">Hotline: {{ $settings['hotline'] }}</p>
                    @endif
                    @if (!empty($settings['support_email']))
                        <p>Email: {{ $settings['support_email'] }}</p>
                    @endif
                </div>
            </div>
        </footer>
    </div>
</body>

</html>
