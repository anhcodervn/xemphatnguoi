<?php

namespace App\Support;

class ApiPermissionCatalog
{
    public static function all(): array
    {
        return [
            [
                'key' => 'captcha-services.read',
                'group' => 'captcha',
                'version' => 'v1',
                'label' => 'Xem dịch vụ captcha',
                'description' => 'Đọc danh sách dịch vụ captcha đang được hệ thống cung cấp.',
                'endpoints' => [
                    'GET /api/v1/services',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'captcha-tasks.create',
                'group' => 'captcha',
                'version' => 'v1',
                'label' => 'Tạo yêu cầu giải captcha',
                'description' => 'Gửi yêu cầu giải captcha mới qua API.',
                'endpoints' => [
                    'POST /api/v1/create',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'captcha-tasks.read',
                'group' => 'captcha',
                'version' => 'v1',
                'label' => 'Xem trạng thái yêu cầu',
                'description' => 'Đọc thông tin tài khoản, số dư và kết quả task captcha đã gửi.',
                'endpoints' => [
                    'GET /api/v1/user',
                    'GET /api/v1/balance',
                    'POST /api/v1/result',
                ],
                'self_service' => true,
            ],
        ];
    }

    public static function keyed(): array
    {
        return collect(self::all())
            ->keyBy('key')
            ->all();
    }

    public static function keys(): array
    {
        return array_values(array_map(static fn (array $permission): string => $permission['key'], self::all()));
    }
}
