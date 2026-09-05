<?php

namespace App\Features\TrafficFine\Services;

use App\Models\TrafficFineProvider;
use App\Support\SettingStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TrafficFineProviderSettingsService
{
    public const ACTIVE_PROVIDER_KEY = 'traffic_fine_active_provider';

    /** @var list<string> */
    private const EDITABLE_FIELDS = [
        'url',
        'timeout',
        'connect_timeout',
        'retry_times',
        'retry_sleep_ms',
    ];

    /** @var Collection<int, TrafficFineProvider>|null */
    private ?Collection $storedProviders = null;

    private bool $activeProviderResolved = false;

    private string $resolvedActiveProvider = '';

    public function __construct(private readonly SettingStore $settingStore) {}

    /** @return list<string> */
    public function providerNames(): array
    {
        return collect(array_keys((array) config('traffic-fines.sources', [])))
            ->merge($this->storedProviders()->pluck('name'))
            ->filter(fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->unique()
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function driverNames(): array
    {
        return collect(array_keys((array) config('traffic-fines.sources', [])))
            ->filter(fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->values()
            ->all();
    }

    public function activeName(): string
    {
        if ($this->activeProviderResolved) {
            return $this->resolvedActiveProvider;
        }

        $configuredDefault = trim((string) config('traffic-fines.default_source', 'xephatnguoi'));
        $activeName = trim($this->settingStore->getString(self::ACTIVE_PROVIDER_KEY, $configuredDefault));

        $this->activeProviderResolved = true;
        $this->resolvedActiveProvider = $activeName === 'third_party' ? 'xephatnguoi' : $activeName;

        return $this->resolvedActiveProvider;
    }

    public function isActive(string $provider): bool
    {
        return $this->activeName() === $provider;
    }

    public function exists(string $provider): bool
    {
        return array_key_exists($provider, (array) config('traffic-fines.sources', []))
            || $this->storedProviders()->contains('name', $provider);
    }

    public function isManaged(string $provider): bool
    {
        return $this->storedProviders()->contains('name', $provider);
    }

    /** @return array<string, mixed> */
    public function configuration(string $provider): array
    {
        $storedProvider = $this->storedProviders()->firstWhere('name', $provider);
        $driver = $storedProvider?->driver ?? $provider;
        $configuration = (array) config("traffic-fines.sources.{$driver}", []);

        if ($configuration === []) {
            return [];
        }

        $configuration['label'] = $storedProvider?->label
            ?? (string) ($configuration['label'] ?? Str::headline($provider));
        $configuration['provider_code'] = $provider;
        $configuration['provider_driver'] = $driver;

        if ($storedProvider instanceof TrafficFineProvider) {
            return [
                ...$configuration,
                'url' => $storedProvider->api_url,
                'token' => $storedProvider->api_token,
                'timeout' => $storedProvider->timeout,
                'connect_timeout' => $storedProvider->connect_timeout,
                'retry_times' => $storedProvider->retry_times,
                'retry_sleep_ms' => $storedProvider->retry_sleep_ms,
            ];
        }

        foreach (self::EDITABLE_FIELDS as $field) {
            $default = $configuration[$field] ?? '';
            $storedValue = $this->settingStore->getString($this->settingKey($provider, $field), (string) $default);
            $configuration[$field] = in_array($field, ['timeout', 'connect_timeout', 'retry_times', 'retry_sleep_ms'], true)
                ? (int) $storedValue
                : trim($storedValue);
        }

        $configuration['token'] = trim($this->settingStore->getEncryptedString(
            $this->settingKey($provider, 'token'),
            (string) ($configuration['token'] ?? ''),
        ));

        return $configuration;
    }

    /** @return array<string, mixed> */
    public function adminConfiguration(string $provider): array
    {
        $configuration = $this->configuration($provider);

        return [
            'name' => $provider,
            'label' => (string) ($configuration['label'] ?? Str::headline($provider)),
            'driver' => (string) ($configuration['provider_driver'] ?? $provider),
            'deletable' => $this->isManaged($provider),
            'enabled' => $this->isActive($provider),
            'url' => '',
            'timeout' => (int) ($configuration['timeout'] ?? 10),
            'connect_timeout' => (int) ($configuration['connect_timeout'] ?? 3),
            'retry_times' => (int) ($configuration['retry_times'] ?? 2),
            'retry_sleep_ms' => (int) ($configuration['retry_sleep_ms'] ?? 200),
            'status' => filled($configuration['url'] ?? null) && filled($configuration['token'] ?? null)
                ? 'configured'
                : 'not_configured',
            'url_configured' => filled($configuration['url'] ?? null),
            'credential_configured' => filled($configuration['token'] ?? null),
        ];
    }

    /** @param array<string, mixed> $payload */
    public function create(array $payload): TrafficFineProvider
    {
        return DB::transaction(function () use ($payload): TrafficFineProvider {
            $provider = TrafficFineProvider::query()->create([
                'name' => (string) $payload['code'],
                'label' => trim((string) $payload['label']),
                'driver' => (string) $payload['driver'],
                'api_url' => trim((string) $payload['url']),
                'api_token' => trim((string) $payload['token']),
                'timeout' => (int) $payload['timeout'],
                'connect_timeout' => (int) $payload['connect_timeout'],
                'retry_times' => (int) $payload['retry_times'],
                'retry_sleep_ms' => (int) $payload['retry_sleep_ms'],
            ]);

            $this->storedProviders = null;

            if ((bool) $payload['enabled']) {
                $this->settingStore->putString(self::ACTIVE_PROVIDER_KEY, $provider->name);
                $this->setResolvedActiveProvider($provider->name);
            }

            return $provider;
        });
    }

    /** @param array<string, mixed> $payload */
    public function update(string $provider, array $payload): void
    {
        DB::transaction(function () use ($provider, $payload): void {
            $storedProvider = TrafficFineProvider::query()->where('name', $provider)->lockForUpdate()->first();

            if ($storedProvider instanceof TrafficFineProvider) {
                $storedProvider->fill([
                    'label' => trim((string) ($payload['label'] ?? $storedProvider->label)),
                    'api_url' => filled($payload['url'] ?? null) ? trim((string) $payload['url']) : $storedProvider->api_url,
                    'timeout' => (int) $payload['timeout'],
                    'connect_timeout' => (int) $payload['connect_timeout'],
                    'retry_times' => (int) $payload['retry_times'],
                    'retry_sleep_ms' => (int) $payload['retry_sleep_ms'],
                ]);

                if (filled($payload['token'] ?? null)) {
                    $storedProvider->api_token = trim((string) $payload['token']);
                }

                $storedProvider->save();
                $this->storedProviders = null;
            } else {
                $this->updateConfiguredProvider($provider, $payload);
            }

            if ((bool) $payload['enabled']) {
                $this->settingStore->putString(self::ACTIVE_PROVIDER_KEY, $provider);
                $this->setResolvedActiveProvider($provider);
            } elseif ($this->isActive($provider)) {
                $this->settingStore->putString(self::ACTIVE_PROVIDER_KEY, '');
                $this->setResolvedActiveProvider('');
            }
        });
    }

    public function delete(string $provider): bool
    {
        return DB::transaction(function () use ($provider): bool {
            $storedProvider = TrafficFineProvider::query()->where('name', $provider)->lockForUpdate()->first();

            if (! $storedProvider instanceof TrafficFineProvider) {
                return false;
            }

            if ($this->isActive($provider)) {
                $this->settingStore->putString(self::ACTIVE_PROVIDER_KEY, '');
                $this->setResolvedActiveProvider('');
            }

            $deleted = (bool) $storedProvider->delete();
            $this->storedProviders = null;

            return $deleted;
        });
    }

    /** @param array<string, mixed> $payload */
    private function updateConfiguredProvider(string $provider, array $payload): void
    {
        foreach (self::EDITABLE_FIELDS as $field) {
            if (! array_key_exists($field, $payload) || ($field === 'url' && blank($payload[$field]))) {
                continue;
            }

            $this->settingStore->putString($this->settingKey($provider, $field), (string) $payload[$field]);
        }

        if (filled($payload['token'] ?? null)) {
            $this->settingStore->putEncryptedString(
                $this->settingKey($provider, 'token'),
                trim((string) $payload['token']),
            );
        }
    }

    private function settingKey(string $provider, string $field): string
    {
        return "traffic_fine_provider_{$provider}_{$field}";
    }

    /** @return Collection<int, TrafficFineProvider> */
    private function storedProviders(): Collection
    {
        return $this->storedProviders ??= TrafficFineProvider::query()->orderBy('id')->get();
    }

    private function setResolvedActiveProvider(string $provider): void
    {
        $this->activeProviderResolved = true;
        $this->resolvedActiveProvider = $provider;
    }
}
