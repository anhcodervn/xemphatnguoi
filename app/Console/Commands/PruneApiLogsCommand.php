<?php

namespace App\Console\Commands;

use App\Models\ApiLog;
use Illuminate\Console\Command;

class PruneApiLogsCommand extends Command
{
    protected $signature = 'api:prune-logs';

    protected $description = 'Xóa API logs quá thời gian lưu trữ cấu hình.';

    public function handle(): int
    {
        $retentionDays = max(1, (int) config('services.api.log_retention_days', 30));
        $cutoff = now()->subDays($retentionDays);

        $deleted = ApiLog::query()
            ->where('created_at', '<=', $cutoff)
            ->delete();

        $this->info(sprintf(
            'Đã xóa %d API log quá %d ngày.',
            $deleted,
            $retentionDays,
        ));

        return self::SUCCESS;
    }
}
