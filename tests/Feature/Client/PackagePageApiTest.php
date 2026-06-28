<?php

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;
use Laravel\Sanctum\Sanctum;

test('client package api returns active packages and summary', function () {
    $user = User::factory()->create();
    $activePackage = Package::factory()->create(['status' => 'active']);
    Package::factory()->create(['status' => 'inactive']);

    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $activePackage->id,
        'status' => SubscriptionStatus::Active,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/package')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.packages')
        ->assertJsonPath('data.summary.active_subscription_count', 1)
        ->assertJsonPath('data.active_subscription_package_ids.0', $activePackage->id);
});

test('client package api can create a package order', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 299000,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/package/orders', [
        'package_id' => $package->id,
    ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.package_id', $package->id)
        ->assertJsonPath('data.user_id', $user->id);

    $this->assertDatabaseHas('package_orders', [
        'user_id' => $user->id,
        'package_id' => $package->id,
    ]);
});

test('client package api can toggle auto renew on current subscription', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'status' => 'active',
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'status' => SubscriptionStatus::Expired,
        'auto_renew_enabled' => false,
        'auto_renew_attempted_at' => now(),
        'auto_renew_status' => 'failed',
        'auto_renew_message' => 'Số dư ví chính không đủ để thanh toán đơn hàng.',
    ]);

    Sanctum::actingAs($user);

    $this->patchJson("/api/package/subscriptions/{$subscription->id}/auto-renew", [
        'auto_renew_enabled' => true,
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.subscription.id', $subscription->id)
        ->assertJsonPath('data.subscription.auto_renew_enabled', true);

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $subscription->id,
        'auto_renew_enabled' => true,
        'auto_renew_attempted_at' => null,
        'auto_renew_status' => null,
        'auto_renew_message' => null,
    ]);
});
