<?php

namespace App\Features\TrafficFine\Services;

use App\Support\SettingStore;

class ApiDocumentationSettingsService
{
    public const V1_DESCRIPTION_SETTING_KEY = 'traffic_fine_api_v1_public_description';

    public const V2_DESCRIPTION_SETTING_KEY = 'traffic_fine_api_v2_public_description';

    public function __construct(private readonly SettingStore $settingStore) {}

    public function v1Description(): string
    {
        return $this->description(
            self::V1_DESCRIPTION_SETTING_KEY,
            (string) config('traffic-fines.public_api.v1_description'),
        );
    }

    public function v2Description(): string
    {
        return $this->description(
            self::V2_DESCRIPTION_SETTING_KEY,
            (string) config('traffic-fines.public_api.v2_description'),
        );
    }

    /** @param array<string, mixed> $validated */
    public function update(array $validated): void
    {
        if (array_key_exists('api_v1_description', $validated)) {
            $this->settingStore->putString(self::V1_DESCRIPTION_SETTING_KEY, trim((string) $validated['api_v1_description']));
        }

        if (array_key_exists('api_v2_description', $validated)) {
            $this->settingStore->putString(self::V2_DESCRIPTION_SETTING_KEY, trim((string) $validated['api_v2_description']));
        }
    }

    private function description(string $settingKey, string $defaultDescription): string
    {
        $description = trim($this->settingStore->getString($settingKey, $defaultDescription));

        return $description === '' ? $defaultDescription : mb_substr($description, 0, 300);
    }
}
