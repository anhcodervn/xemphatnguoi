<?php

use App\Models\ApiKey;
use App\Models\CaptchaService;
use App\Models\CaptchaSource;
use App\Models\CaptchaTask;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('captcha saas schema contains the core application and captcha api tables', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('user_sessions'))->toBeTrue()
        ->and(Schema::hasTable('wallets'))->toBeTrue()
        ->and(Schema::hasTable('wallet_transactions'))->toBeTrue()
        ->and(Schema::hasTable('payment_transactions'))->toBeTrue()
        ->and(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Schema::hasTable('user_logs'))->toBeTrue()
        ->and(Schema::hasTable('settings'))->toBeTrue()
        ->and(Schema::hasTable('api_keys'))->toBeTrue()
        ->and(Schema::hasTable('api_logs'))->toBeTrue()
        ->and(Schema::hasTable('captcha_sources'))->toBeTrue()
        ->and(Schema::hasTable('captcha_services'))->toBeTrue()
        ->and(Schema::hasTable('captcha_tasks'))->toBeTrue();

    expect(Schema::hasColumns('api_keys', [
        'user_id',
        'name',
        'api_key',
        'api_secret_hash',
        'permissions',
        'ip_whitelist',
        'expired_at',
        'status',
    ]))->toBeTrue();

    expect(Schema::hasColumns('captcha_sources', [
        'name',
        'driver',
        'api_base_url',
        'credentials',
        'settings',
        'is_active',
        'priority',
    ]))->toBeTrue();

    expect(Schema::hasColumns('captcha_services', [
        'default_source_id',
        'name',
        'code',
        'category',
        'provider_service_code',
        'base_price',
        'selling_price',
        'estimated_seconds',
        'is_active',
        'settings',
    ]))->toBeTrue();

    expect(Schema::hasColumns('captcha_tasks', [
        'user_id',
        'api_key_id',
        'captcha_service_id',
        'captcha_source_id',
        'task_code',
        'external_task_id',
        'service_code',
        'status',
        'request_payload',
        'result_payload',
        'provider_cost',
        'selling_price',
        'error_message',
        'requested_at',
        'solved_at',
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

test('captcha models cast structured payload fields correctly', function () {
    $apiKey = new ApiKey;
    $apiKey->permissions = ['captcha-tasks.create'];
    $apiKey->ip_whitelist = ['127.0.0.1'];

    $source = new CaptchaSource;
    $source->credentials = ['api_key' => 'demo'];
    $source->settings = ['timeout' => 120];

    $service = new CaptchaService;
    $service->settings = ['fields' => ['site_key' => ['type' => 'string']]];

    $task = new CaptchaTask;
    $task->request_payload = ['site_key' => 'abc'];
    $task->result_payload = ['token' => 'xyz'];

    expect($apiKey->permissions)->toBeArray()
        ->and($apiKey->ip_whitelist)->toBeArray()
        ->and($source->credentials)->toBeArray()
        ->and($source->settings)->toBeArray()
        ->and($service->settings)->toBeArray()
        ->and($task->request_payload)->toBeArray()
        ->and($task->result_payload)->toBeArray();
});
