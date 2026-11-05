<?php

use App\Features\TrafficFine\Services\ApiLookupAvailabilityService;
use App\Features\TrafficFine\Services\TrafficFineProviderSettingsService;
use App\Models\Setting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function apiAvailabilityPayload(bool $v1Enabled, bool $v2Enabled): array
{
    return [
        'api_request_price' => 20,
        'api_v2_request_price' => 150,
        'api_request_cost' => 7,
        'api_v2_request_cost' => 90,
        'api_v1_enabled' => $v1Enabled,
        'api_v2_enabled' => $v2Enabled,
    ];
}

it('lets an admin put each api version into maintenance independently', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->putJson('/api/admin-api/traffic-fines/billing', apiAvailabilityPayload(false, true))
        ->assertOk()
        ->assertJsonPath('data.api_v1_enabled', false)
        ->assertJsonPath('data.api_v2_enabled', true);

    expect(app(ApiLookupAvailabilityService::class)->isEnabled('v1'))->toBeFalse()
        ->and(app(ApiLookupAvailabilityService::class)->isEnabled('v2'))->toBeTrue()
        ->and(Setting::query()->where('key', ApiLookupAvailabilityService::V1_ENABLED_SETTING_KEY)->value('type'))->toBe('boolean')
        ->and(Setting::query()->where('key', ApiLookupAvailabilityService::V2_ENABLED_SETTING_KEY)->value('type'))->toBe('boolean');
});

it('blocks v1 lookup before calling the lookup controller while v1 is in maintenance', function (): void {
    app(ApiLookupAvailabilityService::class)->update(v1Enabled: false, v2Enabled: true);

    $this->postJson('/api/lookup', ['plate' => '30A12345', 'vehicle_type' => 'car'])
        ->assertServiceUnavailable()
        ->assertJson([
            'success' => false,
            'status' => 'api_maintenance',
        ])
        ->assertJsonPath('message', 'Cổng tra cứu API v1 đang bảo trì. Vui lòng thử lại sau.');
});

it('blocks v2 web lookup while the v2 public gateway is disabled', function (): void {
    app(ApiLookupAvailabilityService::class)->update(v1Enabled: true, v2Enabled: false);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/client/traffic-fines/lookup-v2', ['plate' => '30A12345', 'vehicle_type' => 'car'])
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'api_maintenance')
        ->assertJsonPath('message', 'Cổng tra cứu API v2 đang bảo trì. Vui lòng thử lại sau.');
});

it('lets admins select provider v1 for v2 while both public gateways remain enabled', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $payload = [...apiAvailabilityPayload(true, true), 'api_v2_provider_version' => 'v1'];
    $this->putJson('/api/admin-api/traffic-fines/billing', $payload)
        ->assertOk()
        ->assertJsonPath('data.api_v1_enabled', true)
        ->assertJsonPath('data.api_v2_enabled', true)
        ->assertJsonPath('data.api_v2_provider_version', 'v1');
    $this->getJson('/api/admin-api/traffic-fines/billing')
        ->assertOk()->assertJsonPath('data.api_v2_provider_version', 'v1');

    expect(app(TrafficFineProviderSettingsService::class)->v2ApiVersion())->toBe('v1');

    $this->putJson('/api/admin-api/traffic-fines/billing', apiAvailabilityPayload(true, true))
        ->assertOk()->assertJsonPath('data.api_v2_provider_version', 'v1');

    $this->putJson('/api/admin-api/traffic-fines/billing', [...$payload, 'api_v2_provider_version' => 'v2'])
        ->assertOk()->assertJsonPath('data.api_v2_provider_version', 'v2');
});

it('rejects unsupported provider versions without changing gateway configuration', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->putJson('/api/admin-api/traffic-fines/billing', [
        ...apiAvailabilityPayload(false, false), 'api_v2_provider_version' => 'v3',
    ])->assertUnprocessable();

    expect(app(ApiLookupAvailabilityService::class)->isEnabled('v1'))->toBeTrue()
        ->and(app(ApiLookupAvailabilityService::class)->isEnabled('v2'))->toBeTrue()
        ->and(app(TrafficFineProviderSettingsService::class)->v2ApiVersion())->toBe('v2');
});

it('does not let customers change the provider used by v2', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $this->putJson('/api/admin-api/traffic-fines/billing', [
        ...apiAvailabilityPayload(true, true), 'api_v2_provider_version' => 'v1',
    ])->assertForbidden();

    expect(app(TrafficFineProviderSettingsService::class)->v2ApiVersion())->toBe('v2');
});

it('does not let a customer change api maintenance state', function (): void {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/admin-api/traffic-fines/billing', apiAvailabilityPayload(false, false))
        ->assertForbidden();

    expect(app(ApiLookupAvailabilityService::class)->isEnabled('v1'))->toBeTrue()
        ->and(app(ApiLookupAvailabilityService::class)->isEnabled('v2'))->toBeTrue();
});
