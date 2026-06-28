<?php

namespace App\Features\Admin\CronJob\Controllers;

use App\Features\Admin\CronJob\Requests\AdminCronJobIndexRequest;
use App\Features\Admin\CronJob\Requests\UpdateAdminCronJobStatusRequest;
use App\Features\Client\CronJob\Requests\CronJobLogIndexRequest;
use App\Features\Cron\Resources\CronJobLogResource;
use App\Features\Cron\Resources\CronJobResource;
use App\Http\Controllers\Controller;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class CronJobController extends Controller
{
    public function index(AdminCronJobIndexRequest $request): JsonResponse
    {
        $perPage = min(max((int) $request->validated('per_page', 20), 1), 100);
        $filters = $request->validated();

        $jobs = CronJob::query()
            ->with(['user', 'alertChannels'])
            ->when(($filters['search'] ?? '') !== '', function ($query) use ($filters): void {
                $search = trim((string) $filters['search']);
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('group_name', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when(($filters['group_name'] ?? '') !== '', fn ($query) => $query->where('group_name', trim((string) $filters['group_name'])))
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when(($filters['method'] ?? '') !== '', fn ($query) => $query->where('method', $filters['method']))
            ->when(($filters['user_id'] ?? null) !== null, fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(($filters['package'] ?? '') !== '', function ($query) use ($filters): void {
                $package = trim((string) $filters['package']);
                $query->whereHas('user.userSubscriptions', function ($subscriptionQuery) use ($package): void {
                    $subscriptionQuery->where('package_name', 'like', "%{$package}%");
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $availableGroups = CronJob::query()
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->orderBy('group_name')
            ->distinct()
            ->pluck('group_name')
            ->values()
            ->all();

        return response()->json(ApiResponse::success(data: [
            'data' => CronJobResource::collection($jobs->getCollection())->resolve(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
            'filters' => [
                'groups' => $availableGroups,
            ],
            'summary' => [
                'total_jobs' => CronJob::query()->count(),
                'active_jobs' => CronJob::query()->where('status', 'active')->count(),
                'paused_jobs' => CronJob::query()->where('status', 'paused')->count(),
                'disabled_jobs' => CronJob::query()->where('status', 'disabled')->count(),
                'runs_today' => CronJobLog::query()->whereDate('created_at', now()->toDateString())->count(),
                'failed_today' => CronJobLog::query()->whereDate('created_at', now()->toDateString())->whereIn('status', ['failed', 'timeout', 'error', 'blocked'])->count(),
            ],
        ]));
    }

    public function show(CronJob $cronJob): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'cron_job' => CronJobResource::make($cronJob->load(['user', 'alertChannels']))->resolve(),
        ]));
    }

    public function updateStatus(UpdateAdminCronJobStatusRequest $request, CronJob $cronJob): JsonResponse
    {
        $cronJob->forceFill([
            'status' => $request->validated('status'),
        ])->save();

        return response()->json(ApiResponse::success(
            message: 'Đã cập nhật trạng thái cron job.',
            data: ['cron_job' => CronJobResource::make($cronJob->fresh(['user', 'alertChannels']))->resolve()],
        ));
    }

    public function destroy(CronJob $cronJob): JsonResponse
    {
        $cronJob->delete();

        return response()->json(ApiResponse::success(message: 'Đã xóa cron job.'));
    }

    public function logs(CronJobLogIndexRequest $request, CronJob $cronJob): JsonResponse
    {
        return $this->logPayload($request, fn () => $cronJob->logs());
    }

    public function globalLogs(CronJobLogIndexRequest $request): JsonResponse
    {
        return $this->logPayload($request, fn () => CronJobLog::query()->with('cronJob', 'user'));
    }

    private function logPayload(CronJobLogIndexRequest $request, callable $builderFactory): JsonResponse
    {
        $perPage = min(max((int) $request->validated('per_page', 20), 1), 100);
        $filters = $request->validated();
        $builder = $builderFactory();

        $logs = $builder
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when(($filters['status_code'] ?? null) !== null, fn ($query) => $query->where('status_code', $filters['status_code']))
            ->when(($filters['date_from'] ?? '') !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(($filters['date_to'] ?? '') !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json(ApiResponse::success(data: [
            'data' => CronJobLogResource::collection($logs->getCollection())->resolve(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]));
    }
}
