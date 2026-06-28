<?php

namespace App\Support;

class ApiPermissionCatalog
{
    /**
     * @return array<int, array{
     *     key:string,
     *     group:string,
     *     version:string,
     *     label:string,
     *     description:string,
     *     endpoints:array<int, string>,
     *     self_service:bool
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'cron-jobs.read',
                'group' => 'cron',
                'version' => 'v1',
                'label' => 'Xem cron jobs',
                'description' => 'Đọc danh sách cron job và chi tiết từng cron job.',
                'endpoints' => [
                    'GET /api/v1/cron-jobs',
                    'GET /api/v1/cron-jobs/{cron_job}',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'cron-jobs.write',
                'group' => 'cron',
                'version' => 'v1',
                'label' => 'Quản lý cron jobs',
                'description' => 'Tạo, cập nhật, xóa, pause và resume cron job.',
                'endpoints' => [
                    'POST /api/v1/cron-jobs',
                    'PATCH /api/v1/cron-jobs/{cron_job}',
                    'DELETE /api/v1/cron-jobs/{cron_job}',
                    'POST /api/v1/cron-jobs/{cron_job}/pause',
                    'POST /api/v1/cron-jobs/{cron_job}/resume',
                ],
                'self_service' => true,
            ],
            [
                'key' => 'cron-logs.read',
                'group' => 'cron',
                'version' => 'v1',
                'label' => 'Xem cron logs',
                'description' => 'Đọc log theo từng cron job.',
                'endpoints' => [
                    'GET /api/v1/cron-jobs/{cron_job}/logs',
                ],
                'self_service' => true,
            ],
        ];
    }

    /**
     * @return array<string, array{
     *     key:string,
     *     group:string,
     *     version:string,
     *     label:string,
     *     description:string,
     *     endpoints:array<int, string>,
     *     self_service:bool
     * }>
     */
    public static function keyed(): array
    {
        return collect(self::all())
            ->keyBy('key')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_values(array_map(static fn (array $permission): string => $permission['key'], self::all()));
    }
}
