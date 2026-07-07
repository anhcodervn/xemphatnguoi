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
            ->when($request->filled('status_code'), fn ($builder) => $builder->where('status_code', (int) $request->integer('status_code')));

        $logs = (clone $query)
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 15), 1), 100))
            ->withQueryString();

        $summaryQuery = clone $query;

        return response()->json([
            'status' => true,
            'data' => [
                'api_logs' => $logs,
                'summary' => [
                    'total' => (clone $summaryQuery)->count(),
                    'success' => (clone $summaryQuery)->whereBetween('status_code', [200, 299])->count(),
                    'client_error' => (clone $summaryQuery)->whereBetween('status_code', [400, 499])->count(),
                    'server_error' => (clone $summaryQuery)->where('status_code', '>=', 500)->count(),
                ],
            ],
        ]);
    }
}
