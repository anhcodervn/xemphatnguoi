@extends('layouts.landing')

@section('main')
    @php
        $settings = $systemSettings ?? [];
        $hotline = $settings['hotline'] ?? '';
        $supportEmail = $settings['support_email'] ?? '';
        $heroDescription = 'Tạo và quản lý HTTP Cron Jobs theo lịch, chạy qua queue, theo dõi logs, kiểm soát quota theo gói và nhận cảnh báo khi task lỗi.';

        $socialLinks = array_filter([
            ['label' => 'Facebook', 'url' => $settings['facebook'] ?? ''],
            ['label' => 'Zalo', 'url' => $settings['zalo'] ?? ''],
            ['label' => 'YouTube', 'url' => $settings['youtube'] ?? ''],
        ], fn ($item) => ! empty($item['url']));

        $developerStats = [
            ['label' => 'Lịch chạy', 'value' => 'Interval + Cron expression'],
            ['label' => 'Queue', 'value' => 'cron-low / default / high'],
            ['label' => 'Cảnh báo', 'value' => 'Discord / Telegram / Webhook'],
            ['label' => 'Giám sát', 'value' => 'Logs + quota + retry'],
        ];

        $features = [
            ['icon' => 'bx bx-timer', 'title' => 'Lập lịch linh hoạt', 'content' => 'Tạo task theo phút, theo số giây hoặc cron expression tùy gói. Hệ thống tự tính lần chạy kế tiếp và khóa chống chạy trùng.'],
            ['icon' => 'bx bx-link-external', 'title' => 'HTTP request đa cấu hình', 'content' => 'Hỗ trợ GET, POST, PUT, PATCH, DELETE với query params, headers, body JSON, form hoặc raw.'],
            ['icon' => 'bx bx-pulse', 'title' => 'Logs dễ theo dõi', 'content' => 'Lưu request/response preview, thời gian chạy, status code, lỗi timeout, SSL, DNS hoặc body mismatch một cách gọn gàng.'],
            ['icon' => 'bx bx-bell', 'title' => 'Cảnh báo thông minh', 'content' => 'Gửi cảnh báo khi task lỗi, timeout, lệch status code hoặc hồi phục qua Discord, Telegram, Webhook, Email.'],
            ['icon' => 'bx bx-shield-quarter', 'title' => 'Bảo vệ SSRF', 'content' => 'Chặn localhost, private IP, metadata IP, scheme lạ, port nguy hiểm và redirect sang mạng nội bộ trước khi request.'],
            ['icon' => 'bx bx-layer', 'title' => 'Giới hạn theo gói', 'content' => 'Quản lý số lượng cron jobs, khoảng cách chạy tối thiểu, retention logs, quota theo ngày/tháng và độ ưu tiên queue.'],
        ];

        $faqs = [
            ['question' => 'AutoCron phù hợp với ai?', 'answer' => 'Phù hợp với đội vận hành, developer, agency hoặc doanh nghiệp cần ping endpoint định kỳ, kiểm tra healthcheck, đồng bộ dữ liệu hay chạy tác vụ HTTP theo lịch.'],
            ['question' => 'Có thể chạy thủ công một task ngay lập tức không?', 'answer' => 'Có. Với gói cho phép run now, người dùng có thể bấm chạy ngay để kiểm tra nhanh cấu hình mà không cần đợi lịch kế tiếp.'],
            ['question' => 'Task lỗi có được retry không?', 'answer' => 'Có. Tùy giới hạn gói, mỗi cron job có thể cấu hình số lần retry và thời gian chờ giữa các lần thử lại.'],
            ['question' => 'Tại sao một URL có thể bị chặn?', 'answer' => 'Hệ thống áp dụng SSRF protection để chặn localhost, private IP, metadata IP, redirect nguy hiểm hoặc port nhạy cảm nhằm bảo vệ hạ tầng và người dùng.'],
        ];

        $packages = \App\Models\Package::query()->get();
    @endphp

    <section class="relative overflow-hidden bg-slate-950 px-4 pb-16 pt-10 sm:px-6 lg:px-8 lg:pb-24 lg:pt-16">
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)] bg-[size:56px_56px] [mask-image:linear-gradient(to_bottom,rgba(0,0,0,0.85),transparent)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(56,189,248,0.18),transparent_24%),radial-gradient(circle_at_80%_78%,rgba(37,99,235,0.28),transparent_28%),linear-gradient(135deg,#020617_0%,#0f172a_54%,#1d4ed8_100%)]"></div>
        <div class="absolute left-[-90px] top-[-40px] h-[280px] w-[280px] rounded-full bg-cyan-400/18 blur-[110px]"></div>
        <div class="absolute bottom-[-140px] right-[-60px] h-[420px] w-[420px] rounded-full bg-blue-500/18 blur-[140px]"></div>

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.08fr)_420px] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200 backdrop-blur-sm">
                        <span class="h-2 w-2 rounded-full bg-cyan-400"></span>
                        AutoCron / HTTP Cron SaaS
                    </div>

                    <h1 class="font-tech mt-6 max-w-4xl text-4xl font-black leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-[3.9rem]">
                        Thuê và quản lý HTTP Cron Jobs theo lịch trong một dashboard gọn, rõ và an toàn
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                        {{ $heroDescription }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 shadow-[0_18px_40px_rgba(34,211,238,0.18)] transition hover:bg-cyan-300 sm:min-w-[176px]">
                            Bắt đầu ngay
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10 sm:min-w-[176px]">
                            Xem tính năng
                        </a>
                    </div>

                    @if ($hotline || $supportEmail)
                        <div class="mt-6 flex flex-wrap gap-3 text-sm text-slate-300">
                            @if ($hotline)
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-2">Hotline: {{ $hotline }}</span>
                            @endif
                            @if ($supportEmail)
                                <span class="rounded-full border border-white/10 bg-white/5 px-3 py-2">Email: {{ $supportEmail }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="rounded-[16px] border border-white/10 bg-white/5 p-4 backdrop-blur-md">
                    <div class="rounded-[14px] border border-white/10 bg-slate-900/60 p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono-tech text-xs uppercase tracking-[0.24em] text-cyan-200">RUNNER FLOW</p>
                                <p class="mt-2 text-lg font-semibold text-white">Dispatch đúng lịch, xử lý qua queue</p>
                            </div>
                            <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-semibold text-emerald-200">SSRF protected</span>
                        </div>

                        <div class="mt-4 rounded-[12px] border border-white/10 bg-[#020617] p-4 font-mono-tech text-xs leading-6 text-slate-200">
                            <p class="text-cyan-300">POST https://api.example.com/sync</p>
                            <p class="mt-3 text-slate-400">{</p>
                            <p class="pl-4">"job": "inventory-sync",</p>
                            <p class="pl-4">"source": "autocron"</p>
                            <p class="text-slate-400">}</p>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-200">200 OK</span>
                            <span class="rounded-full border border-cyan-400/20 bg-cyan-400/10 px-2.5 py-1 text-[11px] font-semibold text-cyan-200">Queue driven</span>
                            <span class="rounded-full border border-blue-400/20 bg-blue-400/10 px-2.5 py-1 text-[11px] font-semibold text-blue-200">Alert ready</span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            @foreach ($developerStats as $stat)
                                <div class="rounded-[12px] border border-white/10 bg-white/[0.04] px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-400">{{ $stat['label'] }}</p>
                                    <p class="mt-2 text-sm font-semibold text-white">{{ $stat['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="bg-white px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-tech text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Một nền tảng AutoCron gọn, rõ và dễ mở rộng</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">
                    Tập trung vào luồng tạo cron job, dispatch theo lịch, chạy HTTP request, ghi logs và cảnh báo khi có sự cố.
                </p>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($features as $feature)
                    <div class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-sky-200 hover:shadow-lg">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                <i class="{{ $feature['icon'] }} text-2xl"></i>
                            </div>
                            <h3 class="font-tech text-lg font-bold text-slate-950">{{ $feature['title'] }}</h3>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $feature['content'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="bg-slate-50 px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-tech text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Gói dịch vụ linh hoạt theo nhu cầu vận hành</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">
                    Chọn gói phù hợp để mở số lượng cron jobs, nâng quota, tăng retention logs và bật các kênh cảnh báo nâng cao.
                </p>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-3">
                @forelse ($packages as $package)
                    <div class="rounded-[14px] border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="font-tech text-2xl font-bold text-slate-950">{{ $package->name }}</div>
                        <p class="mt-2 text-sm text-slate-600">{{ $package->description }}</p>
                        <div class="mt-5 flex items-end gap-2">
                            <span class="font-tech text-4xl font-bold text-slate-950">{{ \App\Utils\Format::Cash($package->price) }}đ</span>
                            <span class="pb-1 text-sm text-slate-500">/ {{ $package->duration_days }} ngày</span>
                        </div>

                        <div class="mt-5 rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                            <ul class="space-y-2 text-sm text-slate-600">
                                @foreach ($package->features ?? [] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <a href="{{ route('auth.register') }}" class="mt-5 inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Đăng ký gói này
                        </a>
                    </div>
                @empty
                    <div class="rounded-[14px] border border-slate-200 bg-white p-6 text-sm text-slate-600 lg:col-span-3">
                        Chưa có gói dịch vụ nào được cấu hình.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="bg-white px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-4 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="rounded-[14px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-mono-tech text-sm uppercase tracking-[0.22em] text-sky-500">Quy trình vận hành</p>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">1. Chọn gói phù hợp</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Người dùng được cấp giới hạn số lượng cron jobs, khoảng chạy tối thiểu, logs retention và quota theo gói.</p>
                    </div>
                    <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">2. Tạo HTTP Cron Job</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Cấu hình URL, method, headers, body, lịch chạy, expected status code và điều kiện cảnh báo cho từng task.</p>
                    </div>
                    <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">3. Dispatch qua queue</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Scheduler kiểm tra cron jobs đến hạn, khóa chống chạy trùng rồi đẩy vào queue đúng priority của package.</p>
                    </div>
                    <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">4. Ghi log và gửi cảnh báo</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Sau mỗi lần chạy, hệ thống lưu log preview, cập nhật thống kê và gửi cảnh báo nếu task fail hoặc vừa hồi phục.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-[14px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-mono-tech text-sm uppercase tracking-[0.22em] text-sky-500">Kênh hỗ trợ</p>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Email hỗ trợ</p>
                        <p class="mt-1">{{ $supportEmail ?: 'Chưa cấu hình email hỗ trợ.' }}</p>
                    </div>
                    <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                        <p class="font-semibold text-slate-950">Hotline</p>
                        <p class="mt-1">{{ $hotline ?: 'Chưa cấu hình hotline.' }}</p>
                    </div>
                    @if ($socialLinks)
                        <div class="rounded-[12px] border border-slate-200 bg-slate-50 p-4">
                            <p class="font-semibold text-slate-950">Mạng xã hội</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($socialLinks as $link)
                                    <a href="{{ $link['url'] }}" target="_blank" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-sky-200 hover:text-sky-700">
                                        {{ $link['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="bg-slate-50 px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-tech text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Câu hỏi thường gặp</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">
                    Một số câu hỏi phổ biến khi dùng AutoCron để chạy HTTP cron jobs cho hệ thống.
                </p>
            </div>

            <div class="mt-10 grid gap-3">
                @foreach ($faqs as $faq)
                    <div class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-tech text-lg font-bold text-slate-950">{{ $faq['question'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $faq['answer'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-white px-4 pb-16 pt-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-[18px] bg-slate-950 px-6 py-10 text-center text-white shadow-[0_24px_80px_rgba(15,23,42,0.14)]">
            <p class="font-mono-tech text-sm uppercase tracking-[0.3em] text-sky-300">Sẵn sàng triển khai?</p>
            <h2 class="font-tech mt-4 text-2xl font-bold tracking-tight sm:text-3xl">
                Tạo tài khoản và bắt đầu cấu hình HTTP Cron Jobs, alerts và quota ngay hôm nay.
            </h2>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-cyan-400 px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-300">
                    Tạo tài khoản
                </a>
                <a href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-full border border-white/12 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                    Đăng nhập hệ thống
                </a>
            </div>
        </div>
    </section>
@endsection
