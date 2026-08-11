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
            ['icon' => 'server', 'title' => 'Nguồn proxy chọn lọc', 'content' => 'Danh mục được tuyển chọn kỹ từ nhiều nhà cung cấp, ưu tiên chất lượng và khả năng vận hành ổn định.'],
            ['icon' => 'globe', 'title' => 'Phủ rộng đa quốc gia', 'content' => 'Linh hoạt lựa chọn vị trí theo nhu cầu triển khai với nhiều quốc gia và khu vực phổ biến.'],
            ['icon' => 'code', 'title' => 'API đồng bộ', 'content' => 'Một chuẩn API ngắn gọn để đọc danh mục, tích hợp dịch vụ và mở rộng quy trình tự động hóa.'],
            ['icon' => 'expand', 'title' => 'Mở rộng linh hoạt', 'content' => 'Phù hợp từ nhu cầu cá nhân đến tool nội bộ, bot, agency và hệ thống reseller.'],
        ];
        $faqs = [
            ['question' => 'Làm thế nào để xem danh mục proxy?', 'answer' => 'Bạn có thể xem trực tiếp tại bảng sản phẩm bên trên hoặc gọi GET /api/v1/proxy/products để lấy danh mục đang mở bán.'],
            ['question' => 'DailyProxy.vn hỗ trợ những giao thức nào?', 'answer' => 'Giao thức được hiển thị theo từng sản phẩm, phổ biến gồm HTTP, HTTPS, SOCKS4 và SOCKS5.'],
            ['question' => 'Tôi có thể tích hợp qua API không?', 'answer' => 'Có. Sau khi tạo tài khoản, bạn có thể quản lý API key và sử dụng tài liệu tích hợp trong dashboard.'],
            ['question' => 'Tôi cần hỗ trợ lựa chọn sản phẩm?', 'answer' => 'Hãy liên hệ đội ngũ hỗ trợ. Chúng tôi sẽ tư vấn sản phẩm phù hợp với vị trí, giao thức và quy mô bạn cần.'],
        ];
    @endphp

    <section class="relative overflow-hidden bg-white px-4 pb-12 pt-12 sm:px-6 lg:px-8 lg:pb-16 lg:pt-16">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute left-1/2 top-0 h-[34rem] w-[60rem] -translate-x-1/2 rounded-full bg-blue-50/80 blur-3xl"></div>
            <div class="absolute inset-0 opacity-40 [background-image:radial-gradient(#bfdbfe_1px,transparent_1px)] [background-size:24px_24px]"></div>
        </div>

        <div class="relative mx-auto max-w-7xl">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)] lg:items-center">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-blue-700">
                        <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                        Hạ tầng proxy được tuyển chọn
                    </div>

                    <h1 class="mt-6 font-['Space_Grotesk'] text-4xl font-bold leading-[1.08] tracking-[-0.04em] text-[#071a3d] sm:text-5xl lg:text-[3.6rem]">
                        Hạ tầng proxy đa dạng,
                        <span class="text-blue-600">đa quốc gia</span>
                    </h1>

                    <p class="mt-5 max-w-xl text-base leading-8 text-slate-600 sm:text-lg">
                        Hạ tầng proxy đa dạng, đa quốc gia do chúng tôi chắt lọc, lựa chọn từ những sản phẩm tốt nhất.
                    </p>

                    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
                        <a href="#services" class="proxy-focus inline-flex min-h-[48px] items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-200 transition duration-200 hover:bg-blue-700">
                            Xem bảng giá
                        </a>
                        <a href="{{ route('auth.register') }}" class="proxy-focus inline-flex min-h-[48px] items-center justify-center rounded-lg border border-blue-200 bg-white px-6 py-3 text-sm font-bold text-blue-700 transition duration-200 hover:border-blue-300 hover:bg-blue-50">
                            Dùng thử dashboard
                        </a>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-x-5 gap-y-3 text-xs font-medium text-slate-600 sm:text-sm">
                        @foreach (['Không cần thẻ tín dụng', 'Kích hoạt nhanh', 'Hỗ trợ tận tâm'] as $benefit)
                            <span class="inline-flex items-center gap-2">
                                <svg viewBox="0 0 20 20" class="h-4 w-4 fill-blue-600" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.78-9.72a.75.75 0 00-1.06-1.06L9.25 10.69 7.78 9.22a.75.75 0 10-1.06 1.06l2 2a.75.75 0 001.06 0l4-4z" clip-rule="evenodd" /></svg>
                                {{ $benefit }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-2xl lg:mx-0">
                    <div class="absolute inset-8 rounded-full bg-blue-200/60 blur-3xl" aria-hidden="true"></div>
                    <img
                        src="{{ asset('images/landing/proxy-infrastructure-hero.jpg') }}"
                        alt="Minh họa hệ thống máy chủ proxy bảo mật kết nối toàn cầu"
                        width="1536"
                        height="1024"
                        fetchpriority="high"
                        class="relative aspect-[3/2] w-full object-contain mix-blend-multiply"
                    >
                    <div class="absolute right-0 top-[14%] hidden w-36 rounded-xl border border-blue-100 bg-white/95 p-3 shadow-lg shadow-blue-100/70 backdrop-blur sm:block">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50"><span class="h-2 w-2 rounded-full bg-emerald-500"></span></span>
                            <div><p class="text-sm font-extrabold text-[#071a3d]">Sẵn sàng</p><p class="text-[11px] text-slate-500">Theo dõi liên tục</p></div>
                        </div>
                    </div>
                    <div class="absolute bottom-[12%] left-0 hidden w-40 rounded-xl border border-blue-100 bg-white/95 p-3 shadow-lg shadow-blue-100/70 backdrop-blur sm:block">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-600">Hạ tầng</p>
                        <p class="mt-1 text-sm font-extrabold text-[#071a3d]">Đa quốc gia</p>
                    </div>
                </div>
            </div>

            <div class="mt-10 grid overflow-hidden rounded-xl border border-blue-100 bg-white shadow-[0_16px_50px_-36px_rgba(37,99,235,0.55)] sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['globe', 'Vị trí linh hoạt', 'Đa quốc gia / khu vực'],
                    ['shield', 'Nguồn đã chọn lọc', 'Ưu tiên độ ổn định'],
                    ['layers', 'Danh mục đa dạng', 'Nhiều loại proxy'],
                    ['code', 'Tích hợp nhanh', 'API sẵn sàng'],
                ] as [$icon, $title, $subtitle])
                    <div class="flex items-center gap-4 border-b border-blue-100 p-5 last:border-b-0 sm:odd:border-r lg:border-b-0 lg:border-r lg:last:border-r-0">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                            <x-landing-icon :name="$icon" class="h-6 w-6" />
                        </span>
                        <div><p class="font-bold text-[#071a3d]">{{ $title }}</p><p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="features" class="scroll-mt-24 bg-[#f8fbff] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-blue-600">Lợi thế của DailyProxy.vn</p>
                <h2 class="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-[#071a3d] sm:text-4xl">Chủ động lựa chọn, tích hợp nhanh và mở rộng dễ dàng</h2>
            </div>

            <div class="mt-9 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($features as $feature)
                    <article class="rounded-xl border border-blue-100 bg-white p-6 shadow-[0_12px_36px_-28px_rgba(37,99,235,0.55)] transition duration-200 hover:-translate-y-1 hover:shadow-lg motion-reduce:transform-none">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <x-landing-icon :name="$feature['icon']" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-5 text-lg font-extrabold text-[#071a3d]">{{ $feature['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $feature['content'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="services" class="scroll-mt-24 bg-white px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-blue-600">Danh mục đang cung cấp</p>
                <h2 class="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-[#071a3d] sm:text-4xl">Chọn proxy phù hợp với nhu cầu của bạn</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">Dữ liệu sản phẩm được đồng bộ trực tiếp từ catalog đang hoạt động.</p>
            </div>

            <div class="mt-9 overflow-hidden rounded-xl border border-blue-100 bg-white shadow-[0_20px_60px_-40px_rgba(15,42,94,0.4)]">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left">
                        <caption class="sr-only">Bảng danh mục sản phẩm proxy đang mở bán</caption>
                        <thead class="bg-[#071a3d] text-xs font-bold uppercase tracking-wide text-white">
                            <tr>
                                <th scope="col" class="px-6 py-4">Sản phẩm</th>
                                <th scope="col" class="px-5 py-4">Quốc gia</th>
                                <th scope="col" class="px-5 py-4">Giao thức</th>
                                <th scope="col" class="px-5 py-4">Giá từ</th>
                                <th scope="col" class="px-5 py-4 text-right">Bắt đầu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-50 text-sm text-slate-700">
                            @forelse ($services as $service)
                                <tr class="transition duration-200 hover:bg-blue-50/60">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><x-landing-icon name="server" class="h-5 w-5" /></span>
                                            <div><p class="font-extrabold text-[#071a3d]">{{ $service['name'] }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $service['code'] }}</p></div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">{{ $service['country_code'] ?: 'Đa quốc gia' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($service['supported_protocols'] as $protocol)
                                                <span class="rounded-md bg-blue-50 px-2 py-1 text-[11px] font-bold uppercase text-blue-700">{{ $protocol }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-base font-extrabold tabular-nums text-[#071a3d]">{{ number_format((float) $service['selling_price'], 0, ',', '.') }}đ</td>
                                    <td class="px-5 py-4 text-right">
                                        <a href="{{ route('auth.register') }}" class="proxy-focus inline-flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white transition duration-200 hover:bg-blue-700">Mua ngay</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Danh mục proxy đang được cập nhật. Vui lòng quay lại sau.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <p class="mt-4 flex items-center justify-center gap-2 text-center text-xs text-slate-500"><x-landing-icon name="shield-check" class="h-5 w-5 shrink-0 text-blue-600" />Sản phẩm được chọn lọc và cập nhật theo trạng thái catalog.</p>
        </div>
    </section>

    <section id="infrastructure" class="scroll-mt-24 bg-[#f8fbff] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:items-center">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-blue-600">Hạ tầng thực tế</p>
                <h2 class="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-[#071a3d] sm:text-4xl">Một danh mục, nhiều lựa chọn triển khai</h2>
                <p class="mt-4 max-w-xl text-base leading-8 text-slate-600">Chúng tôi tập trung vào việc tuyển chọn và chuẩn hóa sản phẩm để bạn dễ tìm đúng loại proxy, vị trí và giao thức cần thiết.</p>
                <ul class="mt-6 grid gap-3 text-sm font-medium text-slate-700">
                    @foreach (['Sản phẩm được quản lý tập trung trong một catalog', 'Thông tin giá và giao thức hiển thị minh bạch', 'Có API dành cho quy trình tích hợp tự động', 'Đội ngũ hỗ trợ khi cần tư vấn lựa chọn'] as $item)
                        <li class="flex items-start gap-3"><x-landing-icon name="check-circle" class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" /><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['pulse', 'Giám sát danh mục', 'Theo dõi trạng thái sản phẩm để thông tin hiển thị luôn rõ ràng.'],
                    ['bot', 'Tích hợp tự động', 'Kết nối hệ thống của bạn qua API và quản lý key trong dashboard.'],
                    ['shield-check', 'Chọn lọc chất lượng', 'Ưu tiên các lựa chọn phù hợp cho nhu cầu vận hành lâu dài.'],
                    ['support', 'Hỗ trợ khi cần', 'Nhận tư vấn để chọn đúng sản phẩm, vị trí và giao thức.'],
                ] as [$icon, $title, $content])
                    <article class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600"><x-landing-icon :name="$icon" class="h-6 w-6" /></span>
                            <div><h3 class="font-extrabold text-[#071a3d]">{{ $title }}</h3><p class="mt-2 text-sm leading-6 text-slate-600">{{ $content }}</p></div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="api" class="scroll-mt-24 bg-white px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[minmax(0,0.75fr)_minmax(0,1.25fr)] lg:items-center">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-blue-600">API dành cho reseller</p>
                <h2 class="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-[#071a3d]">Tích hợp danh mục chỉ với một endpoint</h2>
                <p class="mt-4 text-base leading-8 text-slate-600">Lấy danh sách proxy đang hoạt động theo thời gian thực và đưa vào quy trình nội bộ của bạn.</p>
                <a href="{{ route('auth.register') }}" class="proxy-focus mt-6 inline-flex min-h-[46px] items-center justify-center gap-2 rounded-lg border border-blue-200 px-5 py-2.5 text-sm font-bold text-blue-700 transition hover:bg-blue-50">Bắt đầu tích hợp <x-landing-icon name="arrow-right" class="h-5 w-5" /></a>
            </div>
            <div class="overflow-hidden rounded-xl bg-[#071a3d] shadow-2xl shadow-blue-200/70">
                <div class="flex items-center gap-2 border-b border-white/10 px-5 py-3 text-xs font-bold text-blue-100"><span class="rounded-md bg-blue-600 px-3 py-1">cURL</span><span class="px-2">Catalog API</span></div>
                <pre class="overflow-x-auto p-6 font-mono text-xs leading-7 text-slate-300 sm:text-sm"><code><span class="text-blue-300">curl</span> -X GET <span class="text-emerald-300">"{{ url('/api/v1/proxy/products') }}"</span> \
  -H <span class="text-emerald-300">"Authorization: Bearer YOUR_API_KEY"</span> \
  -H <span class="text-emerald-300">"Accept: application/json"</span></code></pre>
            </div>
        </div>
    </section>

    <section id="faq" class="scroll-mt-24 bg-[#f8fbff] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-blue-600">Câu hỏi thường gặp</p>
                <h2 class="mt-3 font-['Space_Grotesk'] text-3xl font-bold tracking-tight text-[#071a3d] sm:text-4xl">Thông tin bạn cần trước khi bắt đầu</h2>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($faqs as $faq)
                    <details class="group rounded-xl border border-blue-100 bg-white p-5 shadow-sm">
                        <summary class="proxy-focus flex min-h-[44px] cursor-pointer list-none items-center justify-between gap-4 font-bold text-[#071a3d] [&::-webkit-details-marker]:hidden">
                            {{ $faq['question'] }}
                            <x-landing-icon name="chevron-down" class="h-5 w-5 shrink-0 text-blue-600 transition duration-200 group-open:rotate-180 motion-reduce:transition-none" />
                        </summary>
                        <p class="pt-3 text-sm leading-7 text-slate-600">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>

            <div class="mt-12 overflow-hidden rounded-2xl bg-[#071a3d] px-6 py-8 text-white shadow-xl sm:px-10 lg:flex lg:items-center lg:justify-between lg:gap-8">
                <div><p class="text-sm font-bold uppercase tracking-[0.15em] text-blue-300">Bắt đầu ngay hôm nay</p><h2 class="mt-2 font-['Space_Grotesk'] text-2xl font-bold sm:text-3xl">Chọn hạ tầng proxy phù hợp cho dự án của bạn</h2><p class="mt-2 text-sm leading-6 text-blue-100">Tạo tài khoản để khám phá catalog và tích hợp API.</p></div>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row lg:mt-0">
                    <a href="{{ route('auth.register') }}" class="proxy-focus inline-flex min-h-[48px] items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-blue-500">Tạo tài khoản</a>
                    <a href="{{ route('content.contact') }}" class="proxy-focus inline-flex min-h-[48px] items-center justify-center rounded-lg border border-white/30 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Liên hệ tư vấn</a>
                </div>
            </div>
        </div>
    </section>
@endsection
