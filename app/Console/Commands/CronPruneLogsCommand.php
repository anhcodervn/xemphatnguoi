<?php

namespace App\Console\Commands;

use App\Features\Cron\Services\CronPlanService;
use App\Models\CronJob;
use App\Models\CronJobLog;
use Illuminate\Console\Command;

class CronPruneLogsCommand extends Command
{
    protected $signature = 'cron:prune-logs';

    protected $description = 'Prune AutoCron logs according to per-job package limits.';

    public function handle(CronPlanService $cronPlanService): int
    {
        CronJob::query()->with('user.userSubscriptions.package')->chunkById(100, function ($cronJobs) use ($cronPlanService): void {
            foreach ($cronJobs as $cronJob) {
                $limits = $cronPlanService->limitsForUser($cronJob->user);
                $maxLogsPerJob = max(1, (int) ($limits['max_logs_per_job'] ?? 100));

                $idsToKeep = CronJobLog::query()
                    ->where('cron_job_id', $cronJob->id)
                    ->latest('id')
                    ->limit($maxLogsPerJob)
                    ->pluck('id');

                if ($idsToKeep->isNotEmpty()) {
                    CronJobLog::query()
                        ->where('cron_job_id', $cronJob->id)
                        ->whereNotIn('id', $idsToKeep)
                        ->delete();
                }
            }
        });

        $this->info('Cron logs pruned successfully.');

        return self::SUCCESS;
    }
}
