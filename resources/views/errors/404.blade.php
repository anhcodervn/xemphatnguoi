@include('errors.partials.page', [
    'statusCode' => 404,
    'eyebrow' => 'Not Found',
    'headline' => 'Không tìm thấy trang bạn đang truy cập',
    'description' => 'Liên kết có thể đã thay đổi, bị gỡ bỏ hoặc đường dẫn bạn nhập không còn hợp lệ trong hệ thống.',
    'helpText' => 'Đây thường là lỗi đường dẫn sai hoặc trang đã được di chuyển sang vị trí mới.',
    'hints' => [
        'Kiểm tra lại đường dẫn hoặc mở lại từ menu điều hướng chính.',
        'Nếu bạn đang ở trong dashboard, hãy quay về trang tổng quan rồi truy cập lại chức năng cần dùng.',
        'Nếu liên kết đến từ email hoặc tài liệu cũ, hãy cập nhật lại URL mới nhất.',
    ],
])
