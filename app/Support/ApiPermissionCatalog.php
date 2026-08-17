<?php

namespace App\Support;

class ApiPermissionCatalog
{
    /**
     * @return list<array{
     *     key: string,
     *     group: string,
     *     version: string,
     *     label: string,
     *     description: string,
     *     endpoints: list<string>,
     *     self_service: bool
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'traffic-fines.lookup',
                'group' => 'traffic-fines',
                'version' => 'v1',
                'label' => 'Tra cứu phạt nguội',
                'description' => 'Tra cứu phạt nguội theo biển số và loại phương tiện.',
                'endpoints' => [
                    'GET /api/v1/lookup',
                ],
                'self_service' => true,
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function keyed(): array
    {
        return collect(self::all())
            ->keyBy('key')
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_values(array_map(static fn (array $permission): string => $permission['key'], self::all()));
    }
}
