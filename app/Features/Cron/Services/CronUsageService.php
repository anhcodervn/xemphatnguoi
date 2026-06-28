<?php

namespace App\Features\Cron\Services;

use App\Models\CronUsageCounter;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CronUsageService
{
    /**
     * @return array{today:int,month:int}
     */
    public function usageSummary(User $user, ?CarbonInterface $now = null): array
    {
        $now ??= now();

        $today = (int) CronUsageCounter::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->value('total_runs');

        $month = (int) CronUsageCounter::query()
            ->where('user_id', $user->id)
            ->where('month', $now->format('Y-m'))
            ->sum('total_runs');

        return [
            'today' => $today,
            'month' => $month,
        ];
    }

    public function exceedsQuota(User $user, array $limits, ?CarbonInterface $now = null): ?string
    {
        $usage = $this->usageSummary($user, $now);

        $dailyLimit = $limits['daily_run_quota'] ?? null;
        if (is_numeric($dailyLimit) && $dailyLimit >= 0 && $usage['today'] >= (int) $dailyLimit) {
            return 'Daily run quota exceeded.';
        }

        $monthlyLimit = $limits['monthly_run_quota'] ?? null;
        if (is_numeric($monthlyLimit) && $monthlyLimit >= 0 && $usage['month'] >= (int) $monthlyLimit) {
            return 'Monthly run quota exceeded.';
        }

        return null;
    }

    public function recordRun(User $user, bool $successful, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        DB::transaction(function () use ($user, $successful, $now): void {
            $counter = CronUsageCounter::query()
                ->where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $counter instanceof CronUsageCounter) {
                $counter = CronUsageCounter::query()->create([
                    'user_id' => $user->id,
                    'date' => $now->toDateString(),
                    'month' => $now->format('Y-m'),
                    'total_runs' => 0,
                    'successful_runs' => 0,
                    'failed_runs' => 0,
                ]);
            }

            $counter->forceFill([
                'month' => $now->format('Y-m'),
                'total_runs' => $counter->total_runs + 1,
                'successful_runs' => $counter->successful_runs + ($successful ? 1 : 0),
                'failed_runs' => $counter->failed_runs + ($successful ? 0 : 1),
            ])->save();
        });
    }

    public function pruneOldCounters(int $retentionDays = 120): int
    {
        return CronUsageCounter::query()
            ->whereDate('date', '<', now()->subDays($retentionDays)->toDateString())
            ->delete();
    }
}
