<?php

use App\Models\MonitoringPlan;
use App\Models\MonitoringSubscription;
use App\Models\User;
use App\Models\UserVehicle;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Laravel\Sanctum\Sanctum;

function monitoringPlanPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Gói 5 xe', 'description' => 'Theo dõi hằng ngày', 'is_custom' => false,
        'vehicle_limit' => 5, 'price' => 100000, 'unit_price' => null,
        'min_vehicle_count' => 20, 'duration_days' => 30, 'is_active' => true, 'sort_order' => 1,
    ], $overrides);
}

function walletForMonitoring(User $user, float $balance): Wallet
{
    return $user->wallets()->updateOrCreate(['type' => Wallet::TYPE_MAIN], [
        'balance' => $balance, 'hold_balance' => 0,
        'total_recharge' => $balance, 'total_spent' => 0,
    ]);
}

it('allows only admins to create and update fixed and custom monitoring plans', function (): void {
    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/admin-api/monitoring/plans', monitoringPlanPayload())->assertForbidden();

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $fixed = $this->postJson('/api/admin-api/monitoring/plans', monitoringPlanPayload())
        ->assertCreated()->assertJsonPath('data.plan.vehicle_limit', 5)
        ->assertJsonPath('data.plan.unit_price', null)->json('data.plan');

    $this->patchJson("/api/admin-api/monitoring/plans/{$fixed['id']}", monitoringPlanPayload([
        'name' => 'Gói linh hoạt', 'is_custom' => true, 'vehicle_limit' => 999,
        'price' => 999, 'unit_price' => 12000, 'min_vehicle_count' => 20,
    ]))->assertOk()->assertJsonPath('data.plan.is_custom', true)
        ->assertJsonPath('data.plan.vehicle_limit', null)->assertJsonPath('data.plan.price', null)
        ->assertJsonPath('data.plan.unit_price', '12000.00');

    $this->postJson('/api/admin-api/monitoring/plans', monitoringPlanPayload([
        'name' => 'Gói sai', 'is_custom' => true, 'unit_price' => 10000, 'min_vehicle_count' => 19,
    ]))->assertUnprocessable();
});

it('stores formatted package descriptions while removing executable html', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $description = <<<'HTML'
<h2 style="color:#2563eb;text-align:center;font-size:20px" onclick="alert(1)">Gói nổi bật</h2>
<script>alert('xss')</script>
<p><strong>Đầy đủ tính năng</strong> <a href="javascript:alert(1)" target="_blank">xấu</a></p>
<p><a href="https://example.com" target="_blank">Chi tiết</a><img src="/images/plan.jpg" onerror="alert(1)"><img src="data:text/html,xss"></p>
<svg onload="alert(1)"><circle /></svg>
HTML;

    $response = $this->postJson('/api/admin-api/monitoring/plans', monitoringPlanPayload([
        'name' => 'Gói HTML',
        'description' => $description,
    ]))->assertCreated();

    $storedDescription = (string) $response->json('data.plan.description');

    expect($storedDescription)
        ->toContain('<h2 style="color:#2563eb;text-align:center;font-size:20px">Gói nổi bật</h2>')
        ->toContain('<strong>Đầy đủ tính năng</strong>')
        ->toContain('href="https://example.com"')
        ->toContain('rel="noopener noreferrer"')
        ->toContain('src="/images/plan.jpg"');

    foreach (['script', 'onclick', 'javascript:', 'onerror', '<svg', 'data:text/html'] as $unsafeFragment) {
        expect($storedDescription)->not->toContain($unsafeFragment);
    }

    $planId = (int) $response->json('data.plan.id');
    $updated = $this->patchJson("/api/admin-api/monitoring/plans/{$planId}", monitoringPlanPayload([
        'name' => 'Gói HTML',
        'description' => '<p><strong>Không mã hóa hai lần</strong></p>',
    ]))->assertOk()->json('data.plan.description');

    expect($updated)->toBe('<p><strong>Không mã hóa hai lần</strong></p>');
});

it('lists only active plans for clients and returns their current entitlement', function (): void {
    $user = User::factory()->create();
    $active = MonitoringPlan::factory()->create(['name' => 'Đang bán']);
    MonitoringPlan::factory()->create(['is_active' => false]);
    MonitoringSubscription::factory()->for($user)->for($active, 'plan')->create(['vehicle_limit' => 5]);
    Sanctum::actingAs($user);

    $this->getJson('/api/client/monitoring-plans')->assertOk()->assertJsonCount(1, 'data.plans')
        ->assertJsonPath('data.plans.0.name', 'Đang bán')->assertJsonPath('data.subscription.vehicle_limit', 5);
});

