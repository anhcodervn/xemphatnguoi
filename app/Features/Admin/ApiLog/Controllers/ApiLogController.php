<?php

namespace App\Features\Admin\ApiLog\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());
        $method = strtoupper(trim($request->string('method')->toString()));
        $statusCode = $request->integer('status_code');
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $query = ApiLog::query()
            ->with([
                'user:id,username,email,full_name',
                'apiKey:id,user_id,name,api_key,status',
            ])
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($logQuery) use ($search): void {
                    $logQuery
                        ->where('endpoint', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->where('username', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('apiKey', function ($apiKeyQuery) use ($search): void {
                            $apiKeyQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('api_key', 'like', "%{$search}%");
                        });
                });
            })
            ->when($method !== '', fn ($builder) => $builder->where('method', $method))
            ->when($request->filled('status_code'), fn ($builder) => $builder->where('status_code', $statusCode));

        $apiLogs = $query
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (ApiLog $apiLog): array {
                return [
                    'id' => $apiLog->id,
                    'endpoint' => $apiLog->endpoint,
                    'method' => $apiLog->method,
                    'ip' => $apiLog->ip,
                    'request_data' => $apiLog->request_data,
                    'response_data' => $apiLog->response_data,
                    'status_code' => $apiLog->status_code,
                    'response_time_ms' => $apiLog->response_time_ms,
                    'created_at' => $apiLog->created_at?->toISOString(),
                    'user' => $apiLog->user ? [
                        'id' => $apiLog->user->id,
                        'username' => $apiLog->user->username,
                        'email' => $apiLog->user->email,
                        'full_name' => $apiLog->user->full_name,
                    ] : null,
                    'api_key' => $apiLog->apiKey ? [
                        'id' => $apiLog->apiKey->id,
                        'name' => $apiLog->apiKey->name,
                        'api_key' => $apiLog->apiKey->api_key,
                        'status' => $apiLog->apiKey->status,
                    ] : null,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'api_logs' => $apiLogs,
                'summary' => [
                    'total' => (clone $query)->count(),
                    'success' => (clone $query)->whereBetween('status_code', [200, 299])->count(),
                    'client_error' => (clone $query)->whereBetween('status_code', [400, 499])->count(),
                    'server_error' => (clone $query)->where('status_code', '>=', 500)->count(),
                ],
            ],
        ]);
    }
}
