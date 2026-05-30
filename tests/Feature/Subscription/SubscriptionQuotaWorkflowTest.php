<?php

use App\Exceptions\ApiException;
use App\Features\Client\Subscription\Services\AccountProvisioningService;
use App\Features\Client\Subscription\Services\ExtraAccountOrderService;
use App\Features\Client\Subscription\Services\PackageOrderService;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;
use Laravel\Sanctum\Sanctum;

test('paid package order creates subscription snapshot and initializes quota', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'name' => 'Starter',
        'price' => 199,
        'duration_days' => 30,
        'account_limit' => 3,
    ]);

    $order = PackageOrder::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'price' => 199,
        'discount_amount' => 0,
        'final_amount' => 199,
    ]);

    $subscription = app(PackageOrderService::class)->markAsPaid($order, 'bank_transfer');

    expect($subscription->user_id)->toBe($user->id)
        ->and($subscription->package_id)->toBe($package->id)
        ->and($subscription->order_id)->toBe($order->id)
        ->and($subscription->package_name)->toBe('Starter')
        ->and((string) $subscription->package_price)->toBe('199.00')
        ->and($subscription->base_account_limit)->toBe(3)
        ->and($subscription->extra_account_limit)->toBe(0)
        ->and($subscription->used_account)->toBe(0)
        ->and($subscription->status)->toBe(SubscriptionStatus::Active);

    expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(PackageOrderStatus::Completed);
});

test('paid extra account order increments subscription extra quota', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'account_limit' => 2,
        'can_buy_extra_account' => true,
        'extra_account_price' => 15,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'base_account_limit' => 2,
        'extra_account_limit' => 0,
        'used_account' => 0,
    ]);

    $extraOrder = app(ExtraAccountOrderService::class)->createOrder($subscription->fresh('package'), 2);

    app(ExtraAccountOrderService::class)->markAsPaid($extraOrder);

    expect($subscription->fresh()->extra_account_limit)->toBe(2)
        ->and($extraOrder->fresh()->status->value)->toBe('paid');
});

test('account provisioning rejects quota overflow', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'account_limit' => 1,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'base_account_limit' => 1,
        'extra_account_limit' => 0,
        'used_account' => 1,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDay(),
    ]);

    expect(fn () => app(AccountProvisioningService::class)->createAccount($user, $subscription))
        ->toThrow(ApiException::class);
});

test('account provisioning consumes available quota', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'account_limit' => 2,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'base_account_limit' => 2,
        'extra_account_limit' => 1,
        'used_account' => 0,
        'status' => SubscriptionStatus::Active,
        'expires_at' => now()->addDay(),
    ]);

    $account = app(AccountProvisioningService::class)->createAccount($user, $subscription);

    expect($account->user_id)->toBe($user->id)
        ->and($account->subscription_id)->toBe($subscription->id)
        ->and($subscription->fresh()->used_account)->toBe(1);
});

test('subscription package page endpoint returns packages and subscription summary', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'account_limit' => 4,
    ]);

    UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'base_account_limit' => 4,
        'extra_account_limit' => 2,
        'used_account' => 1,
        'status' => SubscriptionStatus::Active,
        'package_name' => $package->name,
        'package_price' => $package->price,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/client/subscriptions')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.summary.total_quota', 6)
        ->assertJsonPath('data.summary.used_quota', 1)
        ->assertJsonPath('data.summary.available_quota', 5)
        ->assertJsonCount(1, 'data.subscriptions');
});
