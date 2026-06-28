<?php

namespace App\Console\Commands;

use App\Features\Cron\Services\CronScheduleService;
use App\Models\CronJob;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CronRecalculateNextRunCommand extends Command
{
    protected $signature = 'cron:recalculate-next-run {--only-missing}';

    protected $description = 'Recalculate next_run_at for AutoCron jobs.';

    public function handle(CronScheduleService $cronScheduleService): int
    {
        $onlyMissing = (bool) $this->option('only-missing');

        CronJob::query()
            ->when($onlyMissing, fn ($query) => $query->whereNull('next_run_at'))
            ->chunkById(100, function ($cronJobs) use ($cronScheduleService): void {
                foreach ($cronJobs as $cronJob) {
                    $from = $cronJob->last_run_at !== null
                        ? CarbonImmutable::instance($cronJob->last_run_at)
                        : CarbonImmutable::now($cronJob->timezone ?: config('app.timezone'));

                    $cronJob->forceFill([
                        'next_run_at' => $cronScheduleService->calculateNextRun($cronJob, $from),
                    ])->save();
                }
            });

        $this->info('Recalculated next_run_at for cron jobs.');

        return self::SUCCESS;
    }
}
