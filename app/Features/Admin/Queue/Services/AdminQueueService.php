<?php

namespace App\Features\Admin\Queue\Services;

use App\Models\QueueLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class AdminQueueService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $pendingByQueue = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->groupBy('queue')
            ->get();

        $failedByQueue = DB::table('failed_jobs')
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->groupBy('queue')
            ->get();

        $logStats = QueueLog::query()
            ->select('queue', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('queue', 'status')
            ->get();

        $queues = collect()
            ->merge($pendingByQueue->pluck('queue'))
            ->merge($failedByQueue->pluck('queue'))
            ->merge($logStats->pluck('queue'))
            ->filter()
            ->unique()
            ->values();

        $queueItems = $queues->map(function (string $queue) use ($pendingByQueue, $failedByQueue, $logStats): array {
            $processing = (int) ($logStats->first(fn ($row): bool => $row->queue === $queue && $row->status === 'processing')->total ?? 0);
            $success = (int) ($logStats->first(fn ($row): bool => $row->queue === $queue && $row->status === 'success')->total ?? 0);
            $failed = (int) ($logStats->first(fn ($row): bool => $row->queue === $queue && $row->status === 'failed')->total ?? 0);

            return [
                'queue' => $queue,
                'pending_jobs' => (int) ($pendingByQueue->firstWhere('queue', $queue)->total ?? 0),
                'failed_jobs' => (int) ($failedByQueue->firstWhere('queue', $queue)->total ?? 0),
                'processing_logs' => $processing,
                'success_logs' => $success,
                'failed_logs' => $failed,
            ];
        })->values()->all();

        return [
            'summary' => [
                'total_pending_jobs' => (int) DB::table('jobs')->count(),
                'total_failed_jobs' => (int) DB::table('failed_jobs')->count(),
                'total_processing_logs' => (int) QueueLog::query()->where('status', 'processing')->count(),
                'total_failed_logs' => (int) QueueLog::query()->where('status', 'failed')->count(),
            ],
            'queues' => $queueItems,
        ];
    }

    /**
     * @param array{queue?:string,status?:string,search?:string,per_page?:int} $filters
     * @return array<string, mixed>
     */
    public function logs(array $filters): array
    {
        $queue = trim((string) ($filters['queue'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $logs = QueueLog::query()
            ->when($queue !== '', fn (Builder $query) => $query->where('queue', $queue))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('job_uuid', 'like', "%{$search}%")
                        ->orWhere('job_name', 'like', "%{$search}%")
                        ->orWhere('error_message', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);

        return [
            'data' => $logs->items(),
            'meta' => $this->meta($logs),
        ];
    }

    /**
     * @param array{queue?:string,search?:string,per_page?:int} $filters
     * @return array<string, mixed>
     */
    public function failedJobs(array $filters): array
    {
        $queue = trim((string) ($filters['queue'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $perPage = max(1, min((int) ($filters['per_page'] ?? 20), 100));

        $failedJobs = DB::table('failed_jobs')
            ->when($queue !== '', fn ($query) => $query->where('queue', $queue))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('uuid', 'like', "%{$search}%")
                        ->orWhere('queue', 'like', "%{$search}%")
                        ->orWhere('exception', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return [
            'data' => collect($failedJobs->items())->map(function (object $item): array {
                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'queue' => $item->queue,
                    'connection' => $item->connection,
                    'failed_at' => $item->failed_at,
                    'exception' => $item->exception,
                ];
            })->values()->all(),
            'meta' => $this->meta($failedJobs),
        ];
    }

    public function retryFailedJob(int|string $id): void
    {
        Artisan::call('queue:retry', [
            'id' => [(string) $id],
            '--no-interaction' => true,
        ]);
    }

    public function deleteFailedJob(int|string $id): void
    {
        Artisan::call('queue:forget', [
            'id' => (string) $id,
            '--no-interaction' => true,
        ]);
    }

    /**
     * @return array{current_page:int,last_page:int,per_page:int,total:int}
     */
    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
