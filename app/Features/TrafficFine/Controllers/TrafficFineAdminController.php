<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Requests\AdminCachedPlateIndexRequest;
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
use Illuminate\Http\Request;

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

    public function logs(Request $request): JsonResponse
    {
        $search = trim($request->string('search')->toString());

        $logs = TrafficFineLookupLog::query()
            ->with('user:id,username,email')
            ->when($search !== '', fn ($query) => $query->where('plate', 'like', "%{$search}%"))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->latest('created_at')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'status' => true,
            'data' => $logs,
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
