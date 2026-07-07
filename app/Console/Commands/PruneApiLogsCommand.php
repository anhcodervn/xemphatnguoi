<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;

class PruneApiLogsCommand extends Command
{
    protected $signature = 'api:prune-logs';

    protected $description = 'Xoa api logs qua thoi gian luu tru cau hinh.';

    public function handle(): int
    {
        $retentionDays = max(1, (int) config('services.captcha.api_log_retention_days', 30));
        $cutoff = now()->subDays($retentionDays);

        $deleted = ApiLog::query()
            ->where('created_at', '<=', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Da xoa %d api log qua %d ngay.',
            $deleted,
            $retentionDays,
        ));

        return self::SUCCESS;
    }
}
