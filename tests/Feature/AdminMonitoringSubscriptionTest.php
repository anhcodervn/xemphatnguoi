<?php

use App\Models\MonitoringSubscription;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows only admins to list rented monitoring packages', function (): void {
    $this->getJson('/api/admin-api/monitoring/subscriptions')->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/admin-api/monitoring/subscriptions')->assertForbidden();

    $customer = User::factory()->create([
        'username' => 'khachhanggoi',
        'full_name' => 'Khách hàng gói thuê',
        'email' => 'subscription@example.com',
    ]);
    $subscription = MonitoringSubscription::factory()->for($customer)->create([
        'plan_name' => 'Gói doanh nghiệp',
        'vehicle_limit' => 30,
        'total_price' => 600000,
        'auto_renew' => true,
        'started_at' => now()->subDay(),
        'expires_at' => now()->addDays(29),
    ]);

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    $this->getJson('/api/admin-api/monitoring/subscriptions?per_page=10')
        ->assertOk()
        ->assertJsonPath('data.subscriptions.0.id', $subscription->id)
        ->assertJsonPath('data.subscriptions.0.user.username', 'khachhanggoi')
        ->assertJsonPath('data.subscriptions.0.plan_name', 'Gói doanh nghiệp')
        ->assertJsonPath('data.subscriptions.0.vehicle_limit', 30)
        ->assertJsonPath('data.subscriptions.0.total_price', '600000.00')
        ->assertJsonPath('data.subscriptions.0.is_active', true)
        ->assertJsonPath('data.subscriptions.0.auto_renew', true)
        ->assertJsonPath('data.meta.total', 1);
});

it('filters rented packages by customer, lifecycle status, and automatic renewal', function (): void {
    $matchedCustomer = User::factory()->create([
        'username' => 'nguyenan',
        'full_name' => 'Nguyễn Văn An',
        'email' => 'an@example.com',
    ]);
    $otherCustomer = User::factory()->create([
        'username' => 'tranbinh',
        'full_name' => 'Trần Bình',
        'email' => 'binh@example.com',
    ]);

    $expired = MonitoringSubscription::factory()->for($matchedCustomer)->create([
        'plan_name' => 'Gói tìm kiếm',
        'status' => MonitoringSubscription::STATUS_ACTIVE,
        'auto_renew' => false,
        'started_at' => now()->subDays(31),
        'expires_at' => now()->subDay(),
    ]);
    MonitoringSubscription::factory()->for($otherCustomer)->create([
        'plan_name' => 'Gói còn hạn',
        'status' => MonitoringSubscription::STATUS_ACTIVE,
        'auto_renew' => true,
        'started_at' => now()->subDay(),
        'expires_at' => now()->addDays(29),
    ]);

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/monitoring/subscriptions?search=Nguy%E1%BB%85n&status=expired&auto_renew=0')
        ->assertOk()
        ->assertJsonCount(1, 'data.subscriptions')
        ->assertJsonPath('data.subscriptions.0.id', $expired->id)
        ->assertJsonPath('data.subscriptions.0.is_active', false);

    $this->getJson('/api/admin-api/monitoring/subscriptions?status=unknown')
        ->assertUnprocessable();
});

it('shows only the latest package for each customer after an upgrade', function (): void {
    $customer = User::factory()->create([
        'username' => 'upgradecustomer',
        'email' => 'upgrade@example.com',
    ]);
    $previousSubscription = MonitoringSubscription::factory()->for($customer)->create([
        'plan_name' => 'Gói 5 xe',
        'vehicle_limit' => 5,
        'total_price' => 100000,
        'status' => MonitoringSubscription::STATUS_UPGRADED,
        'started_at' => now()->subDays(10),
        'expires_at' => now()->addDays(20),
    ]);
    $upgradedSubscription = MonitoringSubscription::factory()->for($customer)->create([
        'plan_name' => 'Gói 20 xe',
        'vehicle_limit' => 20,
        'total_price' => 300000,
        'status' => MonitoringSubscription::STATUS_ACTIVE,
        'upgraded_from_subscription_id' => $previousSubscription->id,
        'started_at' => now()->subMinute(),
        'expires_at' => now()->addDays(30),
    ]);

    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $this->getJson('/api/admin-api/monitoring/subscriptions?search=upgradecustomer')
        ->assertOk()
        ->assertJsonCount(1, 'data.subscriptions')
        ->assertJsonPath('data.subscriptions.0.id', $upgradedSubscription->id)
        ->assertJsonPath('data.subscriptions.0.plan_name', 'Gói 20 xe')
        ->assertJsonPath('data.subscriptions.0.vehicle_limit', 20)
        ->assertJsonPath('data.subscriptions.0.total_price', '300000.00')
        ->assertJsonPath('data.meta.total', 1);

    $this->getJson('/api/admin-api/monitoring/subscriptions?search=G%C3%B3i%205%20xe')
        ->assertOk()
        ->assertJsonCount(0, 'data.subscriptions')
        ->assertJsonPath('data.meta.total', 0);
});
