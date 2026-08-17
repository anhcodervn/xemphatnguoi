<?php

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Models\LookupHistory;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use Laravel\Sanctum\Sanctum;

it('allows a user to manage only their own vehicles', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherVehicle = UserVehicle::factory()->for($otherUser)->create();

    Sanctum::actingAs($user);

    $createResponse = $this->postJson('/api/client/traffic-fines/vehicles', [
        'name' => 'Mazda 3',
        'plate' => '30A-123.45',
        'vehicle_type' => 'car',
    ])->assertCreated()
        ->assertJsonPath('data.plate', '30A12345');

    $vehicleId = $createResponse->json('data.id');

    $this->patchJson("/api/client/traffic-fines/vehicles/{$vehicleId}", [
        'name' => 'Xe gia đình',
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ])->assertOk()->assertJsonPath('data.name', 'Xe gia đình');

    $this->getJson("/api/client/traffic-fines/vehicles/{$otherVehicle->id}")->assertForbidden();
    $this->patchJson("/api/client/traffic-fines/vehicles/{$otherVehicle->id}", [
        'name' => 'Không được phép',
        'plate' => $otherVehicle->plate,
        'vehicle_type' => $otherVehicle->vehicle_type,
    ])->assertForbidden();
    $this->deleteJson("/api/client/traffic-fines/vehicles/{$otherVehicle->id}")->assertForbidden();

    $this->deleteJson("/api/client/traffic-fines/vehicles/{$vehicleId}")->assertOk();
    $this->assertDatabaseMissing('user_vehicles', ['id' => $vehicleId]);
});

it('requires authentication for vehicle endpoints', function (): void {
    $this->getJson('/api/client/traffic-fines/vehicles')->assertUnauthorized();
});

it('returns a safe error when a vehicle lookup provider is unavailable', function (): void {
    $user = User::factory()->create();
    $vehicle = UserVehicle::factory()->for($user)->create();
    $source = new class implements TrafficFineSourceInterface
    {
        public function name(): string
        {
            return 'unavailable_source';
        }

        public function lookup(string $normalizedPlate, VehicleType $vehicleType): TrafficFineLookupResultDataDto
        {
            throw new TrafficFineProviderException('Internal provider detail.');
        }
    };

    app()->instance(TrafficFineSourceInterface::class, $source);
    Sanctum::actingAs($user);

    $this->postJson("/api/client/traffic-fines/vehicles/{$vehicle->id}/lookup")
        ->assertStatus(503)
        ->assertJsonPath('status', 'provider_error')
        ->assertJsonMissing(['message' => 'Internal provider detail.']);
});

it('does not look up a stored vehicle type after that provider type is disabled', function (): void {
    $user = User::factory()->create();
    $vehicle = UserVehicle::factory()->for($user)->create(['vehicle_type' => 'motorbike']);
    config(['traffic-fines.vehicle_types.motorbike.enabled' => false]);
    Sanctum::actingAs($user);

    $this->postJson("/api/client/traffic-fines/vehicles/{$vehicle->id}/lookup")
        ->assertUnprocessable()
        ->assertJsonPath('status', 'invalid_vehicle_type');
});

it('shows actual traffic fine activity in the admin user detail', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $vehicle = UserVehicle::factory()->for($user)->create();

    LookupHistory::factory()->count(2)->for($user)->create();
    VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create();

    Sanctum::actingAs($admin);

    $this->getJson("/api/admin-api/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.stats.lookup_count', 2)
        ->assertJsonPath('data.stats.vehicle_count', 1)
        ->assertJsonPath('data.stats.monitoring_count', 1);
});
