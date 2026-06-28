<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $settings = $systemSettings ?? [];
        $siteName = $settings['site_name'] ?? config('app.name', 'AutoCron');
        $favicon = $settings['favicon'] ?? null;
        $logo = $settings['light_logo'] ?: ($settings['dark_logo'] ?: null);
        $theme = [
            'landing' => [
                'surface' => 'bg-slate-950',
                'halo' => 'bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.26),_transparent_48%),radial-gradient(circle_at_bottom_left,_rgba(37,99,235,0.2),_transparent_42%)]',
                'panel' => 'border-white/10 bg-white/[0.06] text-white shadow-[0_32px_80px_rgba(2,6,23,0.45)]',
                'badge' => 'border-cyan-400/30 bg-cyan-400/10 text-cyan-200',
                'title' => 'text-white',
                'text' => 'text-slate-300',
                'subtle' => 'text-slate-400',
                'grid' => 'border-white/10 bg-white/[0.04]',
                'primary' => 'bg-cyan-400 text-slate-950 hover:bg-cyan-300',
                'secondary' => 'border-white/12 bg-white/5 text-white hover:bg-white/10',
            ],
            'client' => [
                'surface' => 'bg-[radial-gradient(circle_at_top,_#f8fbff_0%,_#eef4f8_42%,_#e6edf3_100%)]',
                'halo' => 'bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.16),_transparent_36%),radial-gradient(circle_at_bottom_left,_rgba(14,165,233,0.12),_transparent_40%)]',
                'panel' => 'border-sky-100 bg-white/90 text-slate-900 shadow-[0_24px_70px_rgba(15,23,42,0.08)]',
                'badge' => 'border-sky-200 bg-sky-50 text-sky-700',
                'title' => 'text-slate-950',
                'text' => 'text-slate-600',
                'subtle' => 'text-slate-500',
                'grid' => 'border-slate-200 bg-slate-50/80',
                'primary' => 'bg-sky-600 text-white hover:bg-sky-500',
                'secondary' => 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
            ],
            'admin' => [
                'surface' => 'bg-[#f1f5f9]',
                'halo' => 'bg-[radial-gradient(circle_at_top_right,_rgba(79,70,229,0.16),_transparent_36%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.1),_transparent_42%)]',
                'panel' => 'border-slate-200 bg-white/92 text-slate-900 shadow-[0_28px_80px_rgba(15,23,42,0.08)]',
                'badge' => 'border-violet-200 bg-violet-50 text-violet-700',
                'title' => 'text-slate-950',
                'text' => 'text-slate-600',
                'subtle' => 'text-slate-500',
                'grid' => 'border-slate-200 bg-slate-50/80',
                'primary' => 'bg-violet-600 text-white hover:bg-violet-500',
                'secondary' => 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
            ],
        ][$errorContext ?? 'landing'];
    @endphp

    <title>{{ $statusCode }} - {{ $headline }} | {{ $siteName }}</title>
    @if (!empty($favicon))
        <link rel="icon" href="{{ $favicon }}">
        <link rel="shortcut icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,500,600,700|space-grotesk:500,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>

