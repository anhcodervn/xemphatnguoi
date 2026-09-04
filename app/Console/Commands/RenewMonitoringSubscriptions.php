<?php

namespace App\Console\Commands;

use App\Features\Client\MonitoringPlan\Actions\RenewMonitoringSubscriptionAction;
use App\Models\MonitoringSubscription;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class RenewMonitoringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring-subscriptions:renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gia hạn các gói theo dõi đã hết hạn và đang bật tự gia hạn';

    /**
     * Execute the console command.
     */
    public function handle(RenewMonitoringSubscriptionAction $renewSubscription): int
    {
        $renewed = 0;
        $insufficientBalance = 0;
        $failed = 0;

        MonitoringSubscription::query()
            ->dueForRenewal()
            ->select('id')
            ->chunkById(100, function (Collection $subscriptions) use ($renewSubscription, &$renewed, &$insufficientBalance, &$failed): void {
                foreach ($subscriptions as $subscription) {
                    try {
                        $result = $renewSubscription->handle((int) $subscription->id);

                        if ($result === RenewMonitoringSubscriptionAction::RESULT_RENEWED) {
                            $renewed++;
                        } elseif ($result === RenewMonitoringSubscriptionAction::RESULT_INSUFFICIENT_BALANCE) {
                            $insufficientBalance++;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });

        $this->components->info("Đã gia hạn: {$renewed}; chờ đủ số dư: {$insufficientBalance}; lỗi: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
