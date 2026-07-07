<?php

namespace App\Console\Commands;

use App\Features\Captcha\Services\CaptchaSourceBalanceService;
use Illuminate\Console\Command;

class SyncCaptchaSourceBalancesCommand extends Command
{
    protected $signature = 'captcha:sync-source-balances';

    protected $description = 'Dong bo so du cua cac nguon captcha ho tro get balance.';

    public function handle(CaptchaSourceBalanceService $balanceService): int
    {
        $summary = $balanceService->syncActiveSources();

        $this->info(sprintf(
            'Da dong bo %d/%d nguon. Bo qua: %d. Loi: %d.',
            $summary['updated'],
            $summary['total'],
            $summary['skipped'],
            $summary['failed'],
        ));

        return self::SUCCESS;
    }
}
