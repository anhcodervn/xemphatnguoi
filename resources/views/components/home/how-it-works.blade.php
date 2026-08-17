<section id="huong-dan-tra-cuu" class="scroll-mt-24 border-y border-slate-200 bg-slate-50" aria-labelledby="how-it-works-title">
    <div class="site-container site-section">
        <p class="site-eyebrow">Hướng dẫn tra cứu</p>
        <h2 id="how-it-works-title" class="site-section-title">Chỉ 3 bước để tra cứu phạt nguội</h2>

        <ol class="mt-5 grid gap-3 md:grid-cols-3">
            @foreach ([
                ['Nhập biển số xe', 'Điền biển số như trên giấy đăng ký, có thể viết liền hoặc dùng dấu phân cách.'],
                ['Chọn loại phương tiện', 'Chọn đúng ô tô, xe máy hoặc xe máy điện trước khi gửi yêu cầu.'],
                ['Xem kết quả', 'Đọc số lỗi và các thông tin cần đối chiếu trong kết quả trả về.'],
            ] as [$title, $description])
                <li class="flex gap-3 rounded-lg border border-slate-200 bg-white p-4">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-extrabold text-white" aria-hidden="true">{{ $loop->iteration }}</span>
                    <div>
                        <h3 class="text-sm font-bold text-navy">{{ $title }}</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ $description }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <a href="{{ route('traffic-fines.knowledge.guide') }}" class="site-focus mt-3 inline-flex min-h-11 items-center gap-2 text-xs font-bold text-brand hover:text-sky-800">Xem hướng dẫn đầy đủ <span aria-hidden="true">→</span></a>
    </div>
</section>
