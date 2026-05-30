<?php

namespace App\Features\Client\ApiKey\Services;

use App\Features\Client\ApiKey\Resources\ApiKeyResource;
use App\Features\Client\ApiKey\Resources\ApiLogResource;
use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\User;
use App\Support\ApiPermissionCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiKeyService
{
    public function __construct(
        private readonly ApiKeyEligibilityService $apiKeyEligibilityService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginate(User $user, array $filters = []): array
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 15), 100));
        $keys = $this->query($user, $filters)->paginate($perPage)->withQueryString();

        return [
            'data' => ApiKeyResource::collection($keys->getCollection())->resolve(),
            'meta' => $this->paginationMeta($keys),
            'permissions' => ApiPermissionCatalog::selfService(),
        ];
    }

    /**
     * @param  array{name:string,permissions:array<int,string>,ip_whitelist?:array<int,string>|null,expired_at?:string|null}  $payload
     * @return array{api_key:ApiKey,plain_secret:string}
     */
    public function create(User $user, array $payload): array
    {
        $this->apiKeyEligibilityService->ensureCanCreate($user);

        $plainSecret = $this->generateSecret();

        $apiKey = $user->apiKeys()->create([
            'name' => trim($payload['name']),
            'api_key' => $this->generateKey(),
            'api_secret' => Hash::make($plainSecret),
            'permissions' => $this->normalizePermissions($payload['permissions']),
            'ip_whitelist' => $this->normalizeIpWhitelist($payload['ip_whitelist'] ?? null),
            'expired_at' => $payload['expired_at'] ?? null,
            'status' => ApiKey::STATUS_ACTIVE,
        ]);

        return [
            'api_key' => $apiKey->fresh()->loadCount('apiLogs'),
            'plain_secret' => $plainSecret,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(ApiKey $apiKey, array $payload): ApiKey
    {
        $attributes = [];

        if (array_key_exists('name', $payload)) {
            $attributes['name'] = trim((string) $payload['name']);
        }

        if (array_key_exists('permissions', $payload)) {
            $attributes['permissions'] = $this->normalizePermissions((array) $payload['permissions']);
        }

        if (array_key_exists('ip_whitelist', $payload)) {
            $attributes['ip_whitelist'] = $this->normalizeIpWhitelist($payload['ip_whitelist']);
        }

        if (array_key_exists('expired_at', $payload)) {
            $attributes['expired_at'] = $payload['expired_at'];
        }

        if (array_key_exists('status', $payload)) {
            $attributes['status'] = $payload['status'];
        }

        $apiKey->forceFill($attributes)->save();

        return $apiKey->fresh()->loadCount('apiLogs');
    }

    public function revoke(ApiKey $apiKey): ApiKey
    {
        $apiKey->forceFill([
            'status' => ApiKey::STATUS_REVOKED,
        ])->save();

        return $apiKey->fresh()->loadCount('apiLogs');
    }

    /**
     * @return array{api_key:ApiKey,plain_secret:string}
     */
    public function rotateSecret(ApiKey $apiKey): array
    {
        $plainSecret = $this->generateSecret();
        $rotatedKey = $this->generateKey();

        $apiKey->forceFill([
            'api_key' => $rotatedKey,
            'api_secret' => Hash::make($plainSecret),
            'last_used_at' => null,
        ])->save();

        return [
            'api_key' => $apiKey->fresh()->loadCount('apiLogs'),
            'plain_secret' => $plainSecret,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function logs(ApiKey $apiKey, array $filters = []): array
    {
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));
        $logs = ApiLog::query()
            ->whereBelongsTo($apiKey)
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('endpoint', 'like', "%{$search}%")
                        ->orWhere('method', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $builder->orWhere('status_code', (int) $search);
                    }
                });
            })
            ->when(filled($filters['status_code'] ?? null), fn (Builder $query) => $query->where('status_code', (int) $filters['status_code']))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => ApiLogResource::collection($logs->getCollection())->resolve(),
            'meta' => $this->paginationMeta($logs),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(User $user, array $filters): Builder
    {
        return ApiKey::query()
            ->whereBelongsTo($user)
            ->withCount('apiLogs')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    if (is_numeric($search)) {
                        $builder->orWhere('id', (int) $search);
                    }

                    $builder
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('api_key', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', (string) $filters['status']))
            ->latest('id');
    }

    /**
     * @return array{current_page:int,last_page:int,per_page:int,total:int}
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    private function normalizePermissions(array $permissions): array
    {
        return array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $permission): string => trim((string) $permission),
                $permissions,
            ),
            static fn (string $permission): bool => $permission !== '',
        )));
    }

    /**
     * @param  array<int, string>|mixed  $ipWhitelist
     * @return array<int, string>|null
     */
    private function normalizeIpWhitelist(mixed $ipWhitelist): ?array
    {
        if (! is_array($ipWhitelist)) {
            return null;
        }

        $ips = array_values(array_unique(array_filter(
            array_map(
                static fn (mixed $ip): string => trim((string) $ip),
                $ipWhitelist,
            ),
            static fn (string $ip): bool => $ip !== '',
        )));

        return $ips === [] ? null : $ips;
    }

    private function generateKey(): string
    {
        do {
            $key = 'ntd_'.Str::lower(Str::random(32));
        } while (ApiKey::query()->where('api_key', $key)->exists());

        return $key;
    }

    private function generateSecret(): string
    {
        return 'sk_'.Str::random(48);
    }
}
