<?php

use App\Features\TrafficFine\Services\VehicleMonitoringSettingsService;
use App\Jobs\CheckVehicleMonitoringJob;
use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\VehicleMonitoring;
use App\Support\SettingStore;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;

function createScheduledMonitoring(User $user, ?string $lastDispatchedAt): VehicleMonitoring
{
    $vehicle = UserVehicle::factory()->for($user)->create();

    return VehicleMonitoring::factory()->for($user)->for($vehicle, 'vehicle')->create([
        'enabled' => true,
        'last_dispatched_at' => $lastDispatchedAt,
    ]);
}

function grantScheduledMonitoringPackage(User $user): void
{
    $plan = MonitoringPlan::factory()->create();
    MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create();
}

it('uses six hours by default and falls back safely for an invalid stored setting', function (): void {
    config(['traffic-fines.monitoring.interval_hours' => 6]);
    $settings = app(VehicleMonitoringSettingsService::class);

    expect($settings->intervalHours())->toBe(6);

    app(SettingStore::class)->putString(VehicleMonitoringSettingsService::INTERVAL_HOURS_KEY, 'invalid');

    expect($settings->intervalHours())->toBe(6);

    config(['traffic-fines.monitoring.interval_hours' => 0]);
    expect($settings->intervalHours())->toBe(6);
});

it('allows admins to update the monitoring interval', function (): void {
    $this->patchJson('/api/admin-api/settings/monitoring', ['interval_hours' => 12])->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());
    $this->patchJson('/api/admin-api/settings/monitoring', ['interval_hours' => 12])->assertForbidden();

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->patchJson('/api/admin-api/settings/monitoring', ['interval_hours' => 12])
        ->assertOk()
        ->assertJsonPath('data.settings.interval_hours', 12);

    $this->getJson('/api/admin-api/settings/monitoring')
        ->assertOk()
        ->assertJsonPath('data.settings.interval_hours', 12);
});

it('validates monitoring interval boundaries', function (int $intervalHours): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->patchJson('/api/admin-api/settings/monitoring', ['interval_hours' => $intervalHours])
        ->assertUnprocessable();
})->with([
    'less than one hour' => 0,
    'more than thirty days' => 721,
]);

it('dispatches each vehicle only when its configured interval is due', function (): void {
    $this->travelTo('2026-09-04 12:00:00');
    $user = User::factory()->create();
    grantScheduledMonitoringPackage($user);
    $neverDispatched = createScheduledMonitoring($user, null);
    $due = createScheduledMonitoring($user, '2026-09-04 06:00:00');
    $notDue = createScheduledMonitoring($user, '2026-09-04 06:01:00');
    Bus::fake();

    $this->artisan('traffic-fines:dispatch-monitoring-checks')
        ->expectsOutput('Đã đưa 2 biển số đến hạn vào hàng đợi (chu kỳ 6 giờ).')
        ->assertSuccessful();

    Bus::assertDispatched(CheckVehicleMonitoringJob::class, 2);
    Bus::assertDispatched(CheckVehicleMonitoringJob::class, fn (CheckVehicleMonitoringJob $job): bool => $job->monitoringId === $neverDispatched->id);
    Bus::assertDispatched(CheckVehicleMonitoringJob::class, fn (CheckVehicleMonitoringJob $job): bool => $job->monitoringId === $due->id);
    Bus::assertNotDispatched(CheckVehicleMonitoringJob::class, fn (CheckVehicleMonitoringJob $job): bool => $job->monitoringId === $notDue->id);
    expect($neverDispatched->refresh()->last_dispatched_at?->toDateTimeString())->toBe('2026-09-04 12:00:00');

    $this->artisan('traffic-fines:dispatch-monitoring-checks')
        ->expectsOutput('Đã đưa 0 biển số đến hạn vào hàng đợi (chu kỳ 6 giờ).')
        ->assertSuccessful();
    Bus::assertDispatched(CheckVehicleMonitoringJob::class, 2);
});

it('applies an admin interval change on the next dispatcher run', function (): void {
    $this->travelTo('2026-09-04 12:00:00');
    $user = User::factory()->create();
    grantScheduledMonitoringPackage($user);
    $monitoring = createScheduledMonitoring($user, '2026-09-04 09:00:00');
    app(VehicleMonitoringSettingsService::class)->updateIntervalHours(2);
    Bus::fake();

    $this->artisan('traffic-fines:dispatch-monitoring-checks')->assertSuccessful();

    Bus::assertDispatched(CheckVehicleMonitoringJob::class, fn (CheckVehicleMonitoringJob $job): bool => $job->monitoringId === $monitoring->id);
});
