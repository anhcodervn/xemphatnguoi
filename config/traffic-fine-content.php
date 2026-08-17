<?php

return [
    'sitemap_route_names' => [
        'traffic-fines.home',
        'traffic-fines.lookup.car',
        'traffic-fines.lookup.motorbike',
        'traffic-fines.lookup.electric-motorbike',
        'traffic-fines.pricing',
        'partners.api',
        'traffic-fines.penalties.index',
        'traffic-fines.penalties.red-light',
        'traffic-fines.penalties.speeding',
        'traffic-fines.penalties.wrong-lane',
        'traffic-fines.penalties.wrong-way',
        'traffic-fines.penalties.parking',
        'traffic-fines.penalties.signs',
        'traffic-fines.knowledge.what-is',
        'traffic-fines.knowledge.guide',
        'seo.index',
    ],

    'topics' => [
        'car-lookup' => [
            'eyebrow' => 'Tra cứu theo phương tiện',
            'title' => 'Tra cứu phạt nguội ô tô',
            'description' => 'Kiểm tra dữ liệu vi phạm giao thông của ô tô theo biển số và đọc các thông tin cần đối chiếu.',
            'vehicle_type' => 'car',
            'sections' => [
                [
                    'heading' => 'Kiểm tra biển số ô tô đúng cách',
                    'paragraphs' => [
                        'Nhập đầy đủ phần mã tỉnh, chữ cái và dãy số trên biển đăng ký. Hệ thống chấp nhận cách viết có dấu gạch nối, dấu chấm hoặc viết liền, sau đó chuẩn hóa trước khi gửi yêu cầu tra cứu.',
                        'Khi có kết quả, hãy đối chiếu biển số, thời gian, địa điểm, hành vi và trạng thái xử lý. Dữ liệu trực tuyến có thể cập nhật theo quy trình của từng đơn vị nên kết quả trên website mang tính hỗ trợ tại thời điểm kiểm tra.',
                    ],
                ],
            ],
            'related_routes' => [
                ['route' => 'traffic-fines.penalties.index', 'label' => 'Xem mức phạt giao thông thường gặp'],
                ['route' => 'traffic-fines.knowledge.guide', 'label' => 'Đọc hướng dẫn tra cứu từng bước'],
            ],
        ],
        'motorbike-lookup' => [
            'eyebrow' => 'Tra cứu theo phương tiện',
            'title' => 'Tra cứu phạt nguội xe máy',
            'description' => 'Kiểm tra biển số xe máy hoặc xe máy điện và xem dữ liệu vi phạm hiện có tại thời điểm tra cứu.',
            'vehicle_type' => 'motorbike',
            'sections' => [
                [
                    'heading' => 'Lưu ý khi tra cứu xe máy',
                    'paragraphs' => [
                        'Hãy chọn đúng loại phương tiện và nhập biển số như trên giấy đăng ký xe. Bạn có thể dùng dấu chấm, gạch nối hoặc viết liền; hệ thống sẽ chuẩn hóa định dạng trước khi thực hiện tra cứu.',
                        'Phạm vi dữ liệu xe máy có thể khác nhau theo nguồn và thời điểm cập nhật. Nếu phát hiện thông tin cần xử lý, người dùng nên kiểm tra lại với cơ quan có thẩm quyền thay vì chỉ dựa vào một lần tra cứu trực tuyến.',
                    ],
                ],
            ],
            'related_routes' => [
                ['route' => 'traffic-fines.penalties.index', 'label' => 'Tra cứu nhóm lỗi và mức phạt'],
                ['route' => 'traffic-fines.knowledge.what-is', 'label' => 'Tìm hiểu phạt nguội là gì'],
            ],
        ],
        'electric-motorbike-lookup' => [
            'eyebrow' => 'Tra cứu theo phương tiện',
            'title' => 'Tra cứu phạt nguội xe máy điện',
            'description' => 'Kiểm tra biển số xe máy điện và xem dữ liệu vi phạm hiện có theo đúng nhóm phương tiện.',
            'vehicle_type' => 'electric_motorbike',
            'sections' => [
                [
                    'heading' => 'Lưu ý khi tra cứu xe máy điện',
                    'paragraphs' => [
                        'Hãy chọn đúng loại xe máy điện và nhập biển số như trên giấy đăng ký. Hệ thống chấp nhận biển số có dấu phân cách hoặc viết liền rồi chuẩn hóa trước khi gửi yêu cầu tra cứu.',
                        'Kết quả phản ánh dữ liệu nguồn tại thời điểm kiểm tra. Nếu phát hiện thông tin cần xử lý hoặc có khác biệt với thực tế, người dùng nên đối chiếu với cơ quan có thẩm quyền.',
                    ],
                ],
            ],
            'related_routes' => [
                ['route' => 'traffic-fines.penalties.index', 'label' => 'Tra cứu nhóm lỗi và mức phạt'],
                ['route' => 'traffic-fines.knowledge.guide', 'label' => 'Đọc hướng dẫn tra cứu từng bước'],
            ],
        ],
        'penalties' => [
            'eyebrow' => 'Kiến thức giao thông',
            'title' => 'Mức phạt giao thông và các lỗi thường gặp',
            'description' => 'Tổng hợp nhóm hành vi thường được ghi nhận qua hệ thống giám sát để người đọc biết nội dung cần đối chiếu.',
            'sections' => [
                [
                    'heading' => 'Tra cứu theo hành vi vi phạm',
                    'paragraphs' => [
                        'Mức xử lý có thể phụ thuộc loại phương tiện, tình tiết cụ thể và quy định có hiệu lực tại thời điểm vi phạm. Vì vậy, nội dung trên website tập trung giải thích hành vi và cách đọc kết quả, không thay thế quyết định của cơ quan có thẩm quyền.',
                        'Chọn một nhóm lỗi bên dưới để xem thông tin nền, sau đó đối chiếu với văn bản và hướng dẫn chính thức nếu bạn cần xác định mức xử lý áp dụng cho trường hợp cụ thể.',
                    ],
                ],
            ],
            'related_routes' => [
                ['route' => 'traffic-fines.penalties.red-light', 'label' => 'Lỗi vượt đèn đỏ'],
                ['route' => 'traffic-fines.penalties.speeding', 'label' => 'Lỗi chạy quá tốc độ'],
                ['route' => 'traffic-fines.penalties.wrong-lane', 'label' => 'Lỗi đi sai làn đường'],
                ['route' => 'traffic-fines.penalties.wrong-way', 'label' => 'Lỗi đi ngược chiều'],
                ['route' => 'traffic-fines.penalties.parking', 'label' => 'Lỗi dừng đỗ sai quy định'],
                ['route' => 'traffic-fines.penalties.signs', 'label' => 'Lỗi không chấp hành biển báo'],
            ],
        ],
        'red-light' => [
            'eyebrow' => 'Nhóm lỗi phổ biến',
            'title' => 'Lỗi vượt đèn đỏ',
            'description' => 'Cách nhận biết thông tin về hành vi không chấp hành tín hiệu đèn giao thông trong kết quả phạt nguội.',
            'sections' => [[
                'heading' => 'Thông tin cần đối chiếu',
                'paragraphs' => [
                    'Camera giao thông có thể ghi nhận phương tiện di chuyển qua vạch dừng khi tín hiệu không cho phép. Khi xem kết quả, hãy kiểm tra kỹ biển số, thời gian, vị trí, hướng di chuyển và đơn vị phát hiện.',
                    'Không nên suy luận mức phạt chỉ từ tên lỗi rút gọn. Hãy dùng nội dung tra cứu làm đầu mối và xác nhận hồ sơ cụ thể với cơ quan có thẩm quyền khi cần.',
                ],
            ]],
            'related_routes' => [['route' => 'traffic-fines.penalties.index', 'label' => 'Xem tất cả nhóm lỗi giao thông']],
        ],
        'speeding' => [
            'eyebrow' => 'Nhóm lỗi phổ biến',
            'title' => 'Lỗi chạy quá tốc độ',
            'description' => 'Những dữ liệu cần kiểm tra khi phương tiện bị ghi nhận chạy quá tốc độ cho phép.',
            'sections' => [[
                'heading' => 'Đọc kết quả vi phạm tốc độ',
                'paragraphs' => [
                    'Kết quả có thể thể hiện thời gian, địa điểm và mô tả hành vi. Mức độ vượt quá giới hạn, loại đường, biển báo và loại phương tiện là những yếu tố cần được đối chiếu từ hồ sơ chính thức.',
                    'Nếu thông tin trực tuyến chưa có đủ chi tiết, người dùng nên liên hệ đơn vị xử lý được ghi trong kết quả thay vì tự ước tính mức xử lý.',
                ],
            ]],
            'related_routes' => [['route' => 'traffic-fines.penalties.index', 'label' => 'Xem tất cả nhóm lỗi giao thông']],
        ],
        'wrong-lane' => [
            'eyebrow' => 'Nhóm lỗi phổ biến',
            'title' => 'Lỗi đi sai làn đường',
            'description' => 'Phân biệt thông tin cơ bản về làn đường và cách đối chiếu kết quả được camera giao thông ghi nhận.',
            'sections' => [[
                'heading' => 'Kiểm tra bối cảnh ghi nhận',
                'paragraphs' => [
                    'Khi thấy mô tả đi sai làn, cần xem vị trí, hướng lưu thông, biển báo hoặc vạch kẻ đường tại thời điểm được ghi nhận. Tên hành vi trên màn hình tra cứu có thể là mô tả rút gọn từ dữ liệu nguồn.',
                    'Việc xác định hành vi và mức xử lý cuối cùng thuộc hồ sơ của cơ quan có thẩm quyền. Website chỉ hỗ trợ tìm kiếm và trình bày dữ liệu hiện có theo biển số.',
                ],
            ]],
            'related_routes' => [['route' => 'traffic-fines.penalties.index', 'label' => 'Xem tất cả nhóm lỗi giao thông']],
        ],
        'wrong-way' => [
            'eyebrow' => 'Nhóm lỗi phổ biến',
            'title' => 'Lỗi đi ngược chiều',
            'description' => 'Các trường thông tin nên kiểm tra khi kết quả ghi nhận phương tiện đi ngược chiều.',
            'sections' => [[
                'heading' => 'Đối chiếu vị trí và hướng di chuyển',
                'paragraphs' => [
                    'Người dùng nên xem đầy đủ thời gian, tuyến đường, hướng di chuyển và mô tả hành vi. Một ảnh hoặc tên lỗi rút gọn chưa đủ để kết luận toàn bộ bối cảnh.',
                    'Khi cần xác nhận, hãy làm theo hướng dẫn của đơn vị xử lý hiển thị trong kết quả hoặc liên hệ cơ quan có thẩm quyền.',
                ],
            ]],
            'related_routes' => [['route' => 'traffic-fines.penalties.index', 'label' => 'Xem tất cả nhóm lỗi giao thông']],
        ],
        'parking' => [
            'eyebrow' => 'Nhóm lỗi phổ biến',
            'title' => 'Lỗi dừng đỗ sai quy định',
            'description' => 'Cách kiểm tra dữ liệu về vị trí, thời điểm và hành vi dừng hoặc đỗ phương tiện.',
            'sections' => [[
                'heading' => 'Thông tin nên kiểm tra',
                'paragraphs' => [
                    'Vị trí dừng đỗ, biển báo, vạch kẻ đường và thời điểm ghi nhận là các dữ kiện quan trọng. Hãy đọc đầy đủ thông tin thay vì chỉ dựa vào tên lỗi.',
                    'Nếu dữ liệu chưa rõ hoặc có khác biệt với thực tế, người dùng nên lưu lại kết quả và liên hệ đơn vị có thẩm quyền để được hướng dẫn.',
                ],
            ]],
            'related_routes' => [['route' => 'traffic-fines.penalties.index', 'label' => 'Xem tất cả nhóm lỗi giao thông']],
        ],
        'signs' => [
            'eyebrow' => 'Nhóm lỗi phổ biến',
            'title' => 'Lỗi không chấp hành biển báo',
            'description' => 'Những nội dung cần đối chiếu khi dữ liệu ghi nhận phương tiện không tuân thủ biển báo giao thông.',
            'sections' => [[
                'heading' => 'Đọc đúng mô tả hành vi',
                'paragraphs' => [
                    'Cùng một tuyến đường có thể có nhiều biển báo và phạm vi hiệu lực khác nhau. Kết quả tra cứu nên được xem cùng vị trí, hướng đi, thời gian và thông tin do đơn vị phát hiện cung cấp.',
                    'Website không tự xác định hiệu lực biển báo hoặc đưa ra kết luận pháp lý cho từng trường hợp. Khi cần, hãy đối chiếu với nguồn chính thức.',
                ],
            ]],
            'related_routes' => [['route' => 'traffic-fines.penalties.index', 'label' => 'Xem tất cả nhóm lỗi giao thông']],
        ],
        'what-is' => [
            'eyebrow' => 'Kiến thức nền',
            'title' => 'Phạt nguội là gì?',
            'description' => 'Giải thích cách vi phạm được ghi nhận, xác minh và tra cứu theo biển số phương tiện.',
            'sections' => [[
                'heading' => 'Hiểu đúng về phạt nguội',
                'paragraphs' => [
                    'Phạt nguội là cách gọi phổ biến đối với trường hợp hành vi vi phạm được phát hiện qua thiết bị kỹ thuật, hình ảnh hoặc nguồn dữ liệu khác thay vì xử lý trực tiếp ngay tại thời điểm xảy ra. Thông tin ghi nhận cần trải qua quá trình kiểm tra và xử lý của đơn vị có thẩm quyền.',
                    'Người dùng có thể nhập biển số và chọn loại phương tiện để tìm dữ liệu hiện có. Việc kiểm tra định kỳ giúp chủ phương tiện phát hiện thông tin cần đối chiếu, đặc biệt trước khi thực hiện các thủ tục liên quan đến xe.',
                ],
            ]],
            'related_routes' => [
                ['route' => 'traffic-fines.home', 'label' => 'Tra cứu phạt nguội theo biển số'],
                ['route' => 'traffic-fines.knowledge.guide', 'label' => 'Hướng dẫn cách tra cứu phạt nguội'],
            ],
        ],
    ],
];
