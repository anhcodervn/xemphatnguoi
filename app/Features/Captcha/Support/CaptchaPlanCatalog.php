<?php

namespace App\Features\Captcha\Support;

class CaptchaPlanCatalog
{
    public static function defaults(): array
    {
        return [
            'max_api_keys' => 1,
            'requests_per_minute' => 60,
            'monthly_captcha_quota' => 500,
            'max_concurrent_tasks' => 5,
            'max_whitelisted_ips' => 10,
            'supports_callback' => false,
            'supports_priority_queue' => false,
            'supports_manual_review' => false,
            'service_whitelist' => [],
        ];
    }

    public static function resolve(?array $overrides = null): array
    {
        $resolved = array_replace(self::defaults(), $overrides ?? []);
        $quota = $resolved['monthly_captcha_quota'] ?? null;

        return [
            'max_api_keys' => max(1, (int) ($resolved['max_api_keys'] ?? 1)),
            'requests_per_minute' => max(1, (int) ($resolved['requests_per_minute'] ?? 60)),
            'monthly_captcha_quota' => $quota === null ? null : max(0, (int) $quota),
            'max_concurrent_tasks' => max(1, (int) ($resolved['max_concurrent_tasks'] ?? 1)),
            'max_whitelisted_ips' => max(1, (int) ($resolved['max_whitelisted_ips'] ?? 1)),
            'supports_callback' => (bool) ($resolved['supports_callback'] ?? false),
            'supports_priority_queue' => (bool) ($resolved['supports_priority_queue'] ?? false),
            'supports_manual_review' => (bool) ($resolved['supports_manual_review'] ?? false),
            'service_whitelist' => collect($resolved['service_whitelist'] ?? [])
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->values()
                ->all(),
        ];
    }

    public static function presets(): array
    {
        return [
            'starter' => self::resolve([
                'max_api_keys' => 1,
                'requests_per_minute' => 30,
                'monthly_captcha_quota' => 500,
                'max_concurrent_tasks' => 3,
            ]),
            'growth' => self::resolve([
                'max_api_keys' => 3,
                'requests_per_minute' => 120,
                'monthly_captcha_quota' => 5000,
                'max_concurrent_tasks' => 20,
                'supports_callback' => true,
            ]),
            'agency' => self::resolve([
                'max_api_keys' => 10,
                'requests_per_minute' => 300,
                'monthly_captcha_quota' => null,
                'max_concurrent_tasks' => 60,
                'supports_callback' => true,
                'supports_priority_queue' => true,
                'supports_manual_review' => true,
            ]),
        ];
    }
}
