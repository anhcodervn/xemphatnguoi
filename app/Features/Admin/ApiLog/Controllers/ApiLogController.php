<?php

namespace App\Features\Admin\ApiLog\Controllers;

use App\Features\Admin\ApiLog\Requests\ApiLogIndexRequest;
use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\JsonResponse;

class ApiLogController extends Controller
{
    public function index(ApiLogIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = ApiLog::query()
            ->with([
                'user:id,username,email,full_name',
                'apiKey:id,name,api_key,status',
            ])
            ->when($request->filled('search'), function ($builder) use ($request): void {
                $search = trim((string) $request->string('search')->toString());

                $builder->where(function ($inner) use ($search): void {
                    $inner->where('endpoint', 'like', '%'.$search.'%')
                        ->orWhere('method', 'like', '%'.$search.'%')
                        ->orWhere('ip', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('username', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('full_name', 'like', '%'.$search.'%'))
                        ->orWhereHas('apiKey', fn ($apiKeyQuery) => $apiKeyQuery
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('api_key', 'like', '%'.$search.'%'));
                });
            })
            ->when($request->filled('method'), fn ($builder) => $builder->where('method', strtoupper((string) $request->string('method')->toString())))
            ->when($request->filled('api_version'), fn ($builder) => $builder->where(
                'endpoint',
                'like',
                'api/'.$validated['api_version'].'/%',
            ))
            ->when($request->filled('status_code'), fn ($builder) => $builder->where('status_code', (int) $request->integer('status_code')))
            ->when(($validated['status_group'] ?? null) === 'success', fn ($builder) => $builder->whereBetween('status_code', [200, 299]))
            ->when(($validated['status_group'] ?? null) === 'client_error', fn ($builder) => $builder->whereBetween('status_code', [400, 499]))
            ->when(($validated['status_group'] ?? null) === 'server_error', fn ($builder) => $builder->whereBetween('status_code', [500, 599]))
            ->when($request->filled('from'), fn ($builder) => $builder->whereDate('created_at', '>=', $validated['from']))
            ->when($request->filled('to'), fn ($builder) => $builder->whereDate('created_at', '<=', $validated['to']));

        $logs = (clone $query)
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        $summaryQuery = clone $query;
        $failureQuery = (clone $query)->where('status_code', '>=', 500);
        $averageResponseTime = (clone $summaryQuery)->avg('response_time_ms');

        return response()->json([
            'status' => true,
            'data' => [
                'api_logs' => $logs,
                'summary' => [
                    'total' => (clone $summaryQuery)->count(),
                    'success' => (clone $summaryQuery)->whereBetween('status_code', [200, 299])->count(),
                    'client_error' => (clone $summaryQuery)->whereBetween('status_code', [400, 499])->count(),
                    'server_error' => (clone $summaryQuery)->where('status_code', '>=', 500)->count(),
                    'service_unavailable' => (clone $summaryQuery)->where('status_code', 503)->count(),
                    'affected_users' => (clone $failureQuery)->distinct()->count('user_id'),
                    'affected_api_keys' => (clone $failureQuery)->distinct()->count('api_key_id'),
                    'average_response_time_ms' => $averageResponseTime === null ? null : (int) round((float) $averageResponseTime),
                    'first_failure_at' => (clone $failureQuery)->oldest('created_at')->value('created_at'),
                    'last_failure_at' => (clone $failureQuery)->latest('created_at')->value('created_at'),
                    'charged' => (clone $summaryQuery)->where('billing_status', 'charged')->count(),
                    'revenue' => (string) (clone $summaryQuery)->where('billing_status', 'charged')->sum('charged_amount'),
                ],
            ],
        ]);
    }
}
