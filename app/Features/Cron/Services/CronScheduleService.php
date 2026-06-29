<?php

namespace App\Features\Cron\Services;

use App\Models\CronJob;
use Carbon\CarbonImmutable;
use Cron\CronExpression;

class CronScheduleService
{
    public function calculateNextRun(CronJob $cronJob, ?CarbonImmutable $from = null, bool $allowCurrentSlot = true): ?CarbonImmutable
    {
        $base = $from ?? CarbonImmutable::now($cronJob->timezone ?: config('app.timezone'));

        if (is_string($cronJob->cron_expression) && trim($cronJob->cron_expression) !== '') {
            return CarbonImmutable::instance(
                CronExpression::factory($cronJob->cron_expression)
                    ->getNextRunDate($base, 0, $allowCurrentSlot, $cronJob->timezone ?: config('app.timezone'))
            );
        }

        if (is_int($cronJob->interval_seconds) && $cronJob->interval_seconds > 0) {
            return $base->addSeconds($cronJob->interval_seconds);
        }

        return null;
    }
}
