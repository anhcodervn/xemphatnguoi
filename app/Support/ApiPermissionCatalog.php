<?php

namespace App\Support;

class ApiPermissionCatalog
{
    public static function all(): array
    {
        return [
            [
                'key' => 'proxy-products.read',
                'group' => 'proxy',
                'version' => 'v1',
                'label' => 'Xem sản phẩm proxy',
                'description' => 'Đọc danh sách sản phẩm proxy đang mở bán.',
                'endpoints' => [
                    'GET /api/v1/proxy/products',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'proxy-orders.create',
                'group' => 'proxy',
                'version' => 'v1',
                'label' => 'Mua proxy',
                'description' => 'Tạo đơn mua proxy mới và trừ tiền từ ví người dùng.',
                'endpoints' => [
                    'POST /api/v1/proxy/orders',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'proxy-operations.write',
                'group' => 'proxy',
                'version' => 'v1',
                'label' => 'Đổi và gia hạn proxy',
                'description' => 'Tạo tác vụ đổi hoặc gia hạn proxy thuộc tài khoản.',
                'endpoints' => [
                    'POST /api/v1/proxy/change',
                    'POST /api/v1/proxy/renew',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'proxy-rotating.read',
                'group' => 'proxy',
                'version' => 'v1',
                'label' => 'Lấy proxy xoay',
                'description' => 'Lấy endpoint proxy hiện tại từ key xoay thuộc tài khoản.',
                'endpoints' => [
                    'POST /api/v1/proxy/rotating',
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
