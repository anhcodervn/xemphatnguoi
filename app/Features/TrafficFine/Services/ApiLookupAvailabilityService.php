<?php

namespace App\Features\TrafficFine\Services;

use App\Support\SettingStore;
use InvalidArgumentException;

class ApiLookupAvailabilityService
{
    public const V1_ENABLED_SETTING_KEY = 'traffic_fine_api_v1_enabled';

    public const V2_ENABLED_SETTING_KEY = 'traffic_fine_api_v2_enabled';

    public function __construct(private readonly SettingStore $settingStore) {}

    public function isEnabled(string $apiVersion): bool
    {
        $settingKey = match ($apiVersion) {
            'v1' => self::V1_ENABLED_SETTING_KEY,
            'v2' => self::V2_ENABLED_SETTING_KEY,
            default => throw new InvalidArgumentException("Unsupported traffic fine API version [{$apiVersion}]."),
        };

        return (bool) $this->settingStore->get(
            $settingKey,
            (bool) config("traffic-fines.public_api.{$apiVersion}_enabled", true),
        );
    }

    public function update(bool $v1Enabled, bool $v2Enabled): void
    {
        $this->settingStore->putMany([
            self::V1_ENABLED_SETTING_KEY => $v1Enabled,
            self::V2_ENABLED_SETTING_KEY => $v2Enabled,
        ]);
    }
}