<body class="{{ $theme['surface'] }} min-h-screen font-['Be_Vietnam_Pro',sans-serif] text-slate-900">
    <div class="relative isolate min-h-screen overflow-hidden">
        <div class="absolute inset-0 {{ $theme['halo'] }}"></div>
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(148,163,184,0.08)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.08)_1px,transparent_1px)] bg-[size:72px_72px] opacity-50"></div>

        <main class="relative mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid w-full gap-8 lg:grid-cols-[minmax(0,1.15fr)_340px]">
                <section class="rounded-[24px] border p-6 backdrop-blur-xl sm:p-8 lg:p-10 {{ $theme['panel'] }}">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.26em] {{ $theme['badge'] }}">
                            {{ $eyebrow }}
                        </span>
                        <span class="text-sm font-medium {{ $theme['subtle'] }}">{{ $statusCode }}</span>
                    </div>

                    <div class="mt-6 flex items-start justify-between gap-6">
                        <div class="max-w-2xl">
                            @if (!empty($logo))
                                <img src="{{ $logo }}" alt="{{ $siteName }}" class="mb-6 h-10 w-auto object-contain sm:h-11">
                            @endif

                            <h1 class="font-['Space_Grotesk',_'Be_Vietnam_Pro',sans-serif] text-4xl font-bold tracking-tight sm:text-5xl {{ $theme['title'] }}">
                                {{ $headline }}
                            </h1>

                            <p class="mt-4 max-w-2xl text-base leading-8 sm:text-lg {{ $theme['text'] }}">
                                {{ $description }}
                            </p>
                        </div>

                        <div class="hidden h-20 w-20 items-center justify-center rounded-[22px] border text-3xl font-bold {{ $theme['grid'] }} lg:flex">
                            {{ $statusCode }}
                        </div>
                    </div>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border p-5 {{ $theme['grid'] }}">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $theme['subtle'] }}">Tình huống</p>
                            <p class="mt-3 text-sm leading-7 {{ $theme['text'] }}">{{ $helpText }}</p>
                        </div>

                        <div class="rounded-2xl border p-5 {{ $theme['grid'] }}">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $theme['subtle'] }}">Hướng xử lý</p>
                            <ul class="mt-3 space-y-2 text-sm leading-7 {{ $theme['text'] }}">
                                @foreach ($hints as $hint)
                                    <li class="flex gap-3">
                                        <span class="mt-2 h-2 w-2 rounded-full bg-current/70"></span>
                                        <span>{{ $hint }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $errorActions['primary']['href'] ?? '/' }}" class="inline-flex items-center justify-center rounded-full px-5 py-3 text-sm font-semibold transition {{ $theme['primary'] }}">
                            {{ $errorActions['primary']['label'] ?? 'Về trang chủ' }}
                        </a>
                        <a href="{{ $errorActions['secondary']['href'] ?? '/lien-he' }}" class="inline-flex items-center justify-center rounded-full border px-5 py-3 text-sm font-semibold transition {{ $theme['secondary'] }}">
                            {{ $errorActions['secondary']['label'] ?? 'Liên hệ hỗ trợ' }}
                        </a>
                    </div>
                </section>

                <aside class="grid gap-4 self-start">
                    <div class="rounded-[24px] border p-6 backdrop-blur-xl {{ $theme['panel'] }}">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $theme['subtle'] }}">Trung tâm hỗ trợ</p>
                        <div class="mt-5 space-y-4 text-sm {{ $theme['text'] }}">
                            @if (!empty($settings['hotline']))
                                <div>
                                    <p class="font-semibold {{ $theme['title'] }}">Hotline</p>
                                    <p class="mt-1">{{ $settings['hotline'] }}</p>
                                </div>
                            @endif

                            @if (!empty($settings['support_email']))
                                <div>
                                    <p class="font-semibold {{ $theme['title'] }}">Email</p>
                                    <p class="mt-1 break-all">{{ $settings['support_email'] }}</p>
                                </div>
                            @endif

                            <div>
                                <p class="font-semibold {{ $theme['title'] }}">Lời nhắc</p>
                                <p class="mt-1">Nếu lỗi vẫn lặp lại, hãy gửi mã trạng thái {{ $statusCode }} và thời gian gặp lỗi để đội kỹ thuật kiểm tra nhanh hơn.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[24px] border p-6 backdrop-blur-xl {{ $theme['panel'] }}">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $theme['subtle'] }}">Liên kết nhanh</p>
                        <div class="mt-5 grid gap-3 text-sm">
                            <a href="/gioi-thieu" class="rounded-2xl border px-4 py-3 transition {{ $theme['secondary'] }}">Giới thiệu hệ thống</a>
                            <a href="/cau-hoi-thuong-gap" class="rounded-2xl border px-4 py-3 transition {{ $theme['secondary'] }}">Câu hỏi thường gặp</a>
                            <a href="/trang-thai-he-thong" class="rounded-2xl border px-4 py-3 transition {{ $theme['secondary'] }}">Trạng thái hệ thống</a>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</body>

</html>
