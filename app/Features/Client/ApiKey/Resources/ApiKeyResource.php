<?php

namespace App\Features\Client\ApiKey\Resources;

use App\Models\ApiKey;
use App\Support\ApiPermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ApiKey
 */
class ApiKeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'api_key' => $this->api_key,
            'permissions' => $this->permissions ?? [],
            'permission_details' => ApiPermissionCatalog::resolve($this->permissions),
            'ip_whitelist' => $this->ip_whitelist ?? [],
            'status' => $this->status,
            'last_used_at' => $this->last_used_at?->toISOString(),
            'expired_at' => $this->expired_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'logs_count' => $this->whenCounted('apiLogs', $this->api_logs_count),
        ];
    }
}
