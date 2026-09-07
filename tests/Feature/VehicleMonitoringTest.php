<?php

use App\Features\TrafficFine\Actions\CheckVehicleMonitoringAction;
use App\Features\TrafficFine\DTOs\TrafficFineLookupResponseDto;
use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Services\TrafficFineV2LookupService;
use App\Jobs\CheckVehicleMonitoringJob;
use App\Mail\VehicleMonitoringChangedMail;
use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\TrafficFineResult;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use App\Models\WalletTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

function fakeMonitoringLookup(string $plate, string $vehicleType, int $violationCount): void
{
    $lookupService = Mockery::mock(TrafficFineV2LookupService::class);
    $lookupService->shouldReceive('lookup')
        ->once()
        ->with($plate, $vehicleType, null, null, true)
        ->andReturn(new TrafficFineLookupResponseDto(
            data: new TrafficFineLookupResultDataDto(
                plate: $plate,
                displayPlate: $plate,
                vehicleType: $vehicleType,
                status: $violationCount > 0 ? 'success' : 'no_violation',
                violationCount: $violationCount,
                violations: [],
                checkedAt: CarbonImmutable::parse('2026-09-04 07:00:00'),
            ),
            cached: false,
        ));

    app()->instance(TrafficFineV2LookupService::class, $lookupService);
}

function grantMonitoringPackage(User $user, int $vehicleLimit = 10): void
{
    $plan = MonitoringPlan::factory()->create(['vehicle_limit' => $vehicleLimit]);
    MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create([
        'vehicle_limit' => $vehicleLimit,
    ]);
}

it('allows users to configure monitoring and email for their own vehicle', function (): void {
    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create();
    Sanctum::actingAs($user);

    $this->patchJson("/api/client/traffic-fines/vehicles/{$vehicle->id}/monitoring", [
        'enabled' => true,
        'email_notifications' => true,
    ])->assertOk()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.email_notifications', true);

    $monitoring = $vehicle->monitoring()->firstOrFail();

    expect($monitoring->user_id)->toBe($user->id)
        ->and($monitoring->enabled)->toBeTrue()
        ->and($monitoring->email_notifications)->toBeTrue();

    $this->patchJson("/api/client/traffic-fines/vehicles/{$vehicle->id}/monitoring", [
        'enabled' => false,
        'email_notifications' => true,
    ])->assertOk()
        ->assertJsonPath('data.enabled', false)
        ->assertJsonPath('data.email_notifications', false);
});

it('returns monitoring configuration and vehicles for the authenticated user', function (): void {
    config(['traffic-fines.monitoring.interval_hours' => 8]);
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $vehicle = UserVehicle::factory()->for($user)->create();
    UserVehicle::factory()->for($otherUser)->create();
    VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => true,
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/client/traffic-fines/monitoring')
        ->assertOk()
        ->assertJsonPath('data.interval_hours', 8)
        ->assertJsonCount(1, 'data.vehicles')
        ->assertJsonPath('data.vehicles.0.id', $vehicle->id)
        ->assertJsonPath('data.vehicles.0.monitoring.email_notifications', true);
});

it('protects monitoring configuration with authentication ownership and validation', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherVehicle = UserVehicle::factory()->for($otherUser)->create();

    $this->patchJson("/api/client/traffic-fines/vehicles/{$otherVehicle->id}/monitoring", [
        'enabled' => true,
        'email_notifications' => true,
    ])->assertUnauthorized();
    $this->getJson('/api/client/traffic-fines/monitoring')->assertUnauthorized();

    Sanctum::actingAs($user);

    $this->patchJson("/api/client/traffic-fines/vehicles/{$otherVehicle->id}/monitoring", [
        'enabled' => true,
        'email_notifications' => true,
    ])->assertForbidden();

    $ownVehicle = UserVehicle::factory()->for($user)->create();
    $this->patchJson("/api/client/traffic-fines/vehicles/{$ownVehicle->id}/monitoring", [
        'enabled' => 'invalid',
    ])->assertUnprocessable();
});

it('queues an email when the daily violation count changes', function (): void {
    $user = User::factory()->create(['email' => 'driver@example.com']);
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create([
        'name' => 'Xe gia đình',
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ]);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => true,
        'last_violation_count' => 2,
    ]);
    fakeMonitoringLookup($vehicle->plate, $vehicle->vehicle_type, 1);
    Mail::fake();

    app(CheckVehicleMonitoringAction::class)->handle($monitoring->id);

    expect($monitoring->refresh()->last_violation_count)->toBe(1)
        ->and($monitoring->last_checked_at?->toDateTimeString())->toBe('2026-09-04 07:00:00')
        ->and(WalletTransaction::query()->exists())->toBeFalse();
    Mail::assertQueued(VehicleMonitoringChangedMail::class, function (VehicleMonitoringChangedMail $mail): bool {
        return $mail->hasTo('driver@example.com')
            && $mail->previousViolationCount === 2
            && $mail->currentViolationCount === 1;
    });
});

