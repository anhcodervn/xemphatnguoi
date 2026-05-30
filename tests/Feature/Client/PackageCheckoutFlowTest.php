<?php

use App\Models\ExtraAccountOrder;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\ExtraAccountOrderStatus;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('client package quote returns base price for new purchase', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/package/quote', [
        'package_id' => $package->id,
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.quote_type', 'new_purchase')
        ->assertJsonPath('data.price', 300000)
        ->assertJsonPath('data.credit_amount', 0)
        ->assertJsonPath('data.final_amount', 300000);
});

test('client package quote returns prorated credit for active subscription upgrade', function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    $user = User::factory()->create();
    $currentPackage = Package::factory()->create([
        'status' => 'active',
        'price' => 90000,
    ]);
    $targetPackage = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $currentPackage->id,
        'package_name' => $currentPackage->name,
        'package_price' => 90000,
        'starts_at' => Carbon::parse('2026-05-15 12:00:00'),
        'expires_at' => Carbon::parse('2026-06-14 12:00:00'),
        'status' => SubscriptionStatus::Active,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/package/quote', [
        'package_id' => $targetPackage->id,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.quote_type', 'upgrade')
        ->assertJsonPath('data.source_subscription.id', $subscription->id)
        ->assertJsonPath('data.credit_amount', 60000)
        ->assertJsonPath('data.final_amount', 240000);
});

test('client package create order stores quote snapshot for upgrade', function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    $user = User::factory()->create();
    $currentPackage = Package::factory()->create([
        'status' => 'active',
        'price' => 90000,
    ]);
    $targetPackage = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $currentPackage->id,
        'package_name' => $currentPackage->name,
        'package_price' => 90000,
        'starts_at' => Carbon::parse('2026-05-15 12:00:00'),
        'expires_at' => Carbon::parse('2026-06-14 12:00:00'),
        'status' => SubscriptionStatus::Active,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/package/orders', [
        'package_id' => $targetPackage->id,
    ])->assertCreated();

    $orderId = $response->json('data.id');

    $this->assertDatabaseHas('package_orders', [
        'id' => $orderId,
        'user_id' => $user->id,
        'package_id' => $targetPackage->id,
        'source_subscription_id' => $subscription->id,
        'credit_amount' => 60000,
        'final_amount' => 240000,
    ]);
});

test('client package pay with wallet debits balance and creates new subscription', function () {
    Carbon::setTestNow('2026-05-25 12:00:00');

    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => 500000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $currentPackage = Package::factory()->create([
        'status' => 'active',
        'price' => 90000,
    ]);
    $targetPackage = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
        'duration_days' => 30,
    ]);

    $sourceSubscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $currentPackage->id,
        'package_name' => $currentPackage->name,
        'package_price' => 90000,
        'starts_at' => Carbon::parse('2026-05-15 12:00:00'),
        'expires_at' => Carbon::parse('2026-06-14 12:00:00'),
        'status' => SubscriptionStatus::Active,
    ]);

    $order = PackageOrder::factory()->create([
        'user_id' => $user->id,
        'package_id' => $targetPackage->id,
        'source_subscription_id' => $sourceSubscription->id,
        'price' => 300000,
        'discount_amount' => 0,
        'credit_amount' => 60000,
        'final_amount' => 240000,
        'expires_at' => now()->addMinutes(15),
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/package/orders/{$order->id}/pay", [
        'payment_method' => 'wallet',
    ])->assertSuccessful();

    $subscriptionId = $response->json('data.subscription.id');

    $this->assertDatabaseHas('package_orders', [
        'id' => $order->id,
        'payment_status' => PaymentStatus::Paid->value,
        'status' => 'completed',
        'payment_method' => 'wallet',
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'reference_type' => PackageOrder::class,
        'reference_id' => $order->id,
        'type' => 'debit',
        'amount' => 240000,
        'status' => 'success',
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user->wallet->id,
        'balance' => 260000,
        'total_spent' => 240000,
    ]);

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $subscriptionId,
        'user_id' => $user->id,
        'package_id' => $targetPackage->id,
        'order_id' => $order->id,
        'status' => SubscriptionStatus::Active->value,
    ]);

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $sourceSubscription->id,
        'status' => SubscriptionStatus::Cancelled->value,
    ]);
});

test('client package pay with wallet fails when balance is insufficient', function () {
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => 10000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
    ]);

    $order = PackageOrder::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'price' => 300000,
        'discount_amount' => 0,
        'credit_amount' => 0,
        'final_amount' => 300000,
        'expires_at' => now()->addMinutes(15),
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/package/orders/{$order->id}/pay", [
        'payment_method' => 'wallet',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Số dư ví chính không đủ để thanh toán đơn hàng.');

    $this->assertDatabaseHas('package_orders', [
        'id' => $order->id,
        'payment_status' => PaymentStatus::Pending->value,
        'status' => 'pending',
    ]);
});

test('client extra account order pay with wallet debits balance and stores wallet transaction', function () {
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => 500000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $package = Package::factory()->create([
        'status' => 'active',
        'can_buy_extra_account' => true,
        'extra_account_price' => 75000,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => 2,
        'extra_account_limit' => 0,
        'used_account' => 1,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(10),
    ]);

    Sanctum::actingAs($user);

    $storeResponse = $this->postJson('/api/client/subscriptions/extra-account-orders', [
        'user_subscription_id' => $subscription->id,
        'quantity' => 2,
    ])->assertCreated();

    $extraAccountOrderId = $storeResponse->json('data.id');

    $this->postJson("/api/client/subscriptions/extra-account-orders/{$extraAccountOrderId}/pay")
        ->assertSuccessful()
        ->assertJsonPath('status', true);

    $this->assertDatabaseHas('extra_account_orders', [
        'id' => $extraAccountOrderId,
        'status' => ExtraAccountOrderStatus::Paid->value,
        'price' => 150000,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'reference_type' => ExtraAccountOrder::class,
        'reference_id' => $extraAccountOrderId,
        'type' => 'debit',
        'amount' => 150000,
        'status' => 'success',
    ]);

    $this->assertDatabaseHas('wallets', [
        'id' => $user->wallet->id,
        'balance' => 350000,
        'total_spent' => 150000,
    ]);

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $subscription->id,
        'extra_account_limit' => 2,
    ]);
});

test('client extra account order pay with wallet fails when balance is insufficient', function () {
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => 10000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $package = Package::factory()->create([
        'status' => 'active',
        'can_buy_extra_account' => true,
        'extra_account_price' => 50000,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'base_account_limit' => 2,
        'extra_account_limit' => 0,
        'used_account' => 1,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDays(10),
    ]);

    $extraAccountOrder = ExtraAccountOrder::factory()->create([
        'user_subscription_id' => $subscription->id,
        'quantity' => 1,
        'price' => 50000,
        'status' => ExtraAccountOrderStatus::Pending,
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/client/subscriptions/extra-account-orders/{$extraAccountOrder->id}/pay")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Số dư ví chính không đủ để thanh toán đơn hàng.');

    $this->assertDatabaseHas('extra_account_orders', [
        'id' => $extraAccountOrder->id,
        'status' => ExtraAccountOrderStatus::Pending->value,
    ]);

    $this->assertDatabaseMissing('wallet_transactions', [
        'reference_type' => ExtraAccountOrder::class,
        'reference_id' => $extraAccountOrder->id,
    ]);

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $subscription->id,
        'extra_account_limit' => 0,
    ]);
});
