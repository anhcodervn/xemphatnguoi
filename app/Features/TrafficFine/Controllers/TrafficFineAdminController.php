<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\TrafficFine\Requests\AdminCachedPlateIndexRequest;
use App\Features\TrafficFine\Requests\AdminTrafficFineLookupLogRequest;
use App\Features\TrafficFine\Requests\AdminTrafficFineReportRequest;
use App\Features\TrafficFine\Requests\StoreTrafficFineProviderRequest;
use App\Features\TrafficFine\Requests\UpdateApiBillingSettingRequest;
use App\Features\TrafficFine\Requests\UpdateTrafficFineProviderRequest;
use App\Features\TrafficFine\Services\ApiDocumentationSettingsService;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\ApiUsageStatisticsService;
use App\Features\TrafficFine\Services\CachedPlateService;
use App\Features\TrafficFine\Services\TrafficFineProviderBalanceService;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use App\Features\TrafficFine\Services\TrafficFineStatisticsService;
use App\Http\Controllers\Controller;
use App\Models\TrafficFineLookupLog;
use App\Support\SettingStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

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
                $request->filled('api_version'),
                fn ($query) => $query->where('api_version', $request->string('api_version')->toString()),
            )
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

    public function provider(TrafficFineProviderSettingsService $providerSettings): JsonResponse
    {
        $lastErrors = TrafficFineLookupLog::query()
            ->where('status', 'provider_error')
            ->whereNotNull('provider')
            ->selectRaw('provider, MAX(created_at) as last_error_at')
            ->groupBy('provider')
            ->pluck('last_error_at', 'provider');

        $providers = collect($providerSettings->providerNames())
            ->map(function (string $provider) use ($providerSettings, $lastErrors): array {
                $configuration = $providerSettings->adminConfiguration($provider);
                $lastError = $lastErrors->get($provider);
                $configuration['last_error'] = filled($lastError)
                    ? Carbon::parse((string) $lastError)->toISOString()
                    : null;

                return $configuration;
            })
            ->values();

        $activeProvider = $providerSettings->activeName();
        $activeConfiguration = $providers->firstWhere('name', $activeProvider);

        return response()->json([
            'status' => true,
            'data' => [
                'active_provider' => $activeProvider !== '' ? $activeProvider : null,
                'drivers' => $providerSettings->driverNames(),
                'providers' => $providers->all(),
                ...($activeConfiguration ?? [
                    'name' => '',
                    'enabled' => false,
                    'status' => 'disabled',
                ]),
            ],
        ]);
    }

    public function storeProvider(
        StoreTrafficFineProviderRequest $request,
        TrafficFineProviderSettingsService $providerSettings,
    ): JsonResponse {
        $provider = $providerSettings->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Đã thêm nguồn dữ liệu.',
            'data' => $providerSettings->adminConfiguration($provider->name),
        ], 201);
    }

    public function updateProvider(
        UpdateTrafficFineProviderRequest $request,
        string $provider,
        TrafficFineProviderSettingsService $providerSettings,
    ): JsonResponse {
        abort_unless(in_array($provider, $providerSettings->providerNames(), true), 404);

        $providerSettings->update($provider, $request->validated());

        return response()->json([
            'status' => true,
            'message' => $request->boolean('enabled')
                ? 'Đã bật provider. Các provider khác đã được tắt.'
                : 'Đã cập nhật và tắt provider.',
            'data' => [
                'active_provider' => $providerSettings->activeName() ?: null,
                'provider' => $providerSettings->adminConfiguration($provider),
            ],
        ]);
    }

    public function destroyProvider(
        string $provider,
        TrafficFineProviderSettingsService $providerSettings,
    ): JsonResponse {
        abort_unless($providerSettings->exists($provider), 404);

        if (! $providerSettings->delete($provider)) {
            return response()->json([
                'status' => false,
                'message' => 'Nguồn hệ thống không thể xoá, bạn có thể tắt nguồn này.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá nguồn dữ liệu.',
        ]);
    }

    public function providerBalance(
        string $provider,
        TrafficFineProviderSettingsService $providerSettings,
        TrafficFineProviderBalanceService $balanceService,
    ): JsonResponse {
        abort_unless($providerSettings->exists($provider), 404);

        return response()->json([
            'status' => true,
            'data' => $balanceService->get($provider, request()->boolean('refresh')),
        ]);
    }

    public function billing(
        ApiLookupBillingService $billingService,
        ApiDocumentationSettingsService $documentationSettings,
        ApiUsageStatisticsService $statistics,
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'data' => [
                'api_request_price' => $billingService->pricePerRequest(),
                'api_v2_request_price' => $billingService->v2PricePerRequest(),
                'api_v1_description' => $documentationSettings->v1Description(),
                'api_v2_description' => $documentationSettings->v2Description(),
                'summary' => $statistics->summary(),
                'chart' => $statistics->daily(days: 30),
            ],
        ]);
    }

    public function updateBilling(
        UpdateApiBillingSettingRequest $request,
        SettingStore $settingStore,
        ApiLookupBillingService $billingService,
        ApiDocumentationSettingsService $documentationSettings,
        ApiUsageStatisticsService $statistics,
    ): JsonResponse {
        $validated = $request->validated();

        $settingStore->putString(
            ApiLookupBillingService::PRICE_SETTING_KEY,
            (string) $request->integer('api_request_price'),
        );
        $settingStore->putString(
            ApiLookupBillingService::V2_PRICE_SETTING_KEY,
            (string) $request->integer('api_v2_request_price'),
        );
        $documentationSettings->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Đã cập nhật giá và mô tả API.',
            'data' => [
                'api_request_price' => $billingService->pricePerRequest(),
                'api_v2_request_price' => $billingService->v2PricePerRequest(),
                'api_v1_description' => $documentationSettings->v1Description(),
                'api_v2_description' => $documentationSettings->v2Description(),
                'summary' => $statistics->summary(),
                'chart' => $statistics->daily(days: 30),
            ],
        ]);
    }
}