it('calculates custom plan payment on the server with minimum 20 and no maximum', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 2000000000);
    $plan = MonitoringPlan::factory()->create([
        'is_custom' => true, 'vehicle_limit' => null, 'price' => null,
        'unit_price' => 1000, 'min_vehicle_count' => 20,
    ]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/subscribe', [
        'plan_id' => $plan->id, 'vehicle_count' => 19,
    ])->assertUnprocessable();

    $this->postJson('/api/client/monitoring-plans/subscribe', [
        'plan_id' => $plan->id, 'vehicle_count' => 100000, 'total_price' => 1,
    ])->assertCreated()->assertJsonPath('data.payment.amount', '100000000.00')
        ->assertJsonPath('data.payment.vehicle_limit', 100000);

    expect($user->wallets()->firstOrFail()->refresh()->balance)->toBe('1900000000.00');
    $this->assertDatabaseHas('monitoring_subscriptions', [
        'user_id' => $user->id, 'vehicle_limit' => 100000, 'total_price' => 100000000,
    ]);
});

it('uses fixed plan limits and prevents overlapping subscriptions or double debit', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 500000);
    $plan = MonitoringPlan::factory()->create(['vehicle_limit' => 5, 'price' => 100000]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/subscribe', [
        'plan_id' => $plan->id, 'vehicle_count' => 999,
    ])->assertCreated()->assertJsonPath('data.payment.vehicle_limit', 5);
    $this->postJson('/api/client/monitoring-plans/subscribe', ['plan_id' => $plan->id])->assertUnprocessable();

    expect($user->monitoringSubscriptions()->count())->toBe(1)
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('400000.00');
});

it('allows upgrading to a higher vehicle limit and transfers automatic renewal', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 1000000);
    $currentPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 5, 'price' => 100000]);
    $higherPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 10, 'price' => 250000]);
    $currentSubscription = MonitoringSubscription::factory()->for($user)->for($currentPlan, 'plan')->create([
        'vehicle_limit' => 5,
        'auto_renew' => true,
    ]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/upgrade', ['plan_id' => $higherPlan->id])
        ->assertCreated()
        ->assertJsonPath('data.subscription.vehicle_limit', 10)
        ->assertJsonPath('data.subscription.auto_renew', true)
        ->assertJsonPath('data.payment.amount', '250000.00');

    $this->postJson('/api/client/monitoring-plans/upgrade', ['plan_id' => $higherPlan->id])
        ->assertUnprocessable();

    $upgradedSubscription = MonitoringSubscription::query()
        ->where('upgraded_from_subscription_id', $currentSubscription->id)
        ->firstOrFail();

    expect($currentSubscription->refresh()->status)->toBe(MonitoringSubscription::STATUS_UPGRADED)
        ->and($currentSubscription->auto_renew)->toBeFalse()
        ->and($upgradedSubscription->status)->toBe(MonitoringSubscription::STATUS_ACTIVE)
        ->and($upgradedSubscription->auto_renew)->toBeTrue()
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('750000.00')
        ->and(WalletTransaction::query()->where('reference_type', 'monitoring_subscription_upgrade')->count())->toBe(1);
});

it('rejects an upgrade with an equal or lower vehicle limit regardless of price', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 1000000);
    $currentPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 5]);
    $equalPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 5, 'price' => 900000]);
    $lowerPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 3, 'price' => 950000]);
    MonitoringSubscription::factory()->for($user)->for($currentPlan, 'plan')->create(['vehicle_limit' => 5]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/upgrade', ['plan_id' => $equalPlan->id])->assertUnprocessable();
    $this->postJson('/api/client/monitoring-plans/upgrade', ['plan_id' => $lowerPlan->id])->assertUnprocessable();

    expect($user->monitoringSubscriptions()->count())->toBe(1)
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('1000000.00');
});

it('allows upgrading a custom plan by choosing a higher vehicle quantity', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 1000000);
    $currentPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 20]);
    $customPlan = MonitoringPlan::factory()->create([
        'is_custom' => true,
        'vehicle_limit' => null,
        'price' => null,
        'unit_price' => 10000,
        'min_vehicle_count' => 20,
    ]);
    MonitoringSubscription::factory()->for($user)->for($currentPlan, 'plan')->create(['vehicle_limit' => 20]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/upgrade', [
        'plan_id' => $customPlan->id,
        'vehicle_count' => 20,
    ])->assertUnprocessable();

    $this->postJson('/api/client/monitoring-plans/upgrade', [
        'plan_id' => $customPlan->id,
        'vehicle_count' => 21,
        'total_price' => 1,
    ])->assertCreated()
        ->assertJsonPath('data.subscription.vehicle_limit', 21)
        ->assertJsonPath('data.payment.amount', '210000.00');

    expect($user->wallets()->firstOrFail()->refresh()->balance)->toBe('790000.00');
});

