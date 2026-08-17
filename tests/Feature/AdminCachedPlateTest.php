<?php

use App\Models\TrafficFineLookupLog;
use App\Models\TrafficFineResult;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('cached plate management is restricted to admins', function (): void {
    $this->getJson('/api/admin-api/traffic-fines/results')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/admin-api/traffic-fines/results')->assertForbidden();
});

test('cached plate management validates filters and sorting', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/traffic-fines/results?days=13')
        ->assertUnprocessable();
    $this->getJson('/api/admin-api/traffic-fines/results?state=missing')
        ->assertUnprocessable();
    $this->getJson('/api/admin-api/traffic-fines/results?sort=response_json')
        ->assertUnprocessable();
    $this->getJson('/api/admin-api/traffic-fines/results?search=%25')
        ->assertUnprocessable();
});

test('cached plate management returns ttl and lookup intensity without sensitive payloads', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(12));
    config()->set('traffic-fines.cache.ttl', 86400);
    config()->set('cache.default', 'array');
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    TrafficFineResult::factory()->create([
        'plate' => '30A10001',
        'vehicle_type' => 'car',
        'status' => 'success',
        'violation_count' => 2,
        'response_json' => ['secret_provider_payload' => true],
        'provider' => 'xephatnguoi',
        'checked_at' => now()->subHour(),
        'expires_at' => now()->addHours(23),
    ]);
    TrafficFineResult::factory()->create([
        'plate' => '29B10002',
        'vehicle_type' => 'motorbike',
        'status' => 'no_violation',
        'violation_count' => 0,
        'checked_at' => now()->subHours(23),
        'expires_at' => now()->addMinutes(30),
    ]);
    TrafficFineResult::factory()->create([
        'plate' => '51A10003',
        'vehicle_type' => 'car',
        'status' => 'no_violation',
        'violation_count' => 0,
        'checked_at' => now()->subDays(2),
        'expires_at' => now()->subHour(),
    ]);

    foreach ([
        ['source' => 'provider', 'cache_hit' => false, 'status' => 'success', 'created_at' => now()->subHours(3)],
        ['source' => 'redis', 'cache_hit' => true, 'status' => 'success', 'created_at' => now()->subHours(2)],
        ['source' => 'database', 'cache_hit' => true, 'status' => 'success', 'created_at' => now()->subHour()],
        ['source' => 'negative_cache', 'cache_hit' => true, 'status' => 'provider_error', 'created_at' => now()],
    ] as $attributes) {
        TrafficFineLookupLog::factory()->create([
            'user_id' => null,
            'plate' => '30A10001',
            'vehicle_type' => 'car',
            'provider' => 'xephatnguoi',
            ...$attributes,
        ]);
    }
    TrafficFineLookupLog::factory()->create([
        'user_id' => null,
        'plate' => '30A10001',
        'vehicle_type' => 'car',
        'source' => 'provider',
        'cache_hit' => false,
        'status' => 'success',
        'created_at' => now()->subDays(31),
    ]);

    $response = $this->getJson('/api/admin-api/traffic-fines/results?days=30')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.period.days', 30)
        ->assertJsonPath('data.cache.store', 'array')
        ->assertJsonPath('data.cache.configured_ttl_seconds', 86400)
        ->assertJsonPath('data.summary.total_entries', 3)
        ->assertJsonPath('data.summary.active_entries', 2)
        ->assertJsonPath('data.summary.expiring_entries', 1)
        ->assertJsonPath('data.summary.expired_entries', 1)
        ->assertJsonPath('data.summary.violation_entries', 1)
        ->assertJsonPath('data.summary.period_lookups', 4)
        ->assertJsonPath('data.summary.period_positive_cache_hits', 2)
        ->assertJsonPath('data.summary.period_provider_requests', 1)
        ->assertJsonPath('data.summary.positive_cache_hit_rate', 50)
        ->assertJsonPath('data.meta.total', 3)
        ->assertJsonPath('data.items.0.plate', '30A10001')
        ->assertJsonPath('data.items.0.cache_state', 'active')
        ->assertJsonPath('data.items.0.remaining_seconds', 82800)
        ->assertJsonPath('data.items.0.cache_duration_seconds', 86400)
        ->assertJsonPath('data.items.0.lookup_count', 4)
        ->assertJsonPath('data.items.0.positive_cache_hits', 2)
        ->assertJsonPath('data.items.0.provider_requests', 1)
        ->assertJsonPath('data.items.0.provider_errors', 1)
        ->assertJsonPath('data.items.0.cache_hit_rate', 50)
        ->assertJsonMissingPath('data.items.0.response_json');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('secret_provider_payload');
});

test('cached plate management filters normalized plates and paginates deterministic sorting', function (): void {
    $this->travelTo(now()->startOfDay()->addHours(12));
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    TrafficFineResult::factory()->create([
        'plate' => '30A10001',
        'vehicle_type' => 'car',
        'status' => 'success',
        'violation_count' => 1,
        'checked_at' => now()->subHour(),
        'expires_at' => now()->addHours(10),
    ]);
    TrafficFineResult::factory()->create([
        'plate' => '30A20002',
        'vehicle_type' => 'car',
        'status' => 'no_violation',
        'checked_at' => now()->subHour(),
        'expires_at' => now()->addMinutes(20),
    ]);
    TrafficFineResult::factory()->create([
        'plate' => '51B30003',
        'vehicle_type' => 'motorbike',
        'status' => 'no_violation',
        'checked_at' => now()->subDays(2),
        'expires_at' => now()->subMinute(),
    ]);

    $this->getJson('/api/admin-api/traffic-fines/results?search=30A-100.01&state=active&vehicle_type=car&status=success')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.items.0.plate', '30A10001');

    $this->getJson('/api/admin-api/traffic-fines/results?state=expiring')
        ->assertOk()
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.items.0.plate', '30A20002');

    $this->getJson('/api/admin-api/traffic-fines/results?sort=plate&direction=asc&per_page=1&page=2')
        ->assertOk()
        ->assertJsonPath('data.meta.current_page', 2)
        ->assertJsonPath('data.meta.last_page', 3)
        ->assertJsonPath('data.items.0.plate', '30A20002');
});
