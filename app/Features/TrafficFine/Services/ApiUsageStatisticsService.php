<?php

namespace App\Features\TrafficFine\Services;

use App\Models\ApiLog;
use App\Models\User;
use App\Models\WalletTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ApiUsageStatisticsService
{
    /**
     * @return array<string, int|string>
     */
    public function summary(?User $user = null): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();

        $metrics = $this->query($user)
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw("SUM(CASE WHEN billing_status = 'charged' THEN 1 ELSE 0 END) as charged_requests")
            ->selectRaw('SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as failed_requests')
            ->selectRaw("SUM(CASE WHEN billing_status = 'charged' THEN charged_amount ELSE 0 END) as total_amount")
            ->selectRaw("SUM(CASE WHEN created_at >= ? AND billing_status = 'charged' THEN 1 ELSE 0 END) as requests_today", [$today])
            ->selectRaw("SUM(CASE WHEN created_at >= ? AND billing_status = 'charged' THEN charged_amount ELSE 0 END) as amount_today", [$today])
            ->selectRaw("SUM(CASE WHEN created_at >= ? AND billing_status = 'charged' THEN 1 ELSE 0 END) as requests_month", [$month])
            ->selectRaw("SUM(CASE WHEN created_at >= ? AND billing_status = 'charged' THEN charged_amount ELSE 0 END) as amount_month", [$month])
            ->first();

        return [
            'total_requests' => (int) ($metrics?->total_requests ?? 0),
            'charged_requests' => (int) ($metrics?->charged_requests ?? 0),
            'failed_requests' => (int) ($metrics?->failed_requests ?? 0),
            'total_amount' => (string) WalletTransaction::query()
                ->where('reference_type', 'traffic_fine_api_request')
                ->where('status', 'success')
                ->when($user instanceof User, fn (Builder $query) => $query->whereHas(
                    'wallet',
                    fn (Builder $walletQuery) => $walletQuery->whereBelongsTo($user),
                ))
                ->sum('amount'),
            'requests_today' => (int) ($metrics?->requests_today ?? 0),
            'amount_today' => (string) ($metrics?->amount_today ?? '0.00'),
            'requests_month' => (int) ($metrics?->requests_month ?? 0),
            'amount_month' => (string) ($metrics?->amount_month ?? '0.00'),
        ];
    }

    /**
     * @return list<array{date: string, label: string, requests: int, amount: string}>
     */
    public function daily(?User $user = null, int $days = 14): array
    {
        $safeDays = min(max($days, 7), 90);
        $start = CarbonImmutable::today()->subDays($safeDays - 1);

        $rows = $this->query($user)
            ->where('created_at', '>=', $start->startOfDay())
            ->where('billing_status', 'charged')
            ->selectRaw('DATE(created_at) as usage_date, COUNT(*) as request_count, SUM(charged_amount) as amount')
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('usage_date');

        return collect(range(0, $safeDays - 1))
            ->map(function (int $offset) use ($start, $rows): array {
                $date = $start->addDays($offset);
                $row = $rows->get($date->toDateString());

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('d/m'),
                    'requests' => (int) ($row?->request_count ?? 0),
                    'amount' => (string) ($row?->amount ?? '0.00'),
                ];
            })
            ->all();
    }

    private function query(?User $user = null): Builder
    {
        return ApiLog::query()
            ->where('endpoint', 'api/v1/lookup')
            ->where('method', 'GET')
            ->when($user instanceof User, fn (Builder $query) => $query->whereBelongsTo($user));
    }
}
