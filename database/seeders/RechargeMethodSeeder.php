<?php

namespace Database\Seeders;

use App\Models\RechargeMethod;
use Illuminate\Database\Seeder;

class RechargeMethodSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array<string, mixed>> $methods */
        $methods = config('recharge.methods', []);

        $sortOrder = 0;

        foreach ($methods as $code => $method) {
            RechargeMethod::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => (string) ($method['label'] ?? $code),
                    'description' => $method['description'] ?? null,
                    'badge_label' => $method['badge_label'] ?? null,
                    'badge_type' => $method['badge_type'] ?? 'auto',
                    'bank_name' => $method['bank_name'] ?? null,
                    'account_number' => $method['account_number'] ?? null,
                    'account_name' => $method['account_name'] ?? null,
                    'min_amount' => (float) config('recharge.minimum_amount', 50_000),
                    'max_amount' => (float) config('recharge.maximum_amount', 100_000_000),
                    'bonus_percentage' => (int) config('recharge.bonus_percentage', 0),
                    'sort_order' => $sortOrder,
                    'is_active' => (bool) ($method['active'] ?? true),
                    'metadata' => [],
                ],
            );

            $sortOrder++;
        }
    }
}
