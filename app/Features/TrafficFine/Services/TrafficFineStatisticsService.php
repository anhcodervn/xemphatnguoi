<?php

namespace App\Features\TrafficFine\Services;

use App\Models\TrafficFineLookupLog;
use App\Models\User;

class TrafficFineStatisticsService
{
    public function __construct(
        private readonly ApiUsageStatisticsService $apiUsageStatistics,
        private readonly ApiLookupBillingService $billingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $lookupMetrics = TrafficFineLookupLog::query()
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as lookup_today', [$today])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as lookup_month', [$month])
            ->selectRaw('SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits')
            ->selectRaw('SUM(CASE WHEN cache_hit = 0 THEN 1 ELSE 0 END) as cache_misses')
            ->selectRaw("SUM(CASE WHEN source = 'provider' THEN 1 ELSE 0 END) as provider_requests")
            ->selectRaw("SUM(CASE WHEN status = 'provider_error' THEN 1 ELSE 0 END) as provider_errors")
            ->selectRaw("AVG(CASE WHEN source = 'provider' THEN provider_latency_ms ELSE NULL END) as average_provider_latency_ms")
            ->first();

        $apiUsage = $this->apiUsageStatistics->summary();

        return [
            'lookup_today' => (int) ($lookupMetrics?->lookup_today ?? 0),
            'lookup_month' => (int) ($lookupMetrics?->lookup_month ?? 0),
            'cache_hits' => (int) ($lookupMetrics?->cache_hits ?? 0),
            'cache_misses' => (int) ($lookupMetrics?->cache_misses ?? 0),
            'provider_requests' => (int) ($lookupMetrics?->provider_requests ?? 0),
            'provider_errors' => (int) ($lookupMetrics?->provider_errors ?? 0),
            'average_provider_latency_ms' => $lookupMetrics?->average_provider_latency_ms !== null
                ? round((float) $lookupMetrics->average_provider_latency_ms, 2)
                : null,
            'users' => User::query()->count(),
            'api_request_price' => $this->billingService->pricePerRequest(),
            'api_requests_today' => $apiUsage['requests_today'],
            'api_requests_month' => $apiUsage['requests_month'],
            'api_revenue_today' => $apiUsage['amount_today'],
            'api_revenue_month' => $apiUsage['amount_month'],
            'api_revenue_total' => $apiUsage['total_amount'],
            'api_chart' => $this->apiUsageStatistics->daily(days: 14),
        ];
    }
}
