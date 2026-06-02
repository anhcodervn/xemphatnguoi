@include('errors.partials.page', [
    'statusCode' => 520,
    'eyebrow' => 'Unexpected Error',
    'headline' => 'Hệ thống gặp lỗi xử lý ngoài dự kiến',
    'description' => 'Yêu cầu đã đến máy chủ nhưng quá trình xử lý phát sinh lỗi nội bộ. Đội kỹ thuật có thể cần kiểm tra thêm log và trạng thái dịch vụ phụ trợ.',
    'helpText' => 'Lỗi 520 thường xuất hiện khi hệ thống trả về phản hồi không hợp lệ hoặc downstream service hoạt động không ổn định.',
    'hints' => [
        'Thử tải lại trang sau ít phút để kiểm tra xem lỗi còn lặp lại hay không.',
        'Nếu đây là thao tác quan trọng, không nên gửi lại quá nhiều lần liên tiếp để tránh trùng xử lý.',
        'Khi liên hệ hỗ trợ, hãy cung cấp thêm thao tác vừa thực hiện và thời điểm phát sinh lỗi.',
    ],
])
