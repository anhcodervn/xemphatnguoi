<?php

namespace App\Console\Commands;

use App\Features\Client\Subscription\Services\SubscriptionAutoRenewService;
use Illuminate\Console\Command;

class AutoRenewSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:auto-renew-due {--limit=100}';

    protected $description = 'Attempt automatic wallet renewals for expired subscriptions that enabled auto-renew.';

    public function handle(SubscriptionAutoRenewService $subscriptionAutoRenewService): int
    {
        $summary = $subscriptionAutoRenewService->processDueSubscriptions((int) $this->option('limit'));

        $this->info(sprintf(
            'Processed %d subscriptions. Renewed: %d, failed: %d, skipped: %d, expired only: %d.',
            $summary['processed'],
            $summary['renewed'],
            $summary['failed'],
            $summary['skipped'],
            $summary['expired_only'],
        ));

        return self::SUCCESS;
    }
}
