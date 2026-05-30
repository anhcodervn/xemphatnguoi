<?php

namespace App\Support;

use App\Models\BankAccount;
use App\Models\RechargeMethod;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RechargeMethodCatalog
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function methods(): Collection
    {
        $databaseMethods = RechargeMethod::query()
            ->with([
                'bankAccounts' => fn ($query) => $query
                    ->where('bank_accounts.status', 'active')
                    ->wherePivot('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (RechargeMethod $method): array => $this->mapDatabaseMethod($method))
            ->values();

        if ($databaseMethods->isNotEmpty()) {
            return $databaseMethods;
        }

        /** @var array<string, array<string, mixed>> $fallbackMethods */
        $fallbackMethods = config('recharge.methods', []);

        return collect($fallbackMethods)
            ->map(fn (array $method, string $key): array => $this->mapConfigMethod($key, $method))
            ->values();
    }

    /**
     * @return list<string>
     */
    public function activeMethodKeys(): array
    {
        return $this->methods()
            ->filter(fn (array $method): bool => (bool) $method['active'] && $this->hasDestinationAccount($method))
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $key): ?array
    {
        return $this->methods()
            ->first(fn (array $method): bool => $method['key'] === $key && (bool) $method['active'] && $this->hasDestinationAccount($method));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDatabaseMethod(RechargeMethod $method): array
    {
        /** @var BankAccount|null $bankAccount */
        $bankAccount = $method->bankAccounts->first();

        return [
            'source' => 'database',
            'key' => $method->code,
            'active' => $method->is_active,
            'label' => $method->name,
            'description' => $method->description,
            'badge_label' => $method->badge_label,
            'badge_type' => $method->badge_type,
            'bank_name' => $bankAccount?->bank_name ?? $method->bank_name,
            'account_number' => $bankAccount?->account_number ?? $method->account_number,
            'account_name' => $bankAccount?->account_name ?? $method->account_name,
            'minimum_amount' => (float) $method->min_amount,
            'maximum_amount' => (float) $method->max_amount,
            'bonus_percentage' => $method->bonus_percentage,
            'recharge_method_id' => $method->id,
            'bank_account_id' => $bankAccount?->id,
            'metadata' => $method->metadata ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $method
     * @return array<string, mixed>
     */
    private function mapConfigMethod(string $key, array $method): array
    {
        return [
            'source' => 'config',
            'key' => $key,
            'active' => (bool) ($method['active'] ?? true),
            'label' => $method['label'] ?? Str::title($key),
            'description' => $method['description'] ?? null,
            'badge_label' => $method['badge_label'] ?? null,
            'badge_type' => $method['badge_type'] ?? null,
            'bank_name' => $method['bank_name'] ?? null,
            'account_number' => $method['account_number'] ?? null,
            'account_name' => $method['account_name'] ?? null,
            'minimum_amount' => (float) config('recharge.minimum_amount', 50_000),
            'maximum_amount' => (float) config('recharge.maximum_amount', 100_000_000),
            'bonus_percentage' => (int) config('recharge.bonus_percentage', 0),
            'recharge_method_id' => null,
            'bank_account_id' => null,
            'metadata' => Arr::only($method, ['description', 'badge_label', 'badge_type']),
        ];
    }

    /**
     * @param array<string, mixed> $method
     */
    private function hasDestinationAccount(array $method): bool
    {
        return filled($method['bank_name'] ?? null)
            && filled($method['account_number'] ?? null)
            && filled($method['account_name'] ?? null);
    }
}
