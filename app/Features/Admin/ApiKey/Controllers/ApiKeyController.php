<?php

namespace App\Features\Admin\ApiKey\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $status = trim($request->string('status')->toString());
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $query = ApiKey::query()
            ->with(['user:id,username,email,full_name'])
            ->withCount('apiLogs')
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($apiKeyQuery) use ($search): void {
                    $apiKeyQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('api_key', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($builder) => $builder->where('status', $status));

        $apiKeys = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (ApiKey $apiKey): array {
                return [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'api_key' => $apiKey->api_key,
                    'permissions' => $apiKey->permissions ?? [],
                    'ip_whitelist' => $apiKey->ip_whitelist ?? [],
                    'status' => $apiKey->status,
                    'last_used_at' => $apiKey->last_used_at?->toISOString(),
                    'expired_at' => $apiKey->expired_at?->toISOString(),
                    'created_at' => $apiKey->created_at?->toISOString(),
                    'logs_count' => $apiKey->api_logs_count,
                    'user' => $apiKey->user ? [
                        'id' => $apiKey->user->id,
                        'username' => $apiKey->user->username,
                        'email' => $apiKey->user->email,
                        'full_name' => $apiKey->user->full_name,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'api_keys' => $apiKeys,
                'summary' => [
                    'total' => (clone $query)->count(),
                    'active' => (clone $query)->where('status', ApiKey::STATUS_ACTIVE)->count(),
                    'inactive' => (clone $query)->where('status', ApiKey::STATUS_INACTIVE)->count(),
                    'revoked' => (clone $query)->where('status', ApiKey::STATUS_REVOKED)->count(),
                ],
            ],
        ]);
    }
}
