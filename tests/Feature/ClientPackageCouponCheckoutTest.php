<?php

use App\Models\Coupon;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PaymentStatus;

test('user can quote package with coupon code', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'price' => 200000,
    ]);

    Coupon::factory()->create([
        'code' => 'WELCOME20',
        'type' => Coupon::TYPE_PERCENT,
        'value' => 20,
        'min_order_amount' => 100000,
        'max_discount_amount' => 50000,
        'is_active' => true,
        'starts_at' => now()->subHour(),
        'expired_at' => now()->addDay(),
        'applicable_package_ids' => [$package->id],
        'max_usage' => 100,
        'max_usage_per_user' => 2,
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/package/quote', [
            'package_id' => $package->id,
            'coupon_code' => 'welcome20',
        ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.discount_amount', 40000)
        ->assertJsonPath('data.coupon.code', 'WELCOME20');
});

test('user cannot use first order coupon when already has paid package order', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'price' => 150000,
    ]);

    PackageOrder::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'payment_status' => PaymentStatus::Paid,
        'status' => PackageOrderStatus::Completed,
        'paid_at' => now()->subDay(),
    ]);

    Coupon::factory()->create([
        'code' => 'FIRSTONLY',
        'first_order_only' => true,
        'is_active' => true,
        'starts_at' => now()->subHour(),
        'expired_at' => now()->addDay(),
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/package/quote', [
            'package_id' => $package->id,
            'coupon_code' => 'FIRSTONLY',
        ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);
});

test('successful wallet payment with coupon increments usage and writes coupon log', function () {
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => 500000,
    ]);

    $package = Package::factory()->create([
        'price' => 300000,
    ]);

    $coupon = Coupon::factory()->create([
        'code' => 'SAVE50',
        'type' => Coupon::TYPE_FIXED,
        'value' => 50000,
        'min_order_amount' => 0,
        'is_active' => true,
        'starts_at' => now()->subHour(),
        'expired_at' => now()->addDay(),
        'max_usage' => 10,
        'max_usage_per_user' => 3,
    ]);

    $orderResponse = $this->actingAs($user, 'sanctum')
        ->postJson('/api/package/orders', [
            'package_id' => $package->id,
            'coupon_code' => 'SAVE50',
            'payment_method' => 'wallet',
        ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.discount_amount', '50000.00');

    $orderId = $orderResponse->json('data.id');

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/package/orders/{$orderId}/pay", [
            'payment_method' => 'wallet',
        ])
        ->assertOk()
        ->assertJsonPath('status', true);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(1);

    $this->assertDatabaseHas('coupon_logs', [
        'coupon_id' => $coupon->id,
        'user_id' => $user->id,
        'package_order_id' => $orderId,
        'action' => 'applied_package_order',
        'status' => 'success',
    ]);
});
