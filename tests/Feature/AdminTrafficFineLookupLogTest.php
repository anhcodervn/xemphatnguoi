<?php

use App\Models\TrafficFineLookupLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('admin lookup logs summarize authenticated and anonymous users affected by provider errors', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(12));
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $customer = User::factory()->create(['username' => 'affected-customer']);

    TrafficFineLookupLog::factory()->create(['user_id' => $customer->id, 'api_version' => 'v2', 'status' => 'provider_error', 'created_at' => now()->subHours(2)]);
    TrafficFineLookupLog::factory()->create(['user_id' => null, 'ip' => '203.0.113.10', 'status' => 'provider_error', 'created_at' => now()->subHour()]);
    TrafficFineLookupLog::factory()->create(['user_id' => null, 'ip' => '203.0.113.10', 'status' => 'provider_error', 'created_at' => now()]);
    TrafficFineLookupLog::factory()->create(['user_id' => $customer->id, 'status' => 'success', 'created_at' => now()]);
    TrafficFineLookupLog::factory()->create(['user_id' => null, 'ip' => '203.0.113.11', 'status' => 'provider_error', 'created_at' => now()->subDays(2)]);

    $date = now()->toDateString();
    $response = $this->getJson("/api/admin-api/traffic-fines/logs?from={$date}&to={$date}")
        ->assertOk()
        ->assertJsonPath('data.summary.total', 4)
        ->assertJsonPath('data.summary.completed', 1)
        ->assertJsonPath('data.summary.provider_errors', 3)
        ->assertJsonPath('data.summary.affected_users', 1)
        ->assertJsonPath('data.summary.anonymous_requests', 2)
        ->assertJsonPath('data.summary.affected_anonymous_ips', 1)
        ->assertJsonPath('data.logs.data.3.api_version', 'v2')
        ->assertJsonCount(4, 'data.logs.data');

    expect(collect($response->json('data.logs.data'))->pluck('user.username')->filter()->all())
        ->toContain('affected-customer');
});

test('admin lookup logs can filter provider errors and validate the date range', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    TrafficFineLookupLog::factory()->create(['status' => 'provider_error']);
    TrafficFineLookupLog::factory()->create(['status' => 'success']);

    $this->getJson('/api/admin-api/traffic-fines/logs?status=provider_error')
        ->assertOk()
        ->assertJsonPath('data.summary.total', 1)
        ->assertJsonPath('data.summary.provider_errors', 1)
        ->assertJsonCount(1, 'data.logs.data');

    $this->getJson('/api/admin-api/traffic-fines/logs?from=2026-09-05&to=2026-09-04')
        ->assertUnprocessable();

    $this->getJson('/api/admin-api/traffic-fines/logs?api_version=v3')
        ->assertUnprocessable();
});

test('admin lookup logs can filter api versions', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    TrafficFineLookupLog::factory()->create(['api_version' => 'v1']);
    TrafficFineLookupLog::factory()->create(['api_version' => 'v2']);

    $this->getJson('/api/admin-api/traffic-fines/logs?api_version=v2')
        ->assertOk()
        ->assertJsonPath('data.summary.total', 1)
        ->assertJsonPath('data.logs.data.0.api_version', 'v2')
        ->assertJsonCount(1, 'data.logs.data');
});
