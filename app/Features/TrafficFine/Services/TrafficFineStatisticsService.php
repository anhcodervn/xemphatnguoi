<?php

namespace App\Features\TrafficFine\Services;

use App\Features\TrafficFine\Enums\VehicleType;
use App\Models\TrafficFineLookupLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * @return array<string, mixed>
     */
    public function detailedReport(int $days = 30): array
    {
        $days = max(1, min($days, 90));
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();
        $baseQuery = TrafficFineLookupLog::query()
            ->whereBetween('created_at', [$from, $to]);

        $metrics = (clone $baseQuery)
            ->toBase()
            ->selectRaw('COUNT(*) as total_lookups')
            ->selectRaw('COUNT(DISTINCT plate) as unique_plates')
            ->selectRaw("SUM(CASE WHEN status IN ('success', 'no_violation') THEN 1 ELSE 0 END) as completed_lookups")
            ->selectRaw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as violation_lookups")
            ->selectRaw("SUM(CASE WHEN status = 'no_violation' THEN 1 ELSE 0 END) as no_violation_lookups")
            ->selectRaw("SUM(CASE WHEN status = 'provider_error' THEN 1 ELSE 0 END) as provider_errors")
            ->selectRaw("SUM(CASE WHEN source IN ('redis', 'database') THEN 1 ELSE 0 END) as cache_hits")
            ->selectRaw("SUM(CASE WHEN source = 'negative_cache' THEN 1 ELSE 0 END) as negative_cache_hits")
            ->selectRaw('SUM(CASE WHEN cache_hit = 0 THEN 1 ELSE 0 END) as cache_misses')
            ->selectRaw("SUM(CASE WHEN source = 'provider' THEN 1 ELSE 0 END) as provider_requests")
            ->selectRaw("AVG(CASE WHEN source = 'provider' THEN provider_latency_ms ELSE NULL END) as average_provider_latency_ms")
            ->first();

        $totalLookups = (int) ($metrics?->total_lookups ?? 0);
        $completedLookups = (int) ($metrics?->completed_lookups ?? 0);
        $cacheHits = (int) ($metrics?->cache_hits ?? 0);
        $dailyRows = (clone $baseQuery)
            ->toBase()
            ->selectRaw('DATE(created_at) as report_date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('success', 'no_violation') THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status = 'provider_error' THEN 1 ELSE 0 END) as provider_errors")
            ->selectRaw("SUM(CASE WHEN source IN ('redis', 'database') THEN 1 ELSE 0 END) as cache_hits")
            ->selectRaw("SUM(CASE WHEN source = 'negative_cache' THEN 1 ELSE 0 END) as negative_cache_hits")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('report_date')
            ->get()
            ->keyBy(fn (object $row): string => (string) $row->report_date);

        $daily = collect(range(0, $days - 1))->map(function (int $offset) use ($dailyRows, $from): array {
            $date = $from->copy()->addDays($offset);
            $row = $dailyRows->get($date->toDateString());

            return [
                'date' => $date->toDateString(),
                'label' => $date->format('d/m'),
                'total' => (int) ($row?->total ?? 0),
                'completed' => (int) ($row?->completed ?? 0),
                'provider_errors' => (int) ($row?->provider_errors ?? 0),
                'cache_hits' => (int) ($row?->cache_hits ?? 0),
                'negative_cache_hits' => (int) ($row?->negative_cache_hits ?? 0),
            ];
        })->values()->all();

        return [
            'period' => [
                'days' => $days,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'total_lookups' => $totalLookups,
                'unique_plates' => (int) ($metrics?->unique_plates ?? 0),
                'completed_lookups' => $completedLookups,
                'violation_lookups' => (int) ($metrics?->violation_lookups ?? 0),
                'no_violation_lookups' => (int) ($metrics?->no_violation_lookups ?? 0),
                'provider_errors' => (int) ($metrics?->provider_errors ?? 0),
                'cache_hits' => $cacheHits,
                'negative_cache_hits' => (int) ($metrics?->negative_cache_hits ?? 0),
                'cache_misses' => (int) ($metrics?->cache_misses ?? 0),
                'cache_hit_rate' => $this->percentage($cacheHits, $totalLookups),
                'completion_rate' => $this->percentage($completedLookups, $totalLookups),
                'provider_requests' => (int) ($metrics?->provider_requests ?? 0),
                'average_provider_latency_ms' => $metrics?->average_provider_latency_ms !== null
                    ? round((float) $metrics->average_provider_latency_ms, 2)
                    : null,
            ],
            'daily' => $daily,
            'vehicle_types' => $this->breakdown($baseQuery, 'vehicle_type', $totalLookups, fn (string $key): string => $this->vehicleTypeLabel($key)),
            'sources' => $this->breakdown($baseQuery, 'source', $totalLookups, fn (string $key): string => $this->sourceLabel($key)),
            'recent_errors' => (clone $baseQuery)
                ->where('status', 'provider_error')
                ->latest('created_at')
                ->limit(8)
                ->get(['id', 'plate', 'vehicle_type', 'source', 'provider', 'provider_latency_ms', 'created_at'])
                ->map(fn (TrafficFineLookupLog $log): array => [
                    'id' => $log->id,
                    'plate' => $log->plate,
                    'vehicle_type' => $log->vehicle_type,
                    'provider' => $log->provider,
                    'source' => $log->source,
                    'provider_latency_ms' => $log->provider_latency_ms,
                    'created_at' => $log->created_at?->toISOString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  callable(string): string  $labelResolver
     * @return list<array{key: string, label: string, total: int, percentage: float}>
     */
    private function breakdown(Builder $query, string $column, int $totalLookups, callable $labelResolver): array
    {
        return (clone $query)
            ->toBase()
            ->select($column)
            ->selectRaw('COUNT(*) as total')
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->map(function (object $row) use ($column, $labelResolver, $totalLookups): array {
                $key = (string) $row->{$column};
                $total = (int) $row->total;

                return [
                    'key' => $key,
                    'label' => $labelResolver($key),
                    'total' => $total,
                    'percentage' => $this->percentage($total, $totalLookups),
                ];
            })
            ->values()
            ->all();
    }

    private function percentage(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0.0;
    }

    private function vehicleTypeLabel(string $vehicleType): string
    {
        return VehicleType::tryFrom($vehicleType)?->label() ?? $vehicleType;
    }

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'provider' => 'Nhà cung cấp',
            'redis' => 'Redis',
            'database' => 'Database',
            'negative_cache' => 'Cache lỗi',
            default => $source,
        };
    }
}
