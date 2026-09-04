<?php

namespace App\Console\Commands;

use App\Features\TrafficFine\Services\VehicleMonitoringSettingsService;
use App\Jobs\CheckVehicleMonitoringJob;
use App\Models\VehicleMonitoring;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DispatchVehicleMonitoringChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'traffic-fines:dispatch-monitoring-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đưa các biển số đã đến chu kỳ theo dõi vào hàng đợi kiểm tra';

    /**
     * Execute the console command.
     */
    public function handle(VehicleMonitoringSettingsService $settings): int
    {
        $dispatchedCount = 0;
        $intervalHours = $settings->intervalHours();
        $dueBefore = now()->subHours($intervalHours);

        VehicleMonitoring::query()
            ->where('enabled', true)
            ->where(fn ($query) => $query->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<=', $dueBefore))
            ->whereHas('user.monitoringSubscriptions', fn ($query) => $query->active())
            ->select('id')
            ->chunkById(100, function (Collection $monitorings) use (&$dispatchedCount, $dueBefore): void {
                foreach ($monitorings as $monitoring) {
                    if ($this->dispatchIfDue($monitoring->id, $dueBefore)) {
                        $dispatchedCount++;
                    }
                }
            });

        $this->info("Đã đưa {$dispatchedCount} biển số đến hạn vào hàng đợi (chu kỳ {$intervalHours} giờ).");

        return self::SUCCESS;
    }

    private function dispatchIfDue(int $monitoringId, DateTimeInterface $dueBefore): bool
    {
        return DB::transaction(function () use ($monitoringId, $dueBefore): bool {
            $monitoring = VehicleMonitoring::query()
                ->whereKey($monitoringId)
                ->where('enabled', true)
                ->where(fn ($query) => $query->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<=', $dueBefore))
                ->whereHas('user.monitoringSubscriptions', fn ($query) => $query->active())
                ->lockForUpdate()
                ->first();

            if (! $monitoring instanceof VehicleMonitoring) {
                return false;
            }

            CheckVehicleMonitoringJob::dispatch($monitoring->id);
            $monitoring->update(['last_dispatched_at' => now()]);

            return true;
        });
    }
}
