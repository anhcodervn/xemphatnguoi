<?php

namespace App\Support;

class ApiPermissionCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key' => '*',
                'group' => 'admin',
                'version' => 'global',
                'label' => 'Full access',
                'description' => 'Allows this API key to call every published API endpoint.',
                'endpoints' => ['*'],
                'self_service' => false,
            ],
            [
                'key' => 'profile.read',
                'group' => 'profile',
                'version' => 'v1',
                'label' => 'Read profile',
                'description' => 'Read basic account profile, wallet, and subscription summary.',
                'endpoints' => ['GET /api/v1/me'],
                'self_service' => true,
            ],
            [
                'key' => 'bank-accounts.read',
                'group' => 'bank',
                'version' => 'v1',
                'label' => 'List bank accounts',
                'description' => 'Read the bank accounts that are available for transaction synchronization.',
                'endpoints' => ['GET /api/v1/list-bank-accounts'],
                'self_service' => true,
            ],
            [
                'key' => 'transactions.read',
                'group' => 'bank',
                'version' => 'v1',
                'label' => 'Read bank transactions',
                'description' => 'Read and synchronize transactions for a specific bank account by bank_id.',
                'endpoints' => ['POST /api/v1/transactions'],
                'self_service' => true,
            ],
            [
                'key' => 'recharge.create',
                'group' => 'recharge',
                'version' => 'v1',
                'label' => 'Create recharge order',
                'description' => 'Create a partner recharge order on this system and wait for bank transfer matching.',
                'endpoints' => ['POST /api/v1/recharge-orders'],
                'self_service' => true,
            ],
            [
                'key' => 'recharge.read',
                'group' => 'recharge',
                'version' => 'v1',
                'label' => 'Read recharge order',
                'description' => 'Read partner recharge order status, payment instructions, and transfer content.',
                'endpoints' => ['GET /api/v1/recharge-orders/{orderCode}'],
                'self_service' => true,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_values(array_map(
            static fn (array $permission): string => (string) $permission['key'],
            self::all(),
        ));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function map(): array
    {
        $permissions = [];

        foreach (self::all() as $permission) {
            $permissions[(string) $permission['key']] = $permission;
        }

        return $permissions;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function selfService(): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (array $permission): bool => (bool) ($permission['self_service'] ?? false),
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function selfServiceKeys(): array
    {
        return array_values(array_map(
            static fn (array $permission): string => (string) $permission['key'],
            self::selfService(),
        ));
    }

    /**
     * @param  array<int, string>|null  $keys
     * @return array<int, array<string, mixed>>
     */
    public static function resolve(?array $keys): array
    {
        if ($keys === null || $keys === []) {
            return [];
        }

        $map = self::map();

        return array_values(array_filter(
            array_map(
                static fn (string $key): ?array => $map[$key] ?? null,
                $keys,
            ),
        ));
    }
}
