<?php

use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\SubscriptionStatus;
use Laravel\Sanctum\Sanctum;

test('client home is accessible', function () {
    $response = $this->get('/');

    $response->assertOk();
});

test('authenticated users can retrieve their profile from api user endpoint', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user');

    $response
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('name', $user->name)
        ->assertJsonPath('wallet.type', 'main')
        ->assertJsonPath('wallet.balance', '0.00')
        ->assertJsonPath('user_subscriptions', null);
});

test('authenticated users receive their active subscription in api user response', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 299000,
        'duration_days' => 30,
        'account_limit' => 5,
        'request_limit' => 10000,
        'request_per_minute' => 120,
        'concurrent_limit' => 3,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => $package->account_limit,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(29),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('user_subscriptions.id', $subscription->id)
        ->assertJsonPath('user_subscriptions.package_id', $package->id)
        ->assertJsonPath('user_subscriptions.package_name', $package->name)
        ->assertJsonPath('user_subscriptions.package_price', '299000.00')
        ->assertJsonPath('user_subscriptions.status', SubscriptionStatus::Active->value)
        ->assertJsonPath('user_subscriptions.package.id', $package->id)
        ->assertJsonPath('user_subscriptions.package.name', $package->name);
});

test('authenticated users receive their latest expired subscription in api user response when no active subscription exists', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 199000,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'status' => SubscriptionStatus::Expired,
        'starts_at' => now()->subDays(31),
        'expires_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertSuccessful()
        ->assertJsonPath('user_subscriptions.id', $subscription->id)
        ->assertJsonPath('user_subscriptions.status', SubscriptionStatus::Expired->value)
        ->assertJsonPath('user_subscriptions.package.id', $package->id);
});

test('guests cannot retrieve profile from api user endpoint', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});
