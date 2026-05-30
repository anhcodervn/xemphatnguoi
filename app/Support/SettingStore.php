<?php

namespace App\Support;

use App\Models\Setting;

class SettingStore
{
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    public function putString(string $key, string $value): Setting
    {
        return Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => 'string',
            ],
        );
    }

    /**
     * @param array<string, mixed> $value
     */
    public function putArray(string $key, array $value): Setting
    {
        return Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'type' => 'json',
            ],
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        if ($setting->type === 'json') {
            $decoded = json_decode((string) $setting->value, true);

            return is_array($decoded) ? $decoded : $default;
        }

        return $setting->value ?? $default;
    }
}
