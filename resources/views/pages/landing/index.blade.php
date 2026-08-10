@extends('layouts.landing')

@section('main')
    @php
        $settings = $systemSettings ?? [];
        $supportEmail = $settings['support_email'] ?? '';
        $hotline = $settings['hotline'] ?? '';
        $services = \App\Features\Client\Proxy\Resources\ProxyProductResource::collection(
            \App\Models\ProxyProduct::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        )->resolve();
        $features = [
            ['title' => 'Một API chung', 'content' => 'Mua proxy, kiểm tra bàn giao, xem ví và theo dõi đơn hàng trong một chuẩn API ngắn gọn.'],
            ['title' => 'Bảng dịch vụ rõ ràng', 'content' => 'Mỗi loại proxy hiển thị đúng giá /1 lần giải, tốc độ và tỷ lệ thành công để khách dễ đối chiếu.'],
            ['title' => 'Quản trị linh hoạt', 'content' => 'Admin có thể chủ động cấu hình dịch vụ, biểu phí, request mẫu và luồng xử lý cho từng loại proxy.'],
            ['title' => 'Sẵn sàng tích hợp', 'content' => 'Phù hợp cho tool nội bộ, bot, agency và các hệ thống cần mua proxy ổn định qua API.'],
        ];
        $faqs = [
            ['question' => 'Xem sản phẩm qua API như thế nào?', 'answer' => 'Gửi request GET đến /api/v1/proxy/products để lấy danh mục và sản phẩm đang mở bán.'],
            ['question' => 'Khi nào có API mua proxy?', 'answer' => 'Luồng order đang được xây dựng lại và sẽ được công bố sau khi hoàn tất kiểm thử.'],
            ['question' => 'Có tài liệu mẫu sẵn không?', 'answer' => 'Có. Khu vực tài liệu API cung cấp request mẫu theo từng loại proxy và response mẫu ngắn gọn.'],
            ['question' => 'Quản lý chi phí thế nào?', 'answer' => 'Bạn có thể dùng số dư ví hoặc cơ chế gói, đồng thời theo dõi lịch sử solve và chi phí theo từng task.'],
        ];
    @endphp

    <section class="relative overflow-hidden bg-[linear-gradient(180deg,#f8fbff_0%,#eef8ff_55%,#ffffff_100%)] px-4 pb-16 pt-12 sm:px-6 lg:px-8 lg:pb-24">
        <div class="absolute inset-0">
            <div class="absolute left-[-6rem] top-[-5rem] h-64 w-64 rounded-full bg-sky-200/45 blur-3xl"></div>
            <div class="absolute right-[-4rem] top-12 h-72 w-72 rounded-full bg-cyan-200/45 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-56 w-56 rounded-full bg-blue-100/60 blur-3xl"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.05fr)_420px] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/80 px-4 py-2 text-xs font-bold uppercase tracking-[0.22em] text-sky-700 shadow-sm">
                        giapproxy.vn
                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
                        Proxy Infrastructure
                    </div>

                    <h1 class="mt-6 max-w-4xl font-['Space_Grotesk'] text-4xl font-bold leading-tight tracking-[-0.04em] text-slate-950 sm:text-5xl lg:text-[3.7rem]">
                        Dịch vụ mua proxy qua nguồn thứ ba gọn, rõ giá và dễ tích hợp.
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600 sm:text-lg">
                        Xây dựng luồng mua proxy thương mại với bảng dịch vụ trực quan, tài liệu API ngắn gọn, ví người dùng và lịch sử task đầy đủ cho vận hành thực tế.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('auth.register') }}" class="inline-flex items-center justify-center rounded-full bg-[linear-gradient(135deg,#2563eb,#06b6d4)] px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-200/70 transition hover:opacity-95">
                            Tạo tài khoản
                        </a>
                        <a href="{{ route('auth.login') }}" class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-white px-6 py-3 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:text-sky-800">
                            Đăng nhập dashboard
                        </a>
                    </div>

                    @if ($supportEmail || $hotline)
                        <div class="mt-6 flex flex-wrap gap-3 text-sm text-slate-600">
                            @if ($supportEmail)
                                <span class="rounded-full bg-white px-3 py-2 shadow-sm ring-1 ring-sky-100">Email: {{ $supportEmail }}</span>
                            @endif
                            @if ($hotline)
                                <span class="rounded-full bg-white px-3 py-2 shadow-sm ring-1 ring-sky-100">Hotline: {{ $hotline }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="rounded-[28px] border border-sky-100 bg-white/90 p-4 shadow-[0_30px_80px_-40px_rgba(37,99,235,0.45)] backdrop-blur">
                    <div class="overflow-hidden rounded-[24px] bg-[linear-gradient(135deg,#2563eb_0%,#38bdf8_48%,#67e8f9_100%)] p-6 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/80">Catalog API</p>
                                <h2 class="mt-3 text-2xl font-bold leading-tight">Lấy danh sách sản phẩm bằng một endpoint.</h2>
                            </div>
                            <div class="hidden h-16 w-16 rounded-2xl bg-white/20 lg:flex lg:items-center lg:justify-center">
                                <svg viewBox="0 0 24 24" class="h-9 w-9 fill-none stroke-current" stroke-width="1.8" aria-hidden="true">
                                    <path d="M12 3l7 4v5c0 4.5-2.9 7.9-7 9-4.1-1.1-7-4.5-7-9V7l7-4z"></path>
                                    <path d="M9 12l2 2 4-5"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="mt-6 rounded-[20px] bg-slate-950/85 p-5 font-mono text-xs leading-6 text-slate-200 shadow-inner">
                            <p class="text-cyan-300">GET /api/v1/proxy/products</p>
                            <p class="mt-3 text-slate-400">X-API-KEY: your_api_key</p>
                            <p class="text-slate-400">X-API-SECRET: your_api_secret</p>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-white/16 px-4 py-3">
                                <p class="text-xs text-white/75">Response</p>
                                <p class="mt-1 font-semibold">order_code</p>
                            </div>
                            <div class="rounded-2xl bg-white/16 px-4 py-3">
                                <p class="text-xs text-white/75">Result</p>
                                <p class="mt-1 font-semibold">proxy string</p>
                            </div>
                            <div class="rounded-2xl bg-white/16 px-4 py-3">
                                <p class="text-xs text-white/75">Flow</p>
                                <p class="mt-1 font-semibold">create → check</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-8 max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-sky-600">Tính năng cốt lõi</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">
                    Tập trung vào dịch vụ mua proxy thương mại.
                </h2>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($features as $feature)
                    <div class="rounded-[20px] border border-sky-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#dbeafe,#cffafe)] text-sky-700">
                            <svg viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="2" aria-hidden="true">
                                <path d="M5 12h14"></path>
                                <path d="M12 5v14"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-950">{{ $feature['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $feature['content'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="services" class="bg-white px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-sky-600">Bảng dịch vụ</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Danh sách proxy đang mở bán</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">
                    Giá hiển thị theo đơn vị đ /1 lần giải, đi kèm tốc độ và tỷ lệ thành công để khách hàng theo dõi nhanh.
                </p>
            </div>

            <div class="mt-10 overflow-hidden rounded-[22px] border border-sky-100 bg-white shadow-[0_20px_60px_-40px_rgba(37,99,235,0.28)]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[920px]">
                        <thead class="bg-[linear-gradient(135deg,#2563eb,#2563eb_35%,#0ea5e9)] text-left text-sm font-bold text-white">
                            <tr>
                                <th class="px-6 py-4">Loại proxy</th>
                                <th class="px-4 py-4">Giá /1 lần giải</th>
                                <th class="px-4 py-4">Tốc độ</th>
                                <th class="px-4 py-4">Tỷ lệ thành công</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sky-50 text-sm text-slate-700">
                            @forelse ($services as $service)
                                <tr class="bg-white transition hover:bg-sky-50/55">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,#dbeafe,#e0f2fe)] text-sky-700">
                                                @if (data_get($service, 'settings.icon_url'))
                                                    <img
                                                        src="{{ data_get($service, 'settings.icon_url') }}"
                                                        alt="{{ $service['name'] }} icon"
                                                        class="h-8 w-8 object-contain"
                                                    >
                                                @else
                                                    <svg viewBox="0 0 24 24" class="h-6 w-6 fill-none stroke-current" stroke-width="1.8" aria-hidden="true">
                                                        <path d="M12 3l7 4v5c0 4.5-2.9 7.9-7 9-4.1-1.1-7-4.5-7-9V7l7-4z"></path>
                                                        <path d="M9 12l2 2 4-5"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-sky-600">{{ $service['code'] }}</p>
                                                <p class="mt-1 text-xl font-extrabold tracking-tight text-slate-950">{{ $service['name'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-lg font-bold text-slate-950">{{ data_get($service, 'selling_price', '-') }} đ</td>
                                    <td class="px-4 py-5">
                                        <span class="inline-flex items-center gap-2 font-semibold text-slate-700">
                                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                            {{ data_get($service, 'stats.processing_time_label', '-') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-5">
                                        <span class="inline-flex items-center gap-2 font-semibold text-emerald-600">
                                            <svg viewBox="0 0 20 20" class="h-4 w-4 fill-current" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.78-9.72a.75.75 0 00-1.06-1.06L9.25 10.69 7.78 9.22a.75.75 0 10-1.06 1.06l2 2a.75.75 0 001.06 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            {{ data_get($service, 'stats.success_rate', 99) }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                        Chưa có dịch vụ proxy nào được cấu hình để hiển thị trên landing page.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-sky-600">FAQ</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">
                        Thông tin ngắn gọn trước khi tích hợp.
                    </h2>
                    <p class="mt-4 max-w-xl text-base leading-8 text-slate-600">
                        Nếu cần test thực tế, bạn chỉ cần tạo tài khoản, nạp ví, lấy API key và gửi request theo tài liệu.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach ($faqs as $faq)
                        <div class="rounded-[18px] border border-sky-100 bg-white p-5 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-950">{{ $faq['question'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ $faq['answer'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
