<?php

use App\Models\TrafficFineLookupLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('traffic fine detailed report is restricted to admins', function (): void {
    $this->getJson('/api/admin-api/traffic-fines/report')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/admin-api/traffic-fines/report')->assertForbidden();
});

test('traffic fine detailed report validates the requested period', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/traffic-fines/report?days=13')
        ->assertUnprocessable();
});

test('traffic fine detailed report returns operational breakdowns for the selected period', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(12));
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    TrafficFineLookupLog::factory()->create([
        'user_id' => null,
        'plate' => '30A10001',
        'vehicle_type' => 'car',
        'source' => 'provider',
        'cache_hit' => false,
        'provider' => 'xephatnguoi',
        'provider_latency_ms' => 100,
        'status' => 'success',
        'created_at' => now(),
    ]);
    TrafficFineLookupLog::factory()->create([
        'user_id' => null,
        'plate' => '30A10002',
        'vehicle_type' => 'car',
        'source' => 'redis',
        'cache_hit' => true,
        'provider' => 'xephatnguoi',
        'provider_latency_ms' => null,
        'status' => 'no_violation',
        'created_at' => now()->subHour(),
    ]);
    TrafficFineLookupLog::factory()->create([
        'user_id' => null,
        'plate' => '29B10003',
        'vehicle_type' => 'motorbike',
        'source' => 'provider',
        'cache_hit' => false,
        'provider' => 'xephatnguoi',
        'provider_latency_ms' => 900,
        'status' => 'provider_error',
        'created_at' => now()->subDay(),
    ]);
    TrafficFineLookupLog::factory()->create([
        'user_id' => null,
        'plate' => '29B10003',
        'vehicle_type' => 'motorbike',
        'source' => 'negative_cache',
        'cache_hit' => true,
        'provider' => 'xephatnguoi',
        'provider_latency_ms' => null,
        'status' => 'provider_error',
        'created_at' => now(),
    ]);
    TrafficFineLookupLog::factory()->create([
        'user_id' => null,
        'plate' => '30A99999',
        'vehicle_type' => 'car',
        'source' => 'provider',
        'cache_hit' => false,
        'provider' => 'xephatnguoi',
        'provider_latency_ms' => 200,
        'status' => 'success',
        'created_at' => now()->subDays(8),
    ]);

    $response = $this->getJson('/api/admin-api/traffic-fines/report?days=7')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.period.days', 7)
        ->assertJsonPath('data.summary.total_lookups', 4)
        ->assertJsonPath('data.summary.unique_plates', 3)
        ->assertJsonPath('data.summary.completed_lookups', 2)
        ->assertJsonPath('data.summary.provider_errors', 2)
        ->assertJsonPath('data.summary.cache_hits', 1)
        ->assertJsonPath('data.summary.negative_cache_hits', 1)
        ->assertJsonPath('data.summary.cache_misses', 2)
        ->assertJsonPath('data.summary.cache_hit_rate', 25)
        ->assertJsonPath('data.summary.completion_rate', 50)
        ->assertJsonPath('data.summary.provider_requests', 2)
        ->assertJsonPath('data.summary.average_provider_latency_ms', 500)
        ->assertJsonCount(7, 'data.daily')
        ->assertJsonCount(2, 'data.recent_errors')
        ->assertJsonPath('data.recent_errors.0.source', 'negative_cache')
        ->assertJsonPath('data.recent_errors.0.plate', '29B10003');

    $daily = collect($response->json('data.daily'));
    $vehicleTypes = collect($response->json('data.vehicle_types'));
    $sources = collect($response->json('data.sources'));

    expect($daily->firstWhere('date', now()->toDateString()))
        ->toMatchArray([
            'total' => 3,
            'completed' => 2,
            'provider_errors' => 1,
            'cache_hits' => 1,
            'negative_cache_hits' => 1,
        ])
        ->and($daily->firstWhere('date', now()->subDay()->toDateString()))
        ->toMatchArray([
            'total' => 1,
            'completed' => 0,
            'provider_errors' => 1,
            'cache_hits' => 0,
            'negative_cache_hits' => 0,
        ])
        ->and($vehicleTypes->firstWhere('key', 'car'))
        ->toMatchArray(['total' => 2, 'percentage' => 50])
        ->and($sources->firstWhere('key', 'provider'))
        ->toMatchArray(['total' => 2, 'percentage' => 50]);
});