it('uses the v2 provider for scheduled package checks without charging the user wallet', function (): void {
    config([
        'traffic-fines.api_v2.url' => 'https://api.xephatnguoi.com/v2/search',
        'traffic-fines.sources.xephatnguoi.token' => 'monitoring-v2-token',
        'traffic-fines.cache.store' => 'array',
    ]);
    Cache::store('array')->flush();
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v2/search*' => Http::response([
            'status' => 'success',
            'plate' => '30K12345',
            'type' => 1,
            'data' => [],
            'total' => 0,
            'timestamp' => '2026-09-06 08:00:00',
        ]),
    ]);
    Mail::fake();

    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create([
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ]);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => false,
    ]);

    app(CheckVehicleMonitoringAction::class)->handle($monitoring->id);

    Http::assertSent(function (ClientRequest $request): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return parse_url($request->url(), PHP_URL_PATH) === '/v2/search'
            && $query === ['plate' => '30K12345', 'type' => '1']
            && $request->hasHeader('Authorization', 'Bearer monitoring-v2-token');
    });
    expect(TrafficFineResult::query()->where('provider', 'xephatnguoi_v2')->exists())->toBeTrue()
        ->and(WalletTransaction::query()->exists())->toBeFalse();
});

it('does not email when the daily violation count is unchanged', function (): void {
    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create(['plate' => '30A12345']);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => true,
        'last_violation_count' => 2,
    ]);
    fakeMonitoringLookup($vehicle->plate, $vehicle->vehicle_type, 2);
    Mail::fake();

    app(CheckVehicleMonitoringAction::class)->handle($monitoring->id);

    expect($monitoring->refresh()->last_violation_count)->toBe(2);
    Mail::assertNothingQueued();
});

it('emails on the first check only when violations are found', function (int $violationCount, bool $shouldSend): void {
    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create(['plate' => '30A12345']);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => true,
        'last_violation_count' => null,
    ]);
    fakeMonitoringLookup($vehicle->plate, $vehicle->vehicle_type, $violationCount);
    Mail::fake();

    app(CheckVehicleMonitoringAction::class)->handle($monitoring->id);

    if ($shouldSend) {
        Mail::assertQueued(VehicleMonitoringChangedMail::class);
    } else {
        Mail::assertNothingQueued();
    }
})->with([
    'no violations' => [0, false],
    'violations found' => [2, true],
]);

it('updates the baseline without email when email notifications are disabled', function (): void {
    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create(['plate' => '30A12345']);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => false,
        'last_violation_count' => 2,
    ]);
    fakeMonitoringLookup($vehicle->plate, $vehicle->vehicle_type, 1);
    Mail::fake();

    app(CheckVehicleMonitoringAction::class)->handle($monitoring->id);

    expect($monitoring->refresh()->last_violation_count)->toBe(1);
    Mail::assertNothingQueued();
});

it('preserves the previous baseline when the provider lookup fails', function (): void {
    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $vehicle = UserVehicle::factory()->for($user)->create(['plate' => '30A12345']);
    $monitoring = VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'email_notifications' => true,
        'last_checked_at' => CarbonImmutable::parse('2026-09-03 07:00:00'),
        'last_violation_count' => 2,
    ]);
    $lookupService = Mockery::mock(TrafficFineV2LookupService::class);
    $lookupService->shouldReceive('lookup')->once()->andThrow(new TrafficFineProviderException('Provider unavailable.'));
    app()->instance(TrafficFineV2LookupService::class, $lookupService);

    expect(fn () => app(CheckVehicleMonitoringAction::class)->handle($monitoring->id))
        ->toThrow(TrafficFineProviderException::class);

    expect($monitoring->refresh()->last_violation_count)->toBe(2)
        ->and($monitoring->last_checked_at?->toDateTimeString())->toBe('2026-09-03 07:00:00');
});

it('dispatches checks only for enabled monitorings', function (): void {
    $user = User::factory()->create();
    grantMonitoringPackage($user);
    $enabledVehicle = UserVehicle::factory()->for($user)->create();
    $disabledVehicle = UserVehicle::factory()->for($user)->create();
    $enabledMonitoring = VehicleMonitoring::factory()->for($user)->for($enabledVehicle, 'vehicle')->create(['enabled' => true]);
    VehicleMonitoring::factory()->for($user)->for($disabledVehicle, 'vehicle')->create(['enabled' => false]);
    Bus::fake();

    $this->artisan('traffic-fines:dispatch-monitoring-checks')
        ->expectsOutput('Đã đưa 1 biển số đến hạn vào hàng đợi (chu kỳ 24 giờ).')
        ->assertSuccessful();

    Bus::assertDispatched(CheckVehicleMonitoringJob::class, fn (CheckVehicleMonitoringJob $job): bool => $job->monitoringId === $enabledMonitoring->id);
    Bus::assertDispatchedTimes(CheckVehicleMonitoringJob::class, 1);
});

it('renders the monitoring change email with the comparison and result link', function (): void {
    $html = (new VehicleMonitoringChangedMail(
        vehicleName: 'Xe gia đình',
        plate: '30A12345',
        vehicleType: 'car',
        previousViolationCount: 2,
        currentViolationCount: 1,
    ))->render();

    expect($html)
        ->toContain('Dữ liệu phạt nguội đã thay đổi')
        ->toContain('Số lỗi đã thay đổi từ')
        ->toContain('30A12345')
        ->toContain('/tra-cuu/30A12345?vehicle_type=car&amp;api_version=v2');
});
