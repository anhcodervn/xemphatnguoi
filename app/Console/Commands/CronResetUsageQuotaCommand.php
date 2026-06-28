<?php

namespace App\Console\Commands;

use App\Features\Cron\Services\CronUsageService;
use Illuminate\Console\Command;

class CronResetUsageQuotaCommand extends Command
{
    protected $signature = 'cron:reset-usage-quota {--retention-days=120}';

    protected $description = 'Reset/prune stale AutoCron usage counters.';

    public function handle(CronUsageService $cronUsageService): int
    {
        $deleted = $cronUsageService->pruneOldCounters(max(30, (int) $this->option('retention-days')));

        $this->info(sprintf('Pruned %d old usage counter rows.', $deleted));

        return self::SUCCESS;
    }
}
