<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = $systemSettings ?? [];
        $siteName = $settings['site_name'] ?? config('app.name', 'Nạp Tiền Tự Động');
        $siteDescription = $settings['meta_description'] ?? ($settings['site_description'] ?? 'Nền tảng nạp tiền và đối soát tự động.');
        $metaTitle = $settings['meta_title'] ?? $siteName;
    @endphp
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $siteDescription }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|jetbrains-mono:400,600|ibm-plex-sans:400,500,600,700"
        rel="stylesheet">

    {{-- boxIcon --}}
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
    <div class="relative overflow-hidden">
        <div
            class="absolute inset-x-0 top-0 -z-10 h-[24rem] bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.14),_transparent_28%),radial-gradient(circle_at_top_right,_rgba(251,146,60,0.10),_transparent_24%),linear-gradient(180deg,_#ffffff_0%,_#f8fbff_48%,_#ffffff_100%)]">
        </div>

        <header class="border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-950 text-sm font-bold text-white shadow-[0_12px_30px_rgba(15,23,42,0.14)]">
                        NT
                    </div>
                    <div>
                        <div class="font-tech text-base font-bold tracking-tight text-slate-950 sm:text-lg">
                            {{ $siteName }}</div>
                        <div class="font-mono-tech text-[11px] uppercase tracking-[0.28em] text-sky-700">Recharge
                            Automation</div>
                    </div>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex">
                    <a href="#features" class="transition hover:text-slate-950">Sản phẩm</a>
                    <a href="#pricing" class="transition hover:text-slate-950">Bảng giá</a>
                    <a href="#faq" class="transition hover:text-slate-950">FAQ</a>
                    <a href="#footer" class="transition hover:text-slate-950">Liên hệ</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    <a href="{{ route('auth.login') }}"
                        class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">
                        Đăng nhập
                    </a>
                    <a href="{{ route('auth.register') }}"
                        class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tạo tài khoản
                    </a>
                </div>

                <details class="group relative md:hidden">
                    <summary
                        class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-2xl border border-slate-300 bg-white text-slate-700">
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
                        class="absolute right-0 top-12 z-20 w-64 rounded-3xl border border-slate-200 bg-white p-3 shadow-[0_20px_50px_rgba(15,23,42,0.12)]">
                        <nav class="grid gap-1 text-sm font-medium text-slate-700">
                            <a href="#features" class="rounded-2xl px-4 py-3 transition hover:bg-slate-50">Sản phẩm</a>
                            <a href="#pricing" class="rounded-2xl px-4 py-3 transition hover:bg-slate-50">Bảng giá</a>
                            <a href="#faq" class="rounded-2xl px-4 py-3 transition hover:bg-slate-50">FAQ</a>
                            <a href="#footer" class="rounded-2xl px-4 py-3 transition hover:bg-slate-50">Liên hệ</a>
                        </nav>

                        <div class="mt-3 grid gap-2 border-t border-slate-200 pt-3">
                            <a href="{{ route('auth.login') }}"
                                class="inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700">Đăng
                                nhập</a>
                            <a href="{{ route('auth.register') }}"
                                class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Tạo
                                tài khoản</a>
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
                class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-8 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:px-8">
                <div class="max-w-md">
                    <div class="font-tech text-xl font-bold text-slate-950">{{ $siteName }}
                    </div>
                    <p class="mt-3 text-sm leading-7 text-slate-600">
                        {{ $settings['site_description'] ?? 'Hệ thống hỗ trợ tích hợp nạp tự động qua chuyển khoản ngân hàng nhanh chóng.' }}
                    </p>
                </div>

                <div class="grid gap-3 text-sm text-slate-600">
                    <a href="#features" class="transition hover:text-slate-950">Sản phẩm</a>
                    <a href="#pricing" class="transition hover:text-slate-950">Bảng giá</a>
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
