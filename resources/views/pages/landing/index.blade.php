@extends('layouts.landing')

@section('main')
    @php
        $settings = $systemSettings ?? [];
        $siteName = $settings['site_name'] ?? config('app.name', 'Nạp Tiền Tự Động');
        $siteDescription = $settings['site_description'] ?? 'Naptientudong.com giúp người dùng tích hợp tính năng nạp tiền tự động qua chuyển khoản ngân hàng hoặc ví điện tử.';
    @endphp
    <section id="hero-section"
        class="relative overflow-hidden
  px-4 pt-10 pb-10
  sm:px-6
  lg:px-8 lg:pt-14 lg:pb-14

  bg-gradient-to-br
  from-[#020617]
  via-[#0F172A]
  to-[#2563EB]
">
        <!-- Grid tech -->
        <div
            class="absolute inset-0
  bg-[linear-gradient(rgba(255,255,255,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.04)_1px,transparent_1px)]
  bg-[size:60px_60px]
  [mask-image:linear-gradient(to_bottom,rgba(0,0,0,0.8),transparent)]">
        </div>

        <!-- Blue glow -->
        <div
            class="absolute
  top-[-120px] left-[-120px]
  w-[420px] h-[420px]
  rounded-full
  bg-cyan-400/20
  blur-[120px]">
        </div>

        <!-- Right glow -->
        <div
            class="absolute
  bottom-[-150px] right-[-100px]
  w-[500px] h-[500px]
  rounded-full
  bg-blue-500/20
  blur-[140px]">
        </div>

        <!-- Overlay -->
        <div class="absolute inset-0
  bg-gradient-to-t
  from-black/20
  to-transparent"></div>

        <div class="relative z-10 mx-auto max-w-5xl overflow-hidden rounded-[1rem] px-6 py-10 sm:px-8 lg:px-12 lg:py-14">
            <div aria-hidden="true" class="absolute inset-0">
            </div>
            <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-40 "></div>
            <div class="relative mx-auto flex max-w-3xl flex-col items-center text-center">
                <div
                    class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-sky-800 backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                    {{ strtoupper($siteName) }}
                </div>
                <h1 class="font-tech mt-6 max-w-3xl text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Hệ thống nạp tiền tự động tích hợp đa nền tảng cực dễ dàng
                </h1>

                <p class="mt-4 max-w-2xl text-base leading-7 text-blue-100/80 sm:text-lg">{{ $siteDescription }}</p>

                <div class="mt-6 flex gap-3">
                    <a href="{{ route('auth.login') }}"
                        class="inline-flex items-center justify-center rounded-full bg-[#1E4BAA] px-5 py-3
                        text-sm font-semibold text-white shadow-[0_16px_40px_rgba(15,23,42,0.14)] transition hover:bg-sky-700">
                        Bắt đầu ngay
                    </a>
                    <a href="{{ route('auth.login') }}"
                        class="inline-flex items-center justify-center rounded-full border border-slate-300 bg-white/90 px-5 py-3 text-sm font-semibold text-slate-800 backdrop-blur-sm transition hover:border-slate-950">
                        Xem hệ thống
                    </a>
                </div>
            </div>

            {{-- list item thông số --}}
            <div class="mt-[4rem] grid grid-cols-2 gap-3 md:grid-cols-4">
                @php
                    $arrItem = [
                        [
                            'title' => 'Thành viên hoạt động',
                            'data' => '2k+',
                        ],
                        [
                            'title' => 'Ngân hàng hỗ trợ',
                            'data' => '3',
                        ],
                        [
                            'title' => 'Tốc độ cập nhật',
                            'data' => '2s',
                        ],
                        [
                            'title' => 'Thời gian hoạt động ',
                            'data' => '24/7',
                        ],
                    ];
                @endphp
                <!-- Item -->
                @foreach ($arrItem as $item)
                    <div
                        class="group relative overflow-hidden rounded-2xl
                                border border-white/10
                                bg-white/[0.05]
                                backdrop-blur-xl
                                px-4 py-5
                                transition-all duration-300
                                hover:-translate-y-1
                                hover:border-cyan-300/20">

                        <div class="relative z-10 text-center">
                            <h3 class="text-2xl md:text-3xl font-black text-white">
                                {{ $item['data'] }}
                            </h3>

                            <p class="mt-1 text-xs md:text-sm text-blue-100/70">
                                {{ $item['title'] }}
                            </p>
                        </div>
                    </div>
                @endforeach



            </div>


        </div>
    </section>

    <section class="mt-5 border-b border-gray-300 mb-[3rem]">
        <div class="mx-auto max-w-5xl px-4 pb-10 sm:px-6 lg:px-8">
            <div class="text-center md:px-[15rem]">
                <h2 class="font-mono-tech text-2xl uppercase tracking-[0.1em] font-bold text-sky-700">Các cổng kết nối</h2>
                <p class="font-tech mt-3 text-sm tracking-tight">
                    Naptientudong.com hỗ trợ kết nối thông qua API được ủy quyền. Mọi dữ liệu được mã hóa AES-256 và truyền
                    tải
                    qua HTTPS.
                </p>
            </div>

            <div class="px-4 py-4 sm:px-6">
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {{-- list bank --}}
                    @php
                        $arrListBank = [
                            [
                                'bank_name' => 'Vietcombank',
                                'bank_logo' =>
                                    'https://cdn.haitrieu.com/wp-content/uploads/2022/02/Icon-Vietcombank.png',
                                'bank_status' => 'active',
                            ],
                            [
                                'bank_name' => 'MB Bank',
                                'bank_logo' => 'https://upload.wikimedia.org/wikipedia/commons/2/25/Logo_MB_new.png',
                                'bank_status' => 'active',
                            ],
                            [
                                'bank_name' => 'Á Châu Bank',
                                'bank_logo' => 'https://cdn.haitrieu.com/wp-content/uploads/2022/01/Logo-ACB.png',
                                'bank_status' => 'active',
                            ],
                        ];
                    @endphp

                    @foreach ($arrListBank as $item)
                        <div
                            class="group flex w-full items-center gap-3 rounded-2xl border border-gray-300 bg-white/[0.06]
                    px-4 py-3 shadow-[0_8px_30px_rgba(15,23,42,0.08)] backdrop-blur-xl transition-all
                    duration-300 hover:-translate-y-0.5 hover:border-cyan-300/20 hover:bg-white/[0.08]">

                            {{-- icon logo --}}
                            <div class="flex h-11 w-11 items-center justify-center">
                                <img src="{{ $item['bank_logo'] }}" alt="{{ $item['bank_name'] }}"
                                    class="h-6 w-auto object-contain">
                            </div>

                            {{-- content --}}
                            <div class="flex flex-col leading-tight">
                                <span class="text-sm font-bold">
                                    {{ $item['bank_name'] }}
                                </span>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span class="relative flex h-2 w-2">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                                    </span>
                                    <span class="text-xs font-medium text-emerald-300">
                                        Đang hoạt động
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- badge thêm --}}
                <div class="mt-12 grid gap-3 grid-cols-2 xl:grid-cols-4">
                    @php
                        $arrBadgeBank = [
                            [
                                'html_icon' => '<i class="text-[1.5rem] text-green-500 bx bx-check-shield"></i>',
                                'html_text' => '<span>Mã hoá <b>AES-256</b></span>',
                            ],
                            [
                                'html_icon' => '<i class="text-[1.5rem] text-green-500 bx bx-lock-keyhole"></i>',
                                'html_text' => '<span><b>HTTPS</b> toàn bộ</span>',
                            ],
                            [
                                'html_icon' => '<i class="text-[1.5rem] text-green-500 bx bx-bolt-alt"></i>',
                                'html_text' => '<span>Webhook <b>HMAC-SHA256</b></span>',
                            ],
                            [
                                'html_icon' => '<i class="text-[1.5rem] text-green-500 bx bx-clock"></i>',
                                'html_text' => '<span>Chỉ <b>đọc biến động</b> — không can thiệp TK</span>',
                            ],
                        ];
                    @endphp

                    @foreach ($arrBadgeBank as $item)
                        <div class="flex items-center justify-center gap-1 text-sm">
                            {!! $item['html_icon'] !!}
                            {!! $item['html_text'] !!}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="mx-auto max-w-5xl px-4 pb-12 sm:px-6 lg:px-8">
        <div class="text-center md:px-[15rem]">
            <h2 class="font-mono-tech text-2xl uppercase tracking-[0.1em] font-bold text-sky-700">Tính Năng Nổi Bật</h2>
            <p class="font-tech mt-3 text-sm tracking-tight">
                Mọi thứ bạn cần để tích hợp API ngân hàng vào hệ thống của mình một cách nhanh chóng và an toàn.
            </p>
        </div>

        <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @php
                $arrAction = [
                    [
                        "icon" => '<i class="bx bx-code-alt"></i>',
                        "title" => "API Ngân Hàng",
                        "content" => "Kết nối trực tiếp với hệ thống ngân hàng qua API mạnh mẽ, ổn định."
                    ],
                    [
                        "icon" => '<i class="bx bx-plug-connect"></i>',
                        "title" => "Tích hợp nhanh chóng",
                        "content" => "API đơn giản, tài liệu chi tiết bằng tiếng Việt, hỗ trợ nhiều ngôn ngữ lập trình phổ biến.."
                    ],
                    [
                        "icon" => '<i class="bx bx-bolt"></i>',
                        "title" => "Webhook tức thì",
                        "content" => "Nhận thông báo giao dịch ngay lập tức qua webhook. Độ trễ dưới 1 giây, retry nếu url end lỗi 6 lần."
                    ],
                    [
                        "icon" => '<i class="bx bx-check-shield"></i>',
                        "title" => "Bảo Mật Cao",
                        "content" => "Mã hóa dữ liệu đầu cuối (E2E), bảo vệ thông tin tài khoản. Tuân thủ tiêu chuẩn bảo mật quốc tế."
                    ],
                    [
                        "icon" => '<i class="bx bx-cctv"></i>',
                        "title" => "Giám Sát 24/7",
                        "content" => "Theo dõi biến động số dư liên tục 24/7, không bỏ lỡ giao dịch. Dashboard trực quan, báo cáo chi tiết."
                    ],
                    [
                        "icon" => '<i class="bx bx-community"></i>',
                        "title" => "Hỗ Trợ Nhanh",
                        "content" => "Đội ngũ kỹ thuật chuyên nghiệp, phản hồi nhanh chóng qua chat, email và hotline. Hỗ trợ tiếng Việt."
                    ],
                    
                ];
            @endphp

            @foreach ($arrAction as $item)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm hover:translate-y-[-10px] cursor-pointer hover:border-green-500">
                    <div class="font-tech text-xl font-bold text-slate-950 flex items-center gap-2">
                        {!! $item['icon'] !!}
                        <span>{{ $item['title'] }}</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['content'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="pricing" class="mx-auto max-w-5xl px-4 pb-12 sm:px-6 lg:px-8">
         <div class="text-center md:px-[15rem]">
            <h2 class="font-mono-tech text-2xl uppercase tracking-[0.1em] font-bold text-sky-700">Bảng Giá Dịch Vụ</h2>
            <p class="font-tech mt-3 text-sm tracking-tight">
                Chọn gói phù hợp với nhu cầu của bạn. Tiết kiệm hơn khi đăng ký dài hạn.
            </p>
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-3">
            @php
                $listPackage = \App\Models\Package::get();
                // print_r($listPackage);
            @endphp

            @foreach ($listPackage as $item)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="font-tech text-2xl font-bold text-slate-950">{{ $item->name }}</div>
                    <p class="mt-2 text-sm text-slate-500">{{ $item->description }}</p>
                    <div class="mt-5 flex items-end gap-2">
                        <span class="font-tech text-4xl font-bold text-slate-950">{{ \App\Utils\Format::Cash($item->price) }}đ</span>
                        <span class="pb-1 text-sm text-slate-500">/ {{ \App\Utils\Format::Cash($item->duration_days) }}ngày</span>
                    </div>
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <ul class="space-y-2 text-sm text-slate-600">
                            @foreach ($item->features as $feature)
                                 <li>{{ $feature }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <a href="/register"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800">Bắt
                        đầu</a>
                </div>
            @endforeach

            {{-- <div class="rounded-[1.5rem] border border-sky-300 bg-sky-50 p-6 shadow-sm">
                <div class="font-tech text-2xl font-bold text-slate-950">Gói tháng</div>
                <p class="mt-2 text-sm text-slate-500">Phù hợp cho website đang vận hành ổn định</p>
                <div class="mt-5 flex items-end gap-2">
                    <span class="font-tech text-4xl font-bold text-slate-950">30.000đ</span>
                    <span class="pb-1 text-sm text-slate-500">/ tháng</span>
                </div>
                <div class="mt-5 rounded-2xl border border-sky-200 bg-white p-4">
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li>Gắn được 5 ngân hàng</li>
                        <li>Có thể mua thêm ngân hàng</li>
                        <li>Theo dõi lịch sử giao dịch tập trung</li>
                        <li>Hỗ trợ luồng cộng ví tự động</li>
                        <li>Phù hợp cho hệ thống nhỏ đến vừa</li>
                    </ul>
                </div>
                <a href="/register"
                    class="mt-5 inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white">Bắt
                    đầu</a>
            </div> --}}

        </div>
    </section>

    <section id="faq" class="mx-auto max-w-5xl px-4 pb-12 sm:px-6 lg:px-8">
        <div class="text-center md:px-[15rem]">
            <h2 class="font-mono-tech text-2xl uppercase tracking-[0.1em] font-bold text-sky-700">FAQ</h2>
            <p class="font-tech mt-3 text-sm tracking-tight">
                Một số câu hỏi thường gặp khi sử dụng tại hệ thống
            </p>
        </div>

        <div class="mt-8 grid gap-3">
            @php
                $listQuestion = [
                    [
                        "question" => "Hệ thống này phù hợp với ai?",
                        "answer" => "Hệ thống này phù hợp với những người có nhu cầu tích hợp nạp tự động qua ngân hàng bằng phương thức chuyển khoản."
                    ],
                    [
                        "question" => "Hệ thống phù hợp những nền tảng nào?",
                        "answer" => "Hệ thống bên mình có thể tích hợp cho các website, game, app,... liên quan đến thanh toán chuyển khoản online là bên mình đều sử dụng được."
                    ],
                    [
                        "question" => "Khi tích hợp thẻ, hệ thống có làm gì khác ngoài đọc giao dịch không?",
                        "answer" => "Hệ thống cam đoan 100% chỉ sử dụng để đọc biến động số dư không can thiệp đến những thứ khác của tài khoản khách hàng."
                    ],
                ];
            @endphp

            @foreach ($listQuestion as $item)
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-tech text-lg font-bold text-slate-950">{{ $item['question'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['answer'] }}</p>
                </div>
            @endforeach
            
        </div>
    </section>

    <section class="mx-auto max-w-5xl px-4 pb-14 sm:px-6 lg:px-8">
        <div
            class="rounded-[2rem] border border-slate-200 bg-slate-950 px-6 py-10 text-center text-white shadow-[0_24px_80px_rgba(15,23,42,0.2)] lg:px-10">
            <p class="font-mono-tech text-sm uppercase tracking-[0.3em] text-sky-300">Bạn đã sẵn sàng?</p>
            <h2 class="font-tech mt-4 text-2xl font-bold tracking-tight sm:text-3xl">
                Đăng ký tài khoản và trải nghiệm hệ thống ngay thôi.
            </h2>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('auth.register') }}"
                    class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-950 transition hover:bg-sky-100">Tạo
                    tài khoản</a>
                <a href="{{ route('auth.login') }}"
                    class="inline-flex items-center justify-center rounded-full border border-white/20 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/5">Đăng
                    nhập hệ thống</a>
            </div>
        </div>
    </section>
@endsection
