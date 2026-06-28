<?php

namespace App\Features\Client\CronJob\Controllers;

use App\Exceptions\ApiException;
use App\Features\Client\CronJob\Requests\CronJobIndexRequest;
use App\Features\Client\CronJob\Requests\CronJobLogIndexRequest;
use App\Features\Client\CronJob\Requests\StoreCronJobRequest;
use App\Features\Client\CronJob\Requests\UpdateCronJobRequest;
use App\Features\Cron\Resources\CronJobLogResource;
use App\Features\Cron\Resources\CronJobResource;
use App\Features\Cron\Services\CronJobConfigurationService;
use App\Features\Cron\Services\CronPlanService;
use App\Features\Cron\Services\CronScheduleService;
use App\Features\Cron\Services\CronUsageService;
use App\Http\Controllers\Controller;
use App\Jobs\RunHttpCronJob;
use App\Models\CronAlertChannel;
use App\Models\CronJob;
use App\Models\User;
use App\Utils\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CronJobController extends Controller
{
    public function __construct(
        private readonly CronPlanService $cronPlanService,
        private readonly CronScheduleService $cronScheduleService,
        private readonly CronJobConfigurationService $cronJobConfigurationService,
        private readonly CronUsageService $cronUsageService,
    ) {}

    public function index(CronJobIndexRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $perPage = min(max((int) $request->validated('per_page', 15), 1), 100);
        $filters = $request->validated();

        $jobs = $user->cronJobs()
            ->with('alertChannels')
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
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $availableGroups = $user->cronJobs()
            ->whereNotNull('group_name')
            ->where('group_name', '!=', '')
            ->orderBy('group_name')
            ->distinct()
            ->pluck('group_name')
            ->values()
            ->all();

        $usage = $this->cronUsageService->usageSummary($user);
        $limits = $this->cronPlanService->limitsForUser($user);

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
                'total_jobs' => $user->cronJobs()->count(),
                'active_jobs' => $user->cronJobs()->where('status', 'active')->count(),
                'runs_today' => $usage['today'],
                'runs_month' => $usage['month'],
                'failed_today' => $user->cronJobLogs()->whereDate('created_at', now()->toDateString())->whereIn('status', ['failed', 'timeout', 'error', 'blocked'])->count(),
                'quota' => [
                    'daily' => $limits['daily_run_quota'] ?? null,
                    'monthly' => $limits['monthly_run_quota'] ?? null,
                ],
            ],
        ]));
    }

    public function store(StoreCronJobRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $subscription = $this->cronPlanService->requireActiveSubscription($user);
        $this->cronPlanService->ensureCronJobCapacity($user);
        $limits = $this->cronPlanService->limitsForSubscription($subscription);

        $payload = $this->cronJobConfigurationService->normalizeAndValidate($request->validated(), $limits);
        $cronJob = new CronJob([
            ...$payload,
            'user_id' => $user->id,
        ]);
        $cronJob->next_run_at = $this->cronScheduleService->calculateNextRun($cronJob, CarbonImmutable::now($cronJob->timezone));
        $cronJob->save();

        $this->syncAlertChannels($cronJob, $request->validated('alert_channel_ids', []), $user, $limits);
        $this->cronPlanService->syncSubscriptionUsage($user);

        return response()->json(ApiResponse::success(
            message: 'Tạo cron job thành công.',
            data: ['cron_job' => CronJobResource::make($cronJob->fresh('alertChannels'))->resolve()],
        ), 201);
    }

    public function show(CronJob $cronJob, Request $request): JsonResponse
    {
        $cronJob = $this->ownedJob($cronJob, $this->user($request));

        return response()->json(ApiResponse::success(data: [
            'cron_job' => CronJobResource::make($cronJob->load('alertChannels'))->resolve(),
        ]));
    }

    public function update(UpdateCronJobRequest $request, CronJob $cronJob): JsonResponse
    {
        $user = $this->user($request);
        $cronJob = $this->ownedJob($cronJob, $user);
        $subscription = $this->cronPlanService->requireActiveSubscription($user);
        $limits = $this->cronPlanService->limitsForSubscription($subscription);
        $payload = $this->cronJobConfigurationService->normalizeAndValidate($request->validated(), $limits);

        $cronJob->fill($payload);
        $cronJob->next_run_at = $this->cronScheduleService->calculateNextRun(
            $cronJob,
            $cronJob->last_run_at !== null ? CarbonImmutable::instance($cronJob->last_run_at) : CarbonImmutable::now($cronJob->timezone),
        );
        $cronJob->save();

        $this->syncAlertChannels($cronJob, $request->validated('alert_channel_ids', []), $user, $limits);

        return response()->json(ApiResponse::success(
            message: 'Cập nhật cron job thành công.',
            data: ['cron_job' => CronJobResource::make($cronJob->fresh('alertChannels'))->resolve()],
        ));
    }

    public function destroy(CronJob $cronJob, Request $request): JsonResponse
    {
        $user = $this->user($request);
        $cronJob = $this->ownedJob($cronJob, $user);
        $cronJob->delete();
        $this->cronPlanService->syncSubscriptionUsage($user);

        return response()->json(ApiResponse::success(message: 'Đã xóa cron job.'));
    }

    public function pause(CronJob $cronJob, Request $request): JsonResponse
    {
        $cronJob = $this->ownedJob($cronJob, $this->user($request));
        $cronJob->forceFill(['status' => 'paused'])->save();

        return response()->json(ApiResponse::success(message: 'Cron job đã được tạm dừng.'));
    }

    public function resume(CronJob $cronJob, Request $request): JsonResponse
    {
        $user = $this->user($request);
        $cronJob = $this->ownedJob($cronJob, $user);
        $subscription = $this->cronPlanService->requireActiveSubscription($user);
        $limits = $this->cronPlanService->limitsForSubscription($subscription);

        $cronJob->forceFill([
            'status' => 'active',
            'next_run_at' => $this->cronScheduleService->calculateNextRun($cronJob, CarbonImmutable::now($cronJob->timezone)),
        ])->save();

        return response()->json(ApiResponse::success(data: [
            'cron_job' => CronJobResource::make($cronJob->fresh('alertChannels'))->resolve(),
            'limits' => $limits,
        ]));
    }

    public function runNow(CronJob $cronJob, Request $request): JsonResponse
    {
        $user = $this->user($request);
        $cronJob = $this->ownedJob($cronJob, $user);
        $subscription = $this->cronPlanService->requireActiveSubscription($user);
        $limits = $this->cronPlanService->limitsForSubscription($subscription);

        if (! (bool) ($limits['allow_run_now'] ?? false)) {
            return response()->json(ApiResponse::error('Gói hiện tại chưa hỗ trợ chạy ngay.'), 422);
        }

        if (($quotaReason = $this->cronUsageService->exceedsQuota($user, $limits)) !== null) {
            return response()->json(ApiResponse::error($quotaReason), 422);
        }

        RunHttpCronJob::dispatch($cronJob->id, (string) Str::uuid())
            ->onQueue((string) ($limits['queue_name'] ?? 'cron-default'));

        return response()->json(ApiResponse::success(message: 'Cron job đã được đưa vào hàng đợi chạy ngay.'));
    }

    public function logs(CronJobLogIndexRequest $request, CronJob $cronJob): JsonResponse
    {
        $cronJob = $this->ownedJob($cronJob, $this->user($request));
        $perPage = min(max((int) $request->validated('per_page', 20), 1), 100);
        $filters = $request->validated();

        $logs = $cronJob->logs()
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

    public function globalLogs(CronJobLogIndexRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $perPage = min(max((int) $request->validated('per_page', 20), 1), 100);
        $filters = $request->validated();

        $logs = $user->cronJobLogs()
            ->with('cronJob')
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

    public function stats(CronJob $cronJob, Request $request): JsonResponse
    {
        $cronJob = $this->ownedJob($cronJob, $this->user($request));
        $recentLogs = $cronJob->logs()->latest('id')->limit(10)->get();
        $totalRuns = max(1, (int) $cronJob->total_runs);

        return response()->json(ApiResponse::success(data: [
            'summary' => [
                'total_runs' => $cronJob->total_runs,
                'total_success' => $cronJob->total_success,
                'total_failed' => $cronJob->total_failed,
                'success_rate' => round(($cronJob->total_success / $totalRuns) * 100, 2),
                'consecutive_failures' => $cronJob->consecutive_failures,
                'last_status' => $cronJob->last_status?->value ?? $cronJob->last_status,
                'last_run_at' => $cronJob->last_run_at?->toISOString(),
                'next_run_at' => $cronJob->next_run_at?->toISOString(),
            ],
            'recent_logs' => CronJobLogResource::collection($recentLogs)->resolve(),
        ]));
    }

    private function syncAlertChannels(CronJob $cronJob, array $channelIds, User $user, array $limits): void
    {
        if ($channelIds === []) {
            $cronJob->alertChannels()->sync([]);

            return;
        }

        if (! (bool) ($limits['allow_alerts'] ?? false)) {
            throw new ApiException('Gói hiện tại chưa hỗ trợ alert channels.', 422);
        }

        if (count($channelIds) > (int) ($limits['max_alert_channels'] ?? 0)) {
            throw new ApiException('Số lượng alert channels vượt quá giới hạn gói.', 422);
        }

        $ownedIds = CronAlertChannel::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $channelIds)
            ->pluck('id')
            ->all();

        $cronJob->alertChannels()->sync($ownedIds);
    }

    private function ownedJob(CronJob $cronJob, User $user): CronJob
    {
        abort_unless($cronJob->user_id === $user->id, 404);

        return $cronJob;
    }

    private function user($request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
