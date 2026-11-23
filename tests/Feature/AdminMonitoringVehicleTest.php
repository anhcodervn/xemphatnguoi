<?php

use App\Models\MonitoringSubscription;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use Laravel\Sanctum\Sanctum;

it('restricts the monitoring vehicle list to admins', function (): void {
    $this->getJson('/api/admin-api/monitoring/vehicles')->assertUnauthorized();
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/admin-api/monitoring/vehicles')->assertForbidden();
});

it('lists enabled vehicles across customers including those without a current package', function (): void {
    $customer = User::factory()->create();
    $subscription = MonitoringSubscription::factory()->for($customer)->create([
        'started_at' => now()->subDay(), 'expires_at' => now()->addDay(),
    ]);
    $first = VehicleMonitoring::factory()->for($customer)->create(['enabled' => true]);
    $second = VehicleMonitoring::factory()->create(['enabled' => true]);
    VehicleMonitoring::factory()->create(['enabled' => false]);
    UserVehicle::factory()->create();

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $response = $this->getJson('/api/admin-api/monitoring/vehicles')->assertOk()
        ->assertJsonCount(2, 'data.vehicles')
        ->assertJsonPath('data.meta.total', 2)
        ->assertJsonPath('data.vehicles.0.id', $second->id)
        ->assertJsonPath('data.vehicles.0.subscription', null)
        ->assertJsonPath('data.vehicles.0.last_checked_at', null)
        ->assertJsonPath('data.vehicles.0.last_violation_count', null)
        ->assertJsonPath('data.vehicles.1.id', $first->id)
        ->assertJsonPath('data.vehicles.1.user.id', $customer->id)
        ->assertJsonPath('data.vehicles.1.subscription.id', $subscription->id);

    expect($response->json('data.vehicles.1.user'))->not->toHaveKey('password');
});

it('filters by plate vehicle name customer and vehicle type without leaking disabled vehicles', function (string $search): void {
    $customer = User::factory()->create(['username' => 'fleetowner', 'email' => 'fleet@example.com', 'full_name' => 'Fleet Customer']);
    $vehicle = UserVehicle::factory()->for($customer)->create(['plate' => '30A12345', 'name' => 'Delivery van', 'vehicle_type' => 'car']);
    $monitoring = VehicleMonitoring::factory()->for($customer)->for($vehicle, 'vehicle')->create([
        'enabled' => true, 'email_notifications' => true, 'last_checked_at' => now(), 'last_violation_count' => 2,
    ]);
    VehicleMonitoring::factory()->for($customer)->create(['enabled' => false]);
    VehicleMonitoring::factory()->create(['enabled' => true]);

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->getJson('/api/admin-api/monitoring/vehicles?'.http_build_query(['search' => $search, 'vehicle_type' => 'car']))
        ->assertOk()->assertJsonCount(1, 'data.vehicles')
        ->assertJsonPath('data.vehicles.0.id', $monitoring->id)
        ->assertJsonPath('data.vehicles.0.email_notifications', true)
        ->assertJsonPath('data.vehicles.0.last_violation_count', 2);
})->with(['30A12345', 'Delivery', 'fleetowner', 'fleet@example.com', 'Fleet Customer']);

it('paginates all configured monitoring vehicles and supports disabled filters', function (): void {
    VehicleMonitoring::factory()->count(11)->create(['enabled' => true]);
    $disabled = VehicleMonitoring::factory()->create(['enabled' => false]);
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/monitoring/vehicles?status=all&per_page=10&page=2')
        ->assertOk()->assertJsonCount(2, 'data.vehicles')
        ->assertJsonPath('data.meta.total', 12)->assertJsonPath('data.meta.current_page', 2);
    $this->getJson('/api/admin-api/monitoring/vehicles?status=disabled')
        ->assertOk()->assertJsonCount(1, 'data.vehicles')->assertJsonPath('data.vehicles.0.id', $disabled->id);
    $this->getJson('/api/admin-api/monitoring/vehicles?vehicle_type=motorbike')
        ->assertOk()->assertJsonCount(0, 'data.vehicles');
});

it('validates monitoring vehicle filters', function (array $filters): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->getJson('/api/admin-api/monitoring/vehicles?'.http_build_query($filters))->assertUnprocessable();
})->with([
    [['status' => 'unknown']], [['vehicle_type' => 'truck']], [['per_page' => 101]], [['page' => 0]], [['search' => str_repeat('a', 101)]],
]);
