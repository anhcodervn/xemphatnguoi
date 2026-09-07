<?php

namespace App\Features\TrafficFine\Services;

use App\Support\SettingStore;

class VehicleMonitoringSettingsService
{
    public const INTERVAL_HOURS_KEY = 'vehicle_monitoring_interval_hours';

    public function __construct(private readonly SettingStore $settingStore) {}

    public function intervalHours(): int
    {
        $configuredDefault = filter_var(
            config('traffic-fines.monitoring.interval_hours', 24),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 720]],
        );
        $default = $configuredDefault === false ? 24 : $configuredDefault;
        $stored = filter_var(
            $this->settingStore->get(self::INTERVAL_HOURS_KEY, $default),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 720]],
        );

        return $stored === false ? $default : $stored;
    }

    public function updateIntervalHours(int $intervalHours): void
    {
        $this->settingStore->putMany([
            self::INTERVAL_HOURS_KEY => $intervalHours,
        ]);
    }
}