it('rolls back an upgrade when the wallet balance is insufficient', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 1000);
    $currentPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 5]);
    $higherPlan = MonitoringPlan::factory()->create(['vehicle_limit' => 10, 'price' => 250000]);
    $currentSubscription = MonitoringSubscription::factory()->for($user)->for($currentPlan, 'plan')->create([
        'vehicle_limit' => 5,
        'auto_renew' => true,
    ]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/upgrade', ['plan_id' => $higherPlan->id])
        ->assertUnprocessable();

    expect($currentSubscription->refresh()->status)->toBe(MonitoringSubscription::STATUS_ACTIVE)
        ->and($currentSubscription->auto_renew)->toBeTrue()
        ->and($user->monitoringSubscriptions()->count())->toBe(1)
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('1000.00');
});

it('rolls back the subscription when wallet balance is insufficient', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 1000);
    $plan = MonitoringPlan::factory()->create(['price' => 100000]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/subscribe', ['plan_id' => $plan->id])->assertUnprocessable();
    expect($user->monitoringSubscriptions()->count())->toBe(0)
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('1000.00');
});

it('requires an active package and enforces the exact vehicle quota', function (): void {
    $user = User::factory()->create();
    [$firstVehicle, $secondVehicle] = UserVehicle::factory()->count(2)->for($user)->create();
    Sanctum::actingAs($user);
    $endpoint = fn (UserVehicle $vehicle): string => "/api/client/traffic-fines/vehicles/{$vehicle->id}/monitoring";

    $this->patchJson($endpoint($firstVehicle), ['enabled' => true, 'email_notifications' => true])->assertUnprocessable();
    $plan = MonitoringPlan::factory()->create(['vehicle_limit' => 1]);
    MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create(['vehicle_limit' => 1]);
    $this->patchJson($endpoint($firstVehicle), ['enabled' => true, 'email_notifications' => true])->assertOk();
    $this->patchJson($endpoint($secondVehicle), ['enabled' => true, 'email_notifications' => true])->assertUnprocessable();
    $this->patchJson($endpoint($firstVehicle), ['enabled' => false, 'email_notifications' => true])
        ->assertOk()->assertJsonPath('data.email_notifications', false);
    $this->patchJson($endpoint($secondVehicle), ['enabled' => true, 'email_notifications' => false])->assertOk();
});

it('allows buying again after the previous subscription expires', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 500000);
    $plan = MonitoringPlan::factory()->create(['price' => 100000]);
    MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create([
        'started_at' => now()->subDays(31), 'expires_at' => now()->subDay(),
    ]);
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/subscribe', ['plan_id' => $plan->id])->assertCreated();
    expect($user->monitoringSubscriptions()->count())->toBe(2);
});

it('rejects a new plan smaller than the number of vehicles already enabled', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 500000);
    $plan = MonitoringPlan::factory()->create(['vehicle_limit' => 1, 'price' => 100000]);
    UserVehicle::factory()->count(2)->for($user)->create()->each(function (UserVehicle $vehicle) use ($user): void {
        $vehicle->monitoring()->create([
            'user_id' => $user->id,
            'enabled' => true,
            'email_notifications' => false,
        ]);
    });
    Sanctum::actingAs($user);

    $this->postJson('/api/client/monitoring-plans/subscribe', ['plan_id' => $plan->id])->assertUnprocessable();
    expect($user->monitoringSubscriptions()->count())->toBe(0);
});

it('does not delete a plan that already has subscription history', function (): void {
    $admin = User::factory()->create(['role' => 'admin']);
    $plan = MonitoringPlan::factory()->create();
    MonitoringSubscription::factory()->for($plan, 'plan')->create();
    Sanctum::actingAs($admin);

    $this->deleteJson("/api/admin-api/monitoring/plans/{$plan->id}")->assertUnprocessable();
    $this->assertDatabaseHas('monitoring_plans', ['id' => $plan->id]);
});

it('allows the owner to turn automatic renewal on and off', function (): void {
    $user = User::factory()->create();
    $plan = MonitoringPlan::factory()->create();
    $subscription = MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/client/monitoring-plans/subscription/auto-renew', ['auto_renew' => true])
        ->assertOk()
        ->assertJsonPath('data.subscription.id', $subscription->id)
        ->assertJsonPath('data.subscription.auto_renew', true)
        ->assertJsonPath('data.subscription.is_active', true);

    $this->patchJson('/api/client/monitoring-plans/subscription/auto-renew', ['auto_renew' => false])
        ->assertOk()
        ->assertJsonPath('data.subscription.auto_renew', false);

    $this->patchJson('/api/client/monitoring-plans/subscription/auto-renew', ['auto_renew' => 'invalid'])
        ->assertUnprocessable();
});

