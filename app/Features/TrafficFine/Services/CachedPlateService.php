<?php

namespace App\Features\TrafficFine\Services;

use App\Models\TrafficFineLookupLog;
use App\Models\TrafficFineResult;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;

class CachedPlateService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginate(array $filters): array
    {
        $now = now();
        $days = (int) ($filters['days'] ?? 30);
        $periodStart = $now->copy()->subDays($days - 1)->startOfDay();
        $query = $this->cachedPlateQuery($periodStart);

        $this->applyFilters($query, $filters, $now);
        $this->applySorting($query, $filters);

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 25));
        $items = collect($paginator->items())
            ->map(fn (TrafficFineResult $result): array => $this->serializeResult($result, $now))
            ->values()
            ->all();

        return [
            'server_time' => $now->toISOString(),
            'period' => [
                'days' => $days,
                'from' => $periodStart->toDateString(),
                'to' => $now->toDateString(),
            ],
            'cache' => [
                'store' => (string) config('cache.default'),
                'configured_ttl_seconds' => (int) config('traffic-fines.cache.ttl', 86400),
            ],
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'summary' => $this->summary($now, $periodStart),
        ];
    }

    /** @return Builder<TrafficFineResult> */
    private function cachedPlateQuery(CarbonInterface $periodStart): Builder
    {
        $lookupStatistics = TrafficFineLookupLog::query()
            ->select(['plate', 'vehicle_type'])
            ->selectRaw(
                "COUNT(*) AS lookup_count,
                SUM(CASE WHEN source IN ('redis', 'database') THEN 1 ELSE 0 END) AS positive_cache_hits,
                SUM(CASE WHEN source = 'provider' THEN 1 ELSE 0 END) AS provider_requests,
                SUM(CASE WHEN status = 'provider_error' THEN 1 ELSE 0 END) AS provider_errors,
                MAX(created_at) AS last_lookup_at",
            )
            ->where('created_at', '>=', $periodStart)
            ->groupBy('plate', 'vehicle_type');

        return TrafficFineResult::query()
            ->leftJoinSub($lookupStatistics, 'lookup_statistics', function (JoinClause $join): void {
                $join
                    ->on('lookup_statistics.plate', '=', 'traffic_fine_results.plate')
                    ->on('lookup_statistics.vehicle_type', '=', 'traffic_fine_results.vehicle_type');
            })
            ->select([
                'traffic_fine_results.id',
                'traffic_fine_results.plate',
                'traffic_fine_results.vehicle_type',
                'traffic_fine_results.status',
                'traffic_fine_results.violation_count',
                'traffic_fine_results.provider',
                'traffic_fine_results.checked_at',
                'traffic_fine_results.expires_at',
                'lookup_statistics.last_lookup_at',
            ])
            ->selectRaw('COALESCE(lookup_statistics.lookup_count, 0) AS lookup_count')
            ->selectRaw('COALESCE(lookup_statistics.positive_cache_hits, 0) AS positive_cache_hits')
            ->selectRaw('COALESCE(lookup_statistics.provider_requests, 0) AS provider_requests')
            ->selectRaw('COALESCE(lookup_statistics.provider_errors, 0) AS provider_errors')
            ->withCasts(['last_lookup_at' => 'immutable_datetime']);
    }

    /**
     * @param  Builder<TrafficFineResult>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, CarbonInterface $now): void
    {
        $search = Str::of((string) ($filters['search'] ?? ''))
            ->trim()
            ->upper()
            ->replaceMatches('/[\s.\-]+/u', '')
            ->toString();

        $query
            ->when($search !== '', fn (Builder $builder) => $builder->where('traffic_fine_results.plate', 'like', "{$search}%"))
            ->when(
                filled($filters['vehicle_type'] ?? null),
                fn (Builder $builder) => $builder->where('traffic_fine_results.vehicle_type', $filters['vehicle_type']),
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $builder) => $builder->where('traffic_fine_results.status', $filters['status']),
            )
            ->when(
                filled($filters['provider'] ?? null),
                fn (Builder $builder) => $builder->where('traffic_fine_results.provider', $filters['provider']),
            );

        match ($filters['state'] ?? 'all') {
            'active' => $query->where('traffic_fine_results.expires_at', '>', $now),
            'expiring' => $query
                ->where('traffic_fine_results.expires_at', '>', $now)
                ->where('traffic_fine_results.expires_at', '<=', $now->copy()->addHour()),
            'expired' => $query->where('traffic_fine_results.expires_at', '<=', $now),
            default => null,
        };
    }

    /**
     * @param  Builder<TrafficFineResult>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sort = (string) ($filters['sort'] ?? 'lookup_count');
        $direction = (string) ($filters['direction'] ?? 'desc');
        $sortColumn = match ($sort) {
            'last_lookup_at' => 'lookup_statistics.last_lookup_at',
            'expires_at' => 'traffic_fine_results.expires_at',
            'checked_at' => 'traffic_fine_results.checked_at',
            'plate' => 'traffic_fine_results.plate',
            default => 'lookup_count',
        };

        $query->orderBy($sortColumn, $direction);

        if ($sort === 'lookup_count') {
            $query->orderByDesc('lookup_statistics.last_lookup_at');
        }

        $query->orderByDesc('traffic_fine_results.id');
    }

    /** @return array<string, mixed> */
    private function serializeResult(TrafficFineResult $result, CarbonInterface $now): array
    {
        $expiresAt = $result->expires_at;
        $checkedAt = $result->checked_at;
        $remainingSeconds = max(0, $expiresAt->getTimestamp() - $now->getTimestamp());
        $lookupCount = (int) $result->getAttribute('lookup_count');
        $positiveCacheHits = (int) $result->getAttribute('positive_cache_hits');
        $lastLookupAt = $result->getAttribute('last_lookup_at');

        return [
            'id' => $result->id,
            'plate' => $result->plate,
            'vehicle_type' => $result->vehicle_type,
            'status' => $result->status,
            'violation_count' => $result->violation_count,
            'provider' => $result->provider,
            'checked_at' => $checkedAt->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
            'remaining_seconds' => $remainingSeconds,
            'cache_duration_seconds' => max(0, $expiresAt->getTimestamp() - $checkedAt->getTimestamp()),
            'cache_state' => $this->cacheState($expiresAt, $now),
            'lookup_count' => $lookupCount,
            'positive_cache_hits' => $positiveCacheHits,
            'provider_requests' => (int) $result->getAttribute('provider_requests'),
            'provider_errors' => (int) $result->getAttribute('provider_errors'),
            'cache_hit_rate' => $lookupCount > 0 ? round(($positiveCacheHits / $lookupCount) * 100, 2) : 0.0,
            'last_lookup_at' => $lastLookupAt?->toISOString(),
        ];
    }

    private function cacheState(CarbonInterface $expiresAt, CarbonInterface $now): string
    {
        if ($expiresAt->lessThanOrEqualTo($now)) {
            return 'expired';
        }

        return $expiresAt->lessThanOrEqualTo($now->copy()->addHour()) ? 'expiring' : 'active';
    }

    /** @return array<string, int|float|string> */
    private function summary(CarbonInterface $now, CarbonInterface $periodStart): array
    {
        $expiringAt = $now->copy()->addHour();
        $cache = TrafficFineResult::query()
            ->selectRaw(
                'COUNT(*) AS total_entries,
                SUM(CASE WHEN expires_at > ? THEN 1 ELSE 0 END) AS active_entries,
                SUM(CASE WHEN expires_at > ? AND expires_at <= ? THEN 1 ELSE 0 END) AS expiring_entries,
                SUM(CASE WHEN expires_at <= ? THEN 1 ELSE 0 END) AS expired_entries,
                SUM(CASE WHEN violation_count > 0 THEN 1 ELSE 0 END) AS violation_entries',
                [$now, $now, $expiringAt, $now],
            )
            ->first();
        $lookups = TrafficFineLookupLog::query()
            ->selectRaw(
                "COUNT(*) AS total_lookups,
                SUM(CASE WHEN source IN ('redis', 'database') THEN 1 ELSE 0 END) AS positive_cache_hits,
                SUM(CASE WHEN source = 'provider' THEN 1 ELSE 0 END) AS provider_requests",
            )
            ->where('created_at', '>=', $periodStart)
            ->first();

        $totalLookups = (int) ($lookups?->total_lookups ?? 0);
        $positiveCacheHits = (int) ($lookups?->positive_cache_hits ?? 0);

        return [
            'total_entries' => (int) ($cache?->total_entries ?? 0),
            'active_entries' => (int) ($cache?->active_entries ?? 0),
            'expiring_entries' => (int) ($cache?->expiring_entries ?? 0),
            'expired_entries' => (int) ($cache?->expired_entries ?? 0),
            'violation_entries' => (int) ($cache?->violation_entries ?? 0),
            'period_lookups' => $totalLookups,
            'period_positive_cache_hits' => $positiveCacheHits,
            'period_provider_requests' => (int) ($lookups?->provider_requests ?? 0),
            'positive_cache_hit_rate' => $totalLookups > 0 ? round(($positiveCacheHits / $totalLookups) * 100, 2) : 0.0,
        ];
    }
}
