@include('errors.partials.page', [
    'statusCode' => 524,
    'eyebrow' => 'Request Timeout',
    'headline' => 'Yêu cầu xử lý quá lâu nên đã hết thời gian chờ',
    'description' => 'Hệ thống hoặc một dịch vụ liên quan đã phản hồi chậm hơn ngưỡng cho phép. Trạng thái này thường chỉ mang tính tạm thời.',
    'helpText' => 'Lỗi 524 thường xảy ra khi máy chủ vẫn đang xử lý nhưng upstream proxy đã ngắt kết nối do timeout.',
    'hints' => [
        'Chờ trong giây lát rồi thử lại thao tác sau.',
        'Với tác vụ nền như đồng bộ giao dịch hoặc queue, hãy kiểm tra lại trạng thái hệ thống trước khi thao tác tiếp.',
        'Nếu lỗi lặp lại nhiều lần, nên kiểm tra queue, webhook hoặc dịch vụ ngân hàng đang kết nối.',
    ],
])
