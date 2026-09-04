<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Requests\AdminCachedPlateIndexRequest;
use App\Features\TrafficFine\Requests\AdminTrafficFineLookupLogRequest;
use App\Features\TrafficFine\Requests\AdminTrafficFineReportRequest;
use App\Features\TrafficFine\Requests\UpdateApiBillingSettingRequest;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\ApiUsageStatisticsService;
use App\Features\TrafficFine\Services\CachedPlateService;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceRegistry;
use App\Features\TrafficFine\Services\TrafficFineStatisticsService;
use App\Http\Controllers\Controller;
use App\Models\TrafficFineLookupLog;
use App\Support\SettingStore;
use Illuminate\Http\JsonResponse;

class TrafficFineAdminController extends Controller
{
    public function overview(TrafficFineStatisticsService $statistics): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'metrics' => $statistics->overview(),
            ],
        ]);
    }

    public function report(
        AdminTrafficFineReportRequest $request,
        TrafficFineStatisticsService $statistics,
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'data' => $statistics->detailedReport($request->integer('days', 30)),
        ]);
    }

    public function results(
        AdminCachedPlateIndexRequest $request,
        CachedPlateService $cachedPlates,
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'data' => $cachedPlates->paginate($request->validated()),
        ]);
    }

    public function logs(AdminTrafficFineLookupLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $search = trim($request->string('search')->toString());

        $query = TrafficFineLookupLog::query()
            ->with('user:id,username,email')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('plate', 'like', "%{$search}%")
                        ->orWhere('ip', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $validated['from']))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $validated['to']));

        $logs = (clone $query)
            ->latest('created_at')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        $summaryQuery = clone $query;
        $failureQuery = (clone $query)->where('status', 'provider_error');

        return response()->json([
            'status' => true,
            'data' => [
                'logs' => $logs,
                'summary' => [
                    'total' => (clone $summaryQuery)->count(),
                    'completed' => (clone $summaryQuery)->whereIn('status', ['success', 'no_violation'])->count(),
                    'provider_errors' => (clone $failureQuery)->count(),
                    'affected_users' => (clone $failureQuery)->whereNotNull('user_id')->distinct()->count('user_id'),
                    'anonymous_requests' => (clone $failureQuery)->whereNull('user_id')->count(),
                    'affected_anonymous_ips' => (clone $failureQuery)->whereNull('user_id')->whereNotNull('ip')->distinct()->count('ip'),
                    'first_failure_at' => (clone $failureQuery)->oldest('created_at')->value('created_at'),
                    'last_failure_at' => (clone $failureQuery)->latest('created_at')->value('created_at'),
                ],
            ],
        ]);
    }

    public function provider(TrafficFineSourceRegistry $sourceRegistry): JsonResponse
    {
        $providerName = $sourceRegistry->activeName();
        $providerConfig = $sourceRegistry->activeConfig();
        $lastError = TrafficFineLookupLog::query()
            ->where('status', 'provider_error')
            ->latest('created_at')
            ->first(['created_at']);

        return response()->json([
            'status' => true,
            'data' => [
                'name' => $providerName,
                'enabled' => filled($providerConfig['url'] ?? null),
                'priority' => (int) ($providerConfig['priority'] ?? 1),
                'timeout' => (int) ($providerConfig['timeout'] ?? 10),
                'status' => filled($providerConfig['url'] ?? null) ? 'configured' : 'not_configured',
                'url_configured' => filled($providerConfig['url'] ?? null),
                'credential_configured' => filled($providerConfig['token'] ?? null),
                'last_error' => $lastError?->created_at?->toISOString(),
            ],
        ]);
    }

    public function billing(
        ApiLookupBillingService $billingService,
        ApiUsageStatisticsService $statistics,
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'data' => [
                'api_request_price' => $billingService->pricePerRequest(),
                'summary' => $statistics->summary(),
                'chart' => $statistics->daily(days: 30),
            ],
        ]);
    }

    public function updateBilling(
        UpdateApiBillingSettingRequest $request,
        SettingStore $settingStore,
        ApiLookupBillingService $billingService,
        ApiUsageStatisticsService $statistics,
    ): JsonResponse {
        $settingStore->putString(
            ApiLookupBillingService::PRICE_SETTING_KEY,
            (string) $request->integer('api_request_price'),
        );

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật giá tra cứu API.',
            'data' => [
                'api_request_price' => $billingService->pricePerRequest(),
                'summary' => $statistics->summary(),
                'chart' => $statistics->daily(days: 30),
            ],
        ]);
    }
}
