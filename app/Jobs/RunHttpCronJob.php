<?php

namespace App\Jobs;

use App\Features\Cron\Services\CronRunnerService;
use App\Models\CronJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunHttpCronJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 1;

    public function __construct(
        public readonly int $cronJobId,
        public readonly string $runUuid,
        public readonly int $attemptNumber = 1,
    ) {}

    public function handle(CronRunnerService $cronRunnerService): void
    {
        $cronJob = CronJob::query()->findOrFail($this->cronJobId);

        $cronRunnerService->run($cronJob, $this->runUuid, $this->attemptNumber);
    }
}
