<?php

use App\Features\Cron\Support\CronPackageCatalog;
use App\Models\CronAlertChannel;
use App\Models\CronJob;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

function autocronAccessSubscription(User $user, array $limitOverrides = []): UserSubscription
{
    $package = Package::factory()->create([
        'status' => PackageStatus::Active,
        'package_limits' => array_replace(CronPackageCatalog::defaults(), $limitOverrides),
    ]);

    return UserSubscription::factory()->create([
        'user_id' => $user->id,
        'package_id' => $package->id,
        'package_name' => $package->name,
        'package_price' => $package->price,
        'package_limits' => $package->package_limits,
        'status' => SubscriptionStatus::Active,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(30),
    ]);
}

function autocronAccessJob(User $user, array $attributes = []): CronJob
{
    return CronJob::query()->create(array_replace([
        'user_id' => $user->id,
        'name' => 'Access Job',
        'description' => 'Access test job',
        'url' => 'https://example.com/health',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
        'timezone' => 'Asia/Ho_Chi_Minh',
        'timeout_seconds' => 10,
        'connect_timeout_seconds' => 5,
        'retry_count' => 0,
        'retry_delay_seconds' => 30,
        'max_response_size_kb' => 20,
        'follow_redirects' => false,
        'verify_ssl' => true,
        'status' => 'active',
        'next_run_at' => now()->addMinutes(5),
    ], $attributes));
}

test('regular users cannot access admin autocron endpoints', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/admin-api/cron-jobs')->assertForbidden();
});

test('users cannot access logs of another users cron jobs', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    autocronAccessSubscription($owner);
    autocronAccessSubscription($intruder);

    $job = autocronAccessJob($owner);
    $job->logs()->create([
        'user_id' => $owner->id,
        'run_uuid' => (string) str()->uuid(),
        'attempt' => 1,
        'status' => 'success',
        'method' => 'GET',
        'url' => $job->url,
        'status_code' => 200,
        'duration_ms' => 50,
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    Sanctum::actingAs($intruder);

    $this->getJson("/api/client/cron-jobs/{$job->id}/logs")->assertNotFound();
});

test('cron job creation blocks methods not allowed by the package', function () {
    $user = User::factory()->create();
    autocronAccessSubscription($user, [
        'allowed_methods' => ['GET', 'POST'],
        'min_interval_seconds' => 300,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'Delete endpoint',
        'url' => 'https://example.com/delete',
        'method' => 'DELETE',
        'body_type' => 'none',
        'interval_seconds' => 300,
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);
});

test('cron job creation blocks custom headers and custom body when the package disallows them', function () {
    $user = User::factory()->create();
    autocronAccessSubscription($user, [
        'allow_custom_headers' => false,
        'allow_custom_body' => false,
        'allowed_methods' => ['GET', 'POST'],
        'min_interval_seconds' => 300,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'Header blocked',
        'url' => 'https://example.com/secure',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
        'headers' => [
            ['key' => 'Authorization', 'value' => 'Bearer secret'],
        ],
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'Body blocked',
        'url' => 'https://example.com/post',
        'method' => 'POST',
        'body_type' => 'json',
        'body' => ['source' => 'autocron'],
        'interval_seconds' => 300,
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);
});

test('users can create and test a discord alert channel when the package allows it', function () {
    Http::fake();

    $user = User::factory()->create();
    autocronAccessSubscription($user, [
        'allow_alerts' => true,
        'allow_discord_alert' => true,
        'max_alert_channels' => 2,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-alert-channels', [
        'name' => 'Prod Discord',
        'type' => 'discord',
        'target_url' => 'https://discord.example.test/webhook',
        'events' => ['on_fail', 'on_recovered'],
        'is_enabled' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('status', true);

    $channel = CronAlertChannel::query()->where('user_id', $user->id)->firstOrFail();
    autocronAccessJob($user, [
        'name' => 'Test Alert Job',
    ]);

    $this->postJson("/api/client/cron-alert-channels/{$channel->id}/test")
        ->assertOk()
        ->assertJsonPath('status', true);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://discord.example.test/webhook'
            && $request['product'] === 'AutoCron'
            && $request['event'] === 'on_fail'
            && $request['cron_job']['name'] === 'Test Alert Job';
    });
});

test('users cannot create a discord alert channel when the package does not allow discord alerts', function () {
    $user = User::factory()->create();
    autocronAccessSubscription($user, [
        'allow_alerts' => true,
        'allow_discord_alert' => false,
        'max_alert_channels' => 1,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-alert-channels', [
        'name' => 'Forbidden Discord',
        'type' => 'discord',
        'target_url' => 'https://discord.example.test/webhook',
        'events' => ['on_fail'],
        'is_enabled' => true,
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);
});
