<?php

use App\Models\CronAlertChannel;
use App\Models\CronJob;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('autocron schema contains the core SaaS tables and columns', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('user_sessions'))->toBeTrue()
        ->and(Schema::hasTable('wallets'))->toBeTrue()
        ->and(Schema::hasTable('wallet_transactions'))->toBeTrue()
        ->and(Schema::hasTable('packages'))->toBeTrue()
        ->and(Schema::hasTable('user_packages'))->toBeTrue()
        ->and(Schema::hasTable('package_orders'))->toBeTrue()
        ->and(Schema::hasTable('user_subscriptions'))->toBeTrue()
        ->and(Schema::hasTable('payment_transactions'))->toBeTrue()
        ->and(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Schema::hasTable('user_logs'))->toBeTrue()
        ->and(Schema::hasTable('settings'))->toBeTrue()
        ->and(Schema::hasTable('cron_jobs'))->toBeTrue()
        ->and(Schema::hasTable('cron_job_logs'))->toBeTrue()
        ->and(Schema::hasTable('cron_alert_channels'))->toBeTrue()
        ->and(Schema::hasTable('cron_job_alert_channel'))->toBeTrue()
        ->and(Schema::hasTable('cron_usage_counters'))->toBeTrue();

    expect(Schema::hasColumns('cron_jobs', [
        'user_id',
        'group_name',
        'name',
        'url',
        'method',
        'body_type',
        'cron_expression',
        'interval_seconds',
        'timeout_seconds',
        'retry_count',
        'status',
        'last_run_at',
        'next_run_at',
        'consecutive_failures',
        'total_runs',
        'total_success',
        'total_failed',
    ]))->toBeTrue();

    expect(Schema::hasColumns('cron_job_logs', [
        'cron_job_id',
        'user_id',
        'run_uuid',
        'attempt',
        'status',
        'method',
        'url',
        'status_code',
        'duration_ms',
        'request_headers',
        'response_headers',
        'response_body_preview',
        'error_message',
        'started_at',
        'finished_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('cron_alert_channels', [
        'user_id',
        'cron_job_id',
        'name',
        'type',
        'target_url',
        'telegram_bot_token',
        'telegram_chat_id',
        'email',
        'events',
        'is_enabled',
    ]))->toBeTrue();

    expect(Schema::hasColumns('packages', [
        'duration_days',
        'package_limits',
    ]))->toBeTrue();

    expect(Schema::hasColumns('user_subscriptions', [
        'user_id',
        'package_id',
        'auto_renew_enabled',
        'starts_at',
        'expires_at',
        'auto_renew_attempted_at',
        'auto_renew_status',
        'auto_renew_message',
        'status',
        'package_limits',
    ]))->toBeTrue();

    expect(Schema::hasColumns('package_orders', [
        'auto_renew_enabled',
    ]))->toBeTrue();
});

test('user model still auto-generates username and creates a default main wallet', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($user->username)->toBe('test')
        ->and($user->full_name)->toBe('Test User')
        ->and($user->name)->toBe('Test User')
        ->and($user->wallet)->not->toBeNull()
        ->and($user->wallet?->type)->toBe('main')
        ->and((string) $user->wallet?->balance)->toBe('0.00');
});

test('cron job model casts array and schedule fields correctly', function () {
    $cronJob = new CronJob;
    $cronJob->headers = ['Authorization' => 'Bearer demo'];
    $cronJob->query_params = ['page' => 1];
    $cronJob->expected_status_codes = [200, 204];
    $cronJob->follow_redirects = false;

    expect($cronJob->headers)->toBeArray()
        ->and($cronJob->query_params)->toBeArray()
        ->and($cronJob->expected_status_codes)->toBeArray()
        ->and($cronJob->follow_redirects)->toBeFalse();
});

test('cron alert channel supports structured events and encrypted fields', function () {
    $channel = new CronAlertChannel;
    $channel->events = ['on_fail', 'on_recovered'];
    $channel->telegram_bot_token = 'bot-token';

    expect($channel->events)->toBeArray()
        ->and($channel->events)->toContain('on_fail')
        ->and($channel->telegram_bot_token)->toBe('bot-token');
});
