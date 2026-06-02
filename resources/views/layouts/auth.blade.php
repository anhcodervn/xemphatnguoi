<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $authSettings = \App\Models\Setting::query()
                ->whereIn('key', ['site_name', 'favicon'])
                ->pluck('value', 'key');

            $siteName = $authSettings->get('site_name', config('app.name', 'Laravel'));
            $favicon = $authSettings->get('favicon');
            $routeTitle = match (\Illuminate\Support\Facades\Route::currentRouteName()) {
                'auth.login', 'login' => 'Đăng nhập',
                'auth.register' => 'Đăng ký',
                'password.request' => 'Quên mật khẩu',
                'password.reset' => 'Đặt lại mật khẩu',
                default => trim($__env->yieldContent('title')) ?: 'Xác thực',
            };
        @endphp

        <title>{{ $routeTitle }} - {{ $siteName }}</title>

        @if (! empty($favicon))
            <link rel="icon" href="{{ $favicon }}">
            <link rel="shortcut icon" href="{{ $favicon }}">
            <link rel="apple-touch-icon" href="{{ $favicon }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css'])
        @stack('head')
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top,_#f8fbff_0%,_#eef6ff_38%,_#f8fafc_100%)] font-['IBM_Plex_Sans',sans-serif] text-slate-900 antialiased">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <header class="flex items-center justify-between gap-3 py-2">
                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-sm font-bold text-white">
                        A
                    </div>
                    <div class="min-w-0">
                        <div class="truncate font-['Space_Grotesk',sans-serif] text-sm font-semibold tracking-tight text-slate-950 sm:text-base">
                            {{ $siteName }}
                        </div>
                        <div class="text-[11px] uppercase tracking-[0.22em] text-sky-700">
                            @yield('eyebrow', 'Auth Portal')
                        </div>
                    </div>
                </a>

                <a
                    href="{{ url('/') }}"
                    class="shrink-0 rounded-full border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950 sm:px-4"
                >
                    Trang chủ
                </a>
            </header>

            <main class="mx-auto mt-12 w-full max-w-xl rounded-[1rem] bg-white p-3 shadow-[0_28px_80px_-42px_rgba(15,23,42,0.28)]">
                @yield('main')
            </main>
        </div>

        <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/axios@1.16.1/dist/axios.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @stack('scripts')
    </body>
</html>
