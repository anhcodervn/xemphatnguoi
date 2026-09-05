<?php

use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

test('admin api log report identifies customers affected by server errors', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(12));
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $firstCustomer = User::factory()->create(['username' => 'first-customer']);
    $secondCustomer = User::factory()->create(['username' => 'second-customer']);
    $firstKey = createApiKeyForLogTest($firstCustomer, 'first-key');
    $secondKey = createApiKeyForLogTest($secondCustomer, 'second-key');

    createApiLogForObservabilityTest($firstCustomer, $firstKey, 503, 120, now()->subHours(2));
    createApiLogForObservabilityTest($firstCustomer, $firstKey, 503, 180, now()->subHour());
    createApiLogForObservabilityTest($secondCustomer, $secondKey, 500, 300, now());
    createApiLogForObservabilityTest($secondCustomer, $secondKey, 200, 40, now());
    createApiLogForObservabilityTest($secondCustomer, $secondKey, 503, 900, now()->subDays(2));

    $date = now()->toDateString();
    $response = $this->getJson("/api/admin-api/api-logs?status_group=server_error&from={$date}&to={$date}")
        ->assertOk()
        ->assertJsonPath('data.summary.total', 3)
        ->assertJsonPath('data.summary.server_error', 3)
        ->assertJsonPath('data.summary.service_unavailable', 2)
        ->assertJsonPath('data.summary.affected_users', 2)
        ->assertJsonPath('data.summary.affected_api_keys', 2)
        ->assertJsonPath('data.summary.average_response_time_ms', 200)
        ->assertJsonCount(3, 'data.api_logs.data');

    expect($response->json('data.summary.first_failure_at'))->not->toBeNull()
        ->and($response->json('data.summary.last_failure_at'))->not->toBeNull()
        ->and(collect($response->json('data.api_logs.data'))->pluck('status_code')->all())->toBe([500, 503, 503]);
});

test('admin api log filters reject invalid dates and status groups', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/api-logs?from=not-a-date')->assertUnprocessable();
    $this->getJson('/api/admin-api/api-logs?status_group=broken')->assertUnprocessable();
    $this->getJson('/api/admin-api/api-logs?api_version=v3')->assertUnprocessable();
});

test('admin api log identifies and filters api v1 and v2 clearly', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $customer = User::factory()->create();
    $apiKey = createApiKeyForLogTest($customer, 'version-key');

    createApiLogForObservabilityTest($customer, $apiKey, 200, 40, now(), 'api/v1/lookup');
    createApiLogForObservabilityTest($customer, $apiKey, 200, 50, now(), 'api/v2/lookup');

    $this->getJson('/api/admin-api/api-logs?api_version=v2')
        ->assertOk()
        ->assertJsonPath('data.summary.total', 1)
        ->assertJsonCount(1, 'data.api_logs.data')
        ->assertJsonPath('data.api_logs.data.0.endpoint', 'api/v2/lookup')
        ->assertJsonPath('data.api_logs.data.0.api_version', 'v2');

    $this->getJson('/api/admin-api/api-logs?api_version=v1')
        ->assertOk()
        ->assertJsonPath('data.summary.total', 1)
        ->assertJsonPath('data.api_logs.data.0.api_version', 'v1');
});

function createApiKeyForLogTest(User $user, string $name): ApiKey
{
    return ApiKey::query()->create([
        'user_id' => $user->id,
        'key_type' => ApiKey::TYPE_WALLET,
        'name' => $name,
        'api_key' => 'ak_'.$name,
        'api_secret_hash' => Hash::make('secret'),
        'permissions' => ['traffic-fines.lookup'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);
}

function createApiLogForObservabilityTest(
    User $user,
    ApiKey $apiKey,
    int $statusCode,
    int $responseTime,
    mixed $createdAt,
    string $endpoint = 'api/v1/lookup',
): ApiLog {
    return ApiLog::query()->create([
        'user_id' => $user->id,
        'api_key_id' => $apiKey->id,
        'endpoint' => $endpoint,
        'method' => 'GET',
        'ip' => '127.0.0.1',
        'request_data' => ['query' => ['plate' => '30A12345']],
        'response_data' => ['status' => $statusCode === 503 ? 'provider_error' : 'ok'],
        'status_code' => $statusCode,
        'response_time_ms' => $responseTime,
        'billing_status' => 'not_billable',
        'created_at' => $createdAt,
    ]);
}
