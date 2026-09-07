<?php

namespace App\Features\TrafficFine\Controllers;

use App\Features\Client\Wallet\Services\WalletService;
use App\Features\TrafficFine\Resources\ApiUsageLogResource;
use App\Features\TrafficFine\Services\ApiDocumentationSettingsService;
use App\Features\TrafficFine\Services\ApiLookupBillingService;
use App\Features\TrafficFine\Services\ApiUsageStatisticsService;
use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\LookupHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrafficFineDashboardController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly ApiLookupBillingService $billingService,
        private readonly ApiDocumentationSettingsService $documentationSettings,
        private readonly ApiUsageStatisticsService $apiUsageStatistics,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => [
                'wallet' => $this->walletService->getWalletInfo($user),
                'api_request_price' => $this->billingService->pricePerRequest(),
                'api_v2_request_price' => $this->billingService->v2PricePerRequest(),
                'api_v1_description' => $this->documentationSettings->v1Description(),
                'api_v2_description' => $this->documentationSettings->v2Description(),
                'api_usage' => $this->apiUsageStatistics->summary($user),
                'api_chart' => $this->apiUsageStatistics->daily($user),
                'lookup_count' => $user->lookupHistories()->count(),
                'monitoring_count' => $user->vehicleMonitorings()->where('enabled', true)->count(),
                'vehicle_count' => $user->vehicles()->count(),
                'recent_lookups' => $user->lookupHistories()
                    ->latest('created_at')
                    ->limit(5)
                    ->get(['id', 'plate', 'vehicle_type', 'api_version', 'violation_count', 'created_at']),
            ],
        ]);
    }

    public function histories(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $histories = LookupHistory::query()
            ->whereBelongsTo($user)
            ->latest('created_at')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'status' => true,
            'data' => $histories,
        ]);
    }

    public function apiUsage(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $logs = ApiLog::query()
            ->with('apiKey:id,name')
            ->whereBelongsTo($user)
            ->whereIn('endpoint', ['api/v1/lookup', 'api/v2/lookup'])
            ->where('method', 'GET')
            ->latest('created_at')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json([
            'status' => true,
            'data' => [
                'api_request_price' => $this->billingService->pricePerRequest(),
                'api_v2_request_price' => $this->billingService->v2PricePerRequest(),
                'summary' => $this->apiUsageStatistics->summary($user),
                'chart' => $this->apiUsageStatistics->daily($user),
                'logs' => ApiUsageLogResource::collection($logs)->response()->getData(true),
            ],
        ]);
    }
}
