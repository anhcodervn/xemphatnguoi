<?php

use App\Models\LookupHistory;
use App\Models\TrafficFineResult;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('creates the vehicle, lookup history, lookup log, and 24-hour result cache schema', function (): void {
    expect(Schema::hasColumns('user_vehicles', [
        'id',
        'user_id',
        'name',
        'plate',
        'vehicle_type',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('lookup_histories', [
            'id',
            'user_id',
            'traffic_fine_result_id',
            'plate',
            'vehicle_type',
            'violation_count',
            'created_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('traffic_fine_lookup_logs', [
            'id',
            'user_id',
            'plate',
            'vehicle_type',
            'source',
            'cache_hit',
            'provider',
            'provider_latency_ms',
            'status',
            'ip',
            'created_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('traffic_fine_results', [
            'id',
            'plate',
            'vehicle_type',
            'status',
            'violation_count',
            'response_json',
            'provider',
            'checked_at',
            'expires_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('does not recreate obsolete marketplace tables', function (string $table): void {
    expect(Schema::hasTable($table))->toBeFalse();
})->with([
    'categories' => 'proxy_categories',
    'providers' => 'proxy_providers',
    'products' => 'proxy_products',
    'orders' => 'proxy_orders',
    'user inventory' => 'user_proxies',
    'check batches' => 'proxy_check_batches',
    'check items' => 'proxy_check_items',
]);

it('keeps lookup history when a cached result is removed', function (): void {
    $history = LookupHistory::factory()->for(User::factory())->create();
    $result = TrafficFineResult::query()->findOrFail($history->traffic_fine_result_id);

    $result->delete();

    expect($history->refresh()->traffic_fine_result_id)->toBeNull();
});
