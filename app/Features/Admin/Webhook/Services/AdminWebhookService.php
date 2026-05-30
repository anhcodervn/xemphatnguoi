<?php

namespace App\Features\Admin\Webhook\Services;

use App\Features\Admin\Webhook\Resources\AdminWebhookLogResource;
use App\Features\Admin\Webhook\Resources\AdminWebhookResource;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminWebhookService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateWebhooks(array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $webhooks = $this->webhookQuery($filters)
            ->paginate($perPage)
            ->withQueryString();

        $totalLogs = WebhookLog::query()->count();
        $successLogs = WebhookLog::query()->whereBetween('status_code', [200, 299])->count();

        return [
            'data' => AdminWebhookResource::collection($webhooks->getCollection())->resolve(),
            'meta' => $this->paginationMeta($webhooks),
            'stats' => [
                'total_webhooks' => Webhook::query()->count(),
                'enabled_webhooks' => Webhook::query()->where('status', 'active')->count(),
                'failed_today' => WebhookLog::query()
                    ->whereDate('created_at', today())
                    ->where(function (Builder $query): void {
                        $query->whereNull('status_code')->orWhere('status_code', '>=', 400);
                    })
                    ->count(),
                'success_rate' => $totalLogs > 0 ? round(($successLogs / $totalLogs) * 100, 2) : 0.0,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function webhookDetail(Webhook $webhook): array
    {
        $webhook->load(['user', 'bankAccount']);

        $recentLogs = WebhookLog::query()
            ->where('webhook_id', $webhook->id)
            ->latest('id')
            ->limit(10)
            ->get();

        return [
            'webhook' => $webhook,
            'recent_logs' => AdminWebhookLogResource::collection($recentLogs)->resolve(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function paginateLogs(Webhook $webhook, array $filters = []): array
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $logs = WebhookLog::query()
            ->where('webhook_id', $webhook->id)
            ->when(filled($filters['event'] ?? null), fn (Builder $query) => $query->where('event_keyword', $filters['event']))
            ->when(filled($filters['status'] ?? null), function (Builder $query) use ($filters): void {
                if ($filters['status'] === 'success') {
                    $query->whereBetween('status_code', [200, 299]);
                }

                if ($filters['status'] === 'failed') {
                    $query->where(function (Builder $builder): void {
                        $builder->whereNull('status_code')->orWhere('status_code', '>=', 400);
                    });
                }
            })
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'data' => AdminWebhookLogResource::collection($logs->getCollection())->resolve(),
            'meta' => $this->paginationMeta($logs),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function webhookQuery(array $filters): Builder
    {
        return Webhook::query()
            ->with(['user', 'bankAccount'])
            ->withCount([
                'logs as success_logs_count' => fn (Builder $query) => $query->whereBetween('status_code', [200, 299]),
                'logs as failed_logs_count' => fn (Builder $query) => $query->where(function (Builder $builder): void {
                    $builder->whereNull('status_code')->orWhere('status_code', '>=', 400);
                }),
            ])
            ->withMax('logs', 'created_at')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('url', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            if (is_numeric($search)) {
                                $userQuery->orWhere('id', (int) $search);
                            }

                            $userQuery->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['user_id'] ?? null), fn (Builder $query) => $query->where('user_id', $filters['user_id']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['event'] ?? null), fn (Builder $query) => $query->whereJsonContains('events', $filters['event']))
            ->latest('id');
    }

    /**
     * @return array<string, int>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
