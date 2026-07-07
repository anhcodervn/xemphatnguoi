<?php

namespace App\Features\Client\ApiKey\Resources;

use App\Support\ApiPermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissionCatalog = ApiPermissionCatalog::keyed();
        $permissions = $this->permissions ?? [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'api_key' => $this->api_key,
            'key_type' => $this->key_type,
            'permissions' => $permissions,
            'permission_details' => array_values(array_filter(
                array_map(static fn (string $permission): ?array => $permissionCatalog[$permission] ?? null, $permissions),
            )),
            'ip_whitelist' => $this->ip_whitelist ?? [],
            'status' => $this->status,
            'subscription' => $this->subscription ? [
                'id' => $this->subscription->id,
                'package_id' => $this->subscription->package_id,
                'package_name' => $this->subscription->package_name,
                'expires_at' => $this->subscription->expires_at?->toISOString(),
                'status' => $this->subscription->status?->value ?? (string) $this->subscription->status,
            ] : null,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'expired_at' => $this->expired_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'logs_count' => $this->whenCounted('logs'),
        ];
    }
}
