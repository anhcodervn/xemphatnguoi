<?php

use App\Models\AdSlot;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows only admins to manage centralized ad slots', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/admin-api/traffic-fines/ad-slots')->assertForbidden();

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);

    $createResponse = $this->postJson('/api/admin-api/traffic-fines/ad-slots', [
        'name' => 'home_after_lookup',
        'code' => '<div data-ad-test="first">Ad</div>',
        'enabled' => true,
        'device' => 'all',
        'start_at' => now()->subMinute()->toISOString(),
        'end_at' => now()->addDay()->toISOString(),
    ])->assertCreated();

    $slotId = $createResponse->json('data.id');

    $this->patchJson("/api/admin-api/traffic-fines/ad-slots/{$slotId}", [
        'name' => 'home_after_lookup',
        'code' => '<div data-ad-test="updated">Ad</div>',
        'enabled' => true,
        'device' => 'desktop',
        'start_at' => null,
        'end_at' => null,
    ])->assertOk()
        ->assertJsonPath('data.name', 'home_after_lookup')
        ->assertJsonPath('data.device', 'desktop');

    $this->assertDatabaseHas('ad_slots', [
        'id' => $slotId,
        'name' => 'home_after_lookup',
        'device' => 'desktop',
    ]);
});

it('renders only an active ad slot through the Blade component', function (): void {
    $this->withoutVite();
    AdSlot::factory()->create([
        'name' => 'lookup_result_bottom',
        'code' => '<div data-ad-test="active">Ad</div>',
        'enabled' => true,
        'device' => 'all',
        'start_at' => now()->subMinute(),
        'end_at' => now()->addMinute(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('<div data-ad-test="active">Ad</div>', false);
});
