<?php

namespace App\Features\Cron\Support;

use App\Models\Package;
use App\Models\UserSubscription;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CronPackageCatalog
{
    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'max_cron_jobs' => 3,
            'min_interval_seconds' => 900,
            'max_logs_per_job' => 100,
            'max_request_timeout_seconds' => 10,
            'max_response_size_kb' => 5,
            'max_retries_per_run' => 0,
            'max_headers_count' => 5,
            'max_body_size_kb' => 5,
            'allowed_methods' => ['GET'],
            'allow_custom_headers' => false,
            'allow_custom_body' => false,
            'allow_cron_expression' => false,
            'allow_run_now' => false,
            'allow_alerts' => false,
            'max_alert_channels' => 0,
            'monthly_run_quota' => 1_000,
            'daily_run_quota' => 100,
            'concurrent_runs_limit' => 1,
            'priority' => 'low',
            'queue_name' => 'cron-low',
            'allow_expected_body_check' => false,
            'allow_webhook_alert' => false,
            'allow_discord_alert' => false,
            'allow_telegram_alert' => false,
            'allow_email_alert' => true,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return [
            'free' => self::defaults(),
            'basic' => [
                ...self::defaults(),
                'max_cron_jobs' => 20,
                'min_interval_seconds' => 300,
                'max_logs_per_job' => 500,
                'max_request_timeout_seconds' => 15,
                'max_response_size_kb' => 20,
                'max_retries_per_run' => 1,
                'max_headers_count' => 20,
                'max_body_size_kb' => 32,
                'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                'allow_custom_headers' => true,
                'allow_custom_body' => true,
                'allow_alerts' => true,
                'max_alert_channels' => 2,
                'monthly_run_quota' => 100_000,
                'daily_run_quota' => null,
                'priority' => 'normal',
                'queue_name' => 'cron-default',
                'allow_discord_alert' => true,
            ],
            'pro' => [
                ...self::defaults(),
                'max_cron_jobs' => 100,
                'min_interval_seconds' => 60,
                'max_logs_per_job' => 2_000,
                'max_request_timeout_seconds' => 30,
                'max_response_size_kb' => 50,
                'max_retries_per_run' => 3,
                'max_headers_count' => 50,
                'max_body_size_kb' => 128,
                'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                'allow_custom_headers' => true,
                'allow_custom_body' => true,
                'allow_cron_expression' => true,
                'allow_run_now' => true,
                'allow_alerts' => true,
                'max_alert_channels' => 10,
                'monthly_run_quota' => 500_000,
                'daily_run_quota' => null,
                'concurrent_runs_limit' => 3,
                'priority' => 'normal',
                'queue_name' => 'cron-default',
                'allow_expected_body_check' => true,
                'allow_webhook_alert' => true,
                'allow_discord_alert' => true,
                'allow_telegram_alert' => true,
            ],
            'business' => [
                ...self::defaults(),
                'max_cron_jobs' => 500,
                'min_interval_seconds' => 60,
                'max_logs_per_job' => 5_000,
                'max_request_timeout_seconds' => 30,
                'max_response_size_kb' => 50,
                'max_retries_per_run' => 5,
                'max_headers_count' => 100,
                'max_body_size_kb' => 256,
                'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                'allow_custom_headers' => true,
                'allow_custom_body' => true,
                'allow_cron_expression' => true,
                'allow_run_now' => true,
                'allow_alerts' => true,
                'max_alert_channels' => 25,
                'monthly_run_quota' => 2_000_000,
                'daily_run_quota' => null,
                'concurrent_runs_limit' => 10,
                'priority' => 'high',
                'queue_name' => 'cron-high',
                'allow_expected_body_check' => true,
                'allow_webhook_alert' => true,
                'allow_discord_alert' => true,
                'allow_telegram_alert' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $overrides
     * @return array<string, mixed>
     */
    public static function resolve(?array $overrides = null, ?Package $package = null, ?UserSubscription $subscription = null): array
    {
        $preset = self::guessPresetName($overrides, $package, $subscription);
        $base = self::presets()[$preset] ?? self::defaults();

        return array_replace($base, Arr::wrap($overrides));
    }

    /**
     * @param  array<string, mixed>|null  $overrides
     */
    private static function guessPresetName(?array $overrides, ?Package $package, ?UserSubscription $subscription): string
    {
        $candidate = collect([
            Arr::get($overrides, 'preset'),
            $package?->slug,
            $package?->name,
            $subscription?->package_name,
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => Str::slug($value))
            ->first();

        return match (true) {
            $candidate === null => 'free',
            Str::contains($candidate, 'business') => 'business',
            Str::contains($candidate, 'pro') => 'pro',
            Str::contains($candidate, 'basic') => 'basic',
            default => 'free',
        };
    }
}
