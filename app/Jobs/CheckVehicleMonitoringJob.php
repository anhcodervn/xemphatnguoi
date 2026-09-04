<?php

namespace App\Jobs;

use App\Features\TrafficFine\Actions\CheckVehicleMonitoringAction;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckVehicleMonitoringJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $monitoringId,
    ) {}

    public function handle(CheckVehicleMonitoringAction $checkMonitoring): void
    {
        $checkMonitoring->handle($this->monitoringId);
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return (string) $this->monitoringId;
    }

    public function failed(?Throwable $exception): void
    {
        Log::warning('Kiểm tra theo dõi biển số thất bại.', [
            'monitoring_id' => $this->monitoringId,
            'exception' => $exception !== null ? $exception::class : null,
        ]);
    }
}
