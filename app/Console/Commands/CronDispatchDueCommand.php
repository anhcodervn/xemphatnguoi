<?php

namespace App\Console\Commands;

use App\Features\Cron\Services\CronPlanService;
use App\Features\Cron\Services\CronRunnerService;
use App\Features\Cron\Services\CronScheduleService;
use App\Features\Cron\Services\CronUsageService;
use App\Jobs\RunHttpCronJob;
use App\Models\CronJob;
use App\Support\Enums\CronJobStatus;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CronDispatchDueCommand extends Command
{
    protected $signature = 'cron:dispatch-due {--limit=200}';

    protected $description = 'Dispatch due AutoCron HTTP jobs into the appropriate queue.';

    public function handle(
        CronPlanService $cronPlanService,
        CronScheduleService $cronScheduleService,
        CronUsageService $cronUsageService,
        CronRunnerService $cronRunnerService,
    ): int {
        $limit = max(1, min((int) $this->option('limit'), 1_000));

        $commandLock = Cache::lock('cron:dispatch-due', 50);
        if (! $commandLock->get()) {
            $this->warn('cron:dispatch-due is already running.');

            return self::SUCCESS;
        }

        try {
            $jobs = CronJob::query()
                ->with('user.userSubscriptions.package')
                ->where('status', CronJobStatus::Active)
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<=', now())
                ->orderBy('next_run_at')
                ->limit($limit)
                ->get();

            foreach ($jobs as $cronJob) {
                $lock = Cache::lock("cron-job:dispatch:{$cronJob->id}", 30);
                if (! $lock->get()) {
                    continue;
                }

                try {
                    $user = $cronJob->user;
                    if ($user === null) {
                        continue;
                    }

                    $subscription = $cronPlanService->activeSubscription($user);
                    if ($subscription === null) {
                        $cronRunnerService->recordBlocked($cronJob, 'No active subscription available for this cron job.');
                        $this->advanceNextRun($cronJob, $cronScheduleService);

                        continue;
                    }

                    $limits = $cronPlanService->limitsForSubscription($subscription);
                    if (($quotaReason = $cronUsageService->exceedsQuota($user, $limits)) !== null) {
                        $cronRunnerService->recordBlocked($cronJob, $quotaReason);
                        $this->advanceNextRun($cronJob, $cronScheduleService);

                        continue;
                    }

                    $this->advanceNextRun($cronJob, $cronScheduleService);

                    RunHttpCronJob::dispatch($cronJob->id, (string) Str::uuid())
                        ->onQueue((string) ($limits['queue_name'] ?? 'cron-default'));
                } finally {
                    $lock->release();
                }
            }
        } finally {
            $commandLock->release();
        }

        return self::SUCCESS;
    }

    private function advanceNextRun(CronJob $cronJob, CronScheduleService $cronScheduleService): void
    {
        $now = CarbonImmutable::now($cronJob->timezone ?: config('app.timezone'));
        $from = $cronJob->next_run_at !== null
            ? CarbonImmutable::instance($cronJob->next_run_at)
            : $now;

        if ($from->lt($now)) {
            $from = $now;
        }

        $cronJob->forceFill([
            'next_run_at' => $cronScheduleService->calculateNextRun($cronJob, $from, false),
        ])->save();
    }
}
