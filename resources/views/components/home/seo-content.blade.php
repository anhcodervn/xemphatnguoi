<section id="phat-nguoi-la-gi" class="scroll-mt-24 bg-white" aria-labelledby="what-is-title">
    <div class="site-container site-section">
        <p class="site-eyebrow">Kiến thức cần biết</p>
        <h2 id="what-is-title" class="site-section-title">Tra cứu phạt nguội là gì?</h2>

        <div class="mt-4 grid items-start gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(260px,0.75fr)] lg:gap-7">
            <div class="grid max-w-3xl gap-3 text-sm leading-6 text-slate-700 sm:text-[15px]">
                <p><strong class="font-semibold text-navy">Phạt nguội</strong> là cách gọi phổ biến khi hành vi vi phạm giao thông được ghi nhận qua camera, thiết bị kỹ thuật, hình ảnh hoặc nguồn dữ liệu khác thay vì được xử lý trực tiếp ngay tại thời điểm xảy ra. Thông tin ban đầu thường cần được đơn vị có thẩm quyền kiểm tra, xác minh phương tiện và hoàn thiện trước khi đưa vào quy trình xử lý.</p>
                <p>Tra cứu trực tuyến giúp chủ phương tiện dùng <strong class="font-semibold text-navy">biển số xe</strong> để kiểm tra dữ liệu hiện có. Kết quả có thể bao gồm loại phương tiện, số lỗi, thời gian, địa điểm, hành vi được ghi nhận, đơn vị phát hiện và trạng thái xử lý. Việc trình bày các trường này theo một cấu trúc rõ ràng giúp người dùng biết chính xác thông tin nào cần đối chiếu.</p>
                <p>Dữ liệu có thể được ghi nhận từ hệ thống camera giao thông, thiết bị nghiệp vụ hoặc nguồn do đơn vị xử lý cung cấp. Vì quá trình xác minh và đồng bộ cần thời gian, thông báo “chưa ghi nhận vi phạm” chỉ phản ánh dữ liệu tại thời điểm tra cứu, không phải xác nhận chính thức rằng phương tiện chắc chắn không có vi phạm.</p>
                <p>Người dùng nên kiểm tra định kỳ, đặc biệt khi xe được nhiều người sử dụng hoặc trước các thủ tục liên quan đến phương tiện. Khi phát hiện thông tin cần xử lý, hãy đọc đầy đủ nội dung kết quả và làm việc với cơ quan có thẩm quyền nếu cần xác nhận chính thức.</p>
                <a href="{{ route('traffic-fines.knowledge.what-is') }}" class="site-focus inline-flex min-h-11 w-fit items-center gap-2 text-sm font-bold text-brand hover:text-sky-800">Tìm hiểu chi tiết phạt nguội là gì <span aria-hidden="true">→</span></a>
            </div>

            <div class="rounded-lg border border-sky-100 bg-sky-50/60 p-4" aria-label="Lợi ích của tra cứu phạt nguội">
                <div class="flex items-center gap-2.5">
                    <span data-home-accent-icon class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-brand shadow-sm" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M12 3 19 6v5c0 4.5-3 7.8-7 10-4-2.2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-5"/></svg></span>
                    <h3 class="text-sm font-bold text-navy">Tra cứu phạt nguội giúp bạn</h3>
                </div>
                <ul class="mt-3 grid gap-2 text-xs leading-5 text-slate-700">
                    @foreach ([
                        'Kiểm tra vi phạm theo biển số xe',
                        'Xem thời gian và địa điểm vi phạm',
                        'Biết hành vi được ghi nhận',
                        'Kiểm tra đơn vị phát hiện',
                        'Theo dõi trạng thái xử lý',
                    ] as $benefit)
                        <li class="flex gap-2.5"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" class="mt-1 h-4 w-4 shrink-0 text-brand"><path d="m4 10 4 4 8-8"/></svg><span>{{ $benefit }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
