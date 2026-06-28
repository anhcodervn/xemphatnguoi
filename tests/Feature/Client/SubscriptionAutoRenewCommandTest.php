<?php

use App\Jobs\SendSystemMailJob;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PaymentStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

afterEach(function (): void {
    Carbon::setTestNow();
});

test('auto renew command renews eligible subscription and queues success mail', function () {
    Carbon::setTestNow('2026-06-28 12:00:00');
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'renew-success@example.com',
    ]);
    $user->wallet()->update([
        'balance' => 500000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
        'duration_days' => 30,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'auto_renew_enabled' => true,
        'starts_at' => Carbon::parse('2026-05-29 12:00:00'),
        'expires_at' => Carbon::parse('2026-06-28 11:59:00'),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->artisan('subscriptions:auto-renew-due --limit=10')
        ->expectsOutputToContain('Processed 1 subscriptions. Renewed: 1')
        ->assertSuccessful();

    $newSubscription = UserSubscription::query()
        ->where('user_id', $user->id)
        ->where('id', '!=', $subscription->id)
        ->latest('id')
        ->first();

    expect($newSubscription)->not->toBeNull();

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Expired->value,
        'auto_renew_enabled' => true,
        'auto_renew_status' => 'success',
    ]);

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $newSubscription?->id,
        'package_id' => $package->id,
        'auto_renew_enabled' => true,
        'status' => SubscriptionStatus::Active->value,
    ]);

    $this->assertDatabaseHas('package_orders', [
        'user_id' => $user->id,
        'package_id' => $package->id,
        'source_subscription_id' => $subscription->id,
        'auto_renew_enabled' => true,
        'payment_status' => PaymentStatus::Paid->value,
    ]);

    Queue::assertPushed(SendSystemMailJob::class, function (SendSystemMailJob $job) use ($user): bool {
        return $job->to === $user->email
            && $job->title === 'Tự gia hạn gói dịch vụ thành công';
    });
});

test('auto renew command marks failure and queues failure mail when wallet is insufficient', function () {
    Carbon::setTestNow('2026-06-28 12:00:00');
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'renew-failed@example.com',
    ]);
    $user->wallet()->update([
        'balance' => 10000,
        'hold_balance' => 0,
        'total_spent' => 0,
    ]);

    $package = Package::factory()->create([
        'status' => 'active',
        'price' => 300000,
        'duration_days' => 30,
    ]);

    $subscription = UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'auto_renew_enabled' => true,
        'starts_at' => Carbon::parse('2026-05-29 12:00:00'),
        'expires_at' => Carbon::parse('2026-06-28 11:59:00'),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->artisan('subscriptions:auto-renew-due --limit=10')
        ->expectsOutputToContain('Processed 1 subscriptions. Renewed: 0, failed: 1')
        ->assertSuccessful();

    $this->assertDatabaseHas('user_subscriptions', [
        'id' => $subscription->id,
        'status' => SubscriptionStatus::Expired->value,
        'auto_renew_enabled' => true,
        'auto_renew_status' => 'failed',
    ]);

    $failedOrder = PackageOrder::query()
        ->where('user_id', $user->id)
        ->where('source_subscription_id', $subscription->id)
        ->latest('id')
        ->first();

    expect($failedOrder)->not->toBeNull();

    $this->assertDatabaseHas('package_orders', [
        'id' => $failedOrder?->id,
        'payment_status' => PaymentStatus::Failed->value,
        'status' => 'cancelled',
    ]);

    Queue::assertPushed(SendSystemMailJob::class, function (SendSystemMailJob $job) use ($user): bool {
        return $job->to === $user->email
            && $job->title === 'Tự gia hạn gói dịch vụ thất bại';
    });
});