it('does not allow automatic renewal to be enabled for an inactive plan', function (): void {
    $user = User::factory()->create();
    $plan = MonitoringPlan::factory()->create(['is_active' => false]);
    MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create();
    Sanctum::actingAs($user);

    $this->patchJson('/api/client/monitoring-plans/subscription/auto-renew', ['auto_renew' => true])
        ->assertUnprocessable();

    expect($user->monitoringSubscriptions()->firstOrFail()->auto_renew)->toBeFalse();
});

it('keeps an expired renewal visible so the user can turn it off', function (): void {
    $user = User::factory()->create();
    $plan = MonitoringPlan::factory()->create();
    $subscription = MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create([
        'auto_renew' => true,
        'started_at' => now()->subDays(31),
        'expires_at' => now()->subMinute(),
    ]);
    Sanctum::actingAs($user);

    $this->getJson('/api/client/monitoring-plans')
        ->assertOk()
        ->assertJsonPath('data.subscription.id', $subscription->id)
        ->assertJsonPath('data.subscription.is_active', false)
        ->assertJsonPath('data.subscription.auto_renew', true);

    $this->patchJson('/api/client/monitoring-plans/subscription/auto-renew', ['auto_renew' => false])
        ->assertOk()
        ->assertJsonPath('data.subscription', null);
});

it('automatically renews an expired package once and debits the wallet once', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 500000);
    $plan = MonitoringPlan::factory()->create(['price' => 100000]);
    $subscription = MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create([
        'auto_renew' => true,
        'total_price' => 100000,
        'started_at' => now()->subDays(31),
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('monitoring-subscriptions:renew')->assertSuccessful();
    $this->artisan('monitoring-subscriptions:renew')->assertSuccessful();

    $renewedSubscription = MonitoringSubscription::query()
        ->where('renewed_from_subscription_id', $subscription->id)
        ->firstOrFail();

    expect($user->monitoringSubscriptions()->count())->toBe(2)
        ->and($subscription->refresh()->status)->toBe(MonitoringSubscription::STATUS_RENEWED)
        ->and($subscription->auto_renew)->toBeFalse()
        ->and($renewedSubscription->auto_renew)->toBeTrue()
        ->and($renewedSubscription->renewal_count)->toBe(1)
        ->and($renewedSubscription->expires_at?->isFuture())->toBeTrue()
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('400000.00')
        ->and(WalletTransaction::query()->where('reference_type', 'monitoring_subscription_renewal')->count())->toBe(1);
});

it('waits for enough wallet balance before automatically renewing', function (): void {
    $user = User::factory()->create();
    $wallet = walletForMonitoring($user, 1000);
    $plan = MonitoringPlan::factory()->create(['price' => 100000]);
    $subscription = MonitoringSubscription::factory()->for($user)->for($plan, 'plan')->create([
        'auto_renew' => true,
        'total_price' => 100000,
        'started_at' => now()->subDays(31),
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('monitoring-subscriptions:renew')->assertSuccessful();

    expect($subscription->refresh()->auto_renew)->toBeTrue()
        ->and($subscription->status)->toBe(MonitoringSubscription::STATUS_ACTIVE)
        ->and($user->monitoringSubscriptions()->count())->toBe(1)
        ->and($wallet->refresh()->balance)->toBe('1000.00');

    $wallet->update(['balance' => 200000]);
    $this->artisan('monitoring-subscriptions:renew')->assertSuccessful();

    expect($user->monitoringSubscriptions()->count())->toBe(2)
        ->and($wallet->refresh()->balance)->toBe('100000.00');
});

it('does not renew when automatic renewal is off or the plan is inactive', function (): void {
    $user = User::factory()->create();
    walletForMonitoring($user, 500000);
    $inactivePlan = MonitoringPlan::factory()->create(['is_active' => false]);
    MonitoringSubscription::factory()->for($user)->for($inactivePlan, 'plan')->create([
        'auto_renew' => true,
        'started_at' => now()->subDays(31),
        'expires_at' => now()->subMinute(),
    ]);
    MonitoringSubscription::factory()->for(User::factory())->create([
        'auto_renew' => false,
        'started_at' => now()->subDays(31),
        'expires_at' => now()->subMinute(),
    ]);

    $this->artisan('monitoring-subscriptions:renew')->assertSuccessful();

    expect(MonitoringSubscription::query()->whereNotNull('renewed_from_subscription_id')->count())->toBe(0)
        ->and($user->wallets()->firstOrFail()->refresh()->balance)->toBe('500000.00');
});
