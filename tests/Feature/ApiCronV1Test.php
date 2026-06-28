<?php

use App\Models\ApiKey;
use App\Models\CronJobLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

function apiKeyHeaders(ApiKey $apiKey, string $secret): array
{
    return [
        'X-API-KEY' => $apiKey->api_key,
        'X-API-SECRET' => $secret,
        'Accept' => 'application/json',
    ];
}

function issueApiKey(User $user, array $permissions = ['cron-jobs.read', 'cron-jobs.write', 'cron-logs.read']): array
{
    $credentials = ApiKey::generateCredentials();

    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'name' => 'Main integration',
        'api_key' => $credentials['api_key'],
        'api_secret_hash' => Hash::make($credentials['api_secret']),
        'permissions' => $permissions,
        'ip_whitelist' => ['*'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    return [$apiKey, $credentials['api_secret']];
}

test('client can create api key from sanctum endpoint with active subscription', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 300,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/api-keys', [
        'name' => 'Website production',
        'permissions' => ['cron-jobs.read', 'cron-jobs.write', 'cron-logs.read'],
        'ip_whitelist' => ['*'],
    ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.api_key.name', 'Website production')
        ->assertJsonPath('data.api_key.permissions.0', 'cron-jobs.read');
});

test('client cannot create more than one api key', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 300,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/api-keys', [
        'name' => 'First key',
        'permissions' => ['cron-jobs.read', 'cron-jobs.write', 'cron-logs.read'],
        'ip_whitelist' => ['*'],
    ])->assertCreated();

    $this->postJson('/api/client/api-keys', [
        'name' => 'Second key',
        'permissions' => ['cron-jobs.read', 'cron-jobs.write', 'cron-logs.read'],
        'ip_whitelist' => ['*'],
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);
});

test('api v1 can list create update pause and delete own cron jobs', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 300,
    ]);

    [$apiKey, $secret] = issueApiKey($user);
    $headers = apiKeyHeaders($apiKey, $secret);

    $this->getJson('/api/v1/cron-jobs', $headers)
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(0, 'data.data');

    $createdResponse = $this->postJson('/api/v1/cron-jobs', [
        'name' => 'API created cron',
        'group_name' => 'api',
        'url' => 'https://example.com/health',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
        'timezone' => 'Asia/Ho_Chi_Minh',
        'timeout_seconds' => 10,
        'connect_timeout_seconds' => 5,
        'retry_count' => 0,
        'retry_delay_seconds' => 30,
        'max_response_size_kb' => 20,
        'follow_redirects' => false,
        'verify_ssl' => true,
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.cron_job.name', 'API created cron');

    $cronJobId = $createdResponse->json('data.cron_job.id');

    $this->patchJson("/api/v1/cron-jobs/{$cronJobId}", [
        'name' => 'API updated cron',
        'group_name' => 'api',
        'url' => 'https://example.com/ping',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 600,
        'timezone' => 'Asia/Ho_Chi_Minh',
        'timeout_seconds' => 10,
        'connect_timeout_seconds' => 5,
        'retry_count' => 0,
        'retry_delay_seconds' => 30,
        'max_response_size_kb' => 20,
        'follow_redirects' => false,
        'verify_ssl' => true,
    ], $headers)
        ->assertOk()
        ->assertJsonPath('data.cron_job.name', 'API updated cron');

    $this->postJson("/api/v1/cron-jobs/{$cronJobId}/pause", [], $headers)
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->deleteJson("/api/v1/cron-jobs/{$cronJobId}", [], $headers)
        ->assertOk()
        ->assertJsonPath('status', true);
});

test('api v1 returns logs for the requested cron job', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 300,
    ]);

    [$apiKey, $secret] = issueApiKey($user, ['cron-jobs.read', 'cron-logs.read']);
    $headers = apiKeyHeaders($apiKey, $secret);

    $cronJob = autocronJob($user, [
        'name' => 'Observed job',
    ]);

    CronJobLog::query()->create([
        'cron_job_id' => $cronJob->id,
        'user_id' => $user->id,
        'run_uuid' => (string) str()->uuid(),
        'attempt' => 1,
        'status' => 'success',
        'method' => 'GET',
        'url' => 'https://example.com/health',
        'status_code' => 200,
        'duration_ms' => 125,
        'response_size_bytes' => 32,
        'started_at' => now()->subSecond(),
        'finished_at' => now(),
    ]);

    $this->getJson("/api/v1/cron-jobs/{$cronJob->id}/logs", $headers)
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.cron_job_id', $cronJob->id);
});

test('api v1 blocks endpoints when api key lacks permission', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 300,
    ]);

    [$apiKey, $secret] = issueApiKey($user, ['cron-jobs.read']);
    $headers = apiKeyHeaders($apiKey, $secret);

    $this->postJson('/api/v1/cron-jobs', [
        'name' => 'Blocked create',
        'url' => 'https://example.com/health',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
    ], $headers)
        ->assertForbidden();
});
