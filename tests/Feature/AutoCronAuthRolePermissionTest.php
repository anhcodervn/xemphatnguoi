<?php

use App\Features\Cron\Support\CronPackageCatalog;
use App\Models\CronJob;
use App\Models\Notification;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

function autocronPermissionSubscription(User $user, array $limitOverrides = []): UserSubscription
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

function autocronPermissionJob(User $user, array $attributes = []): CronJob
{
    return CronJob::query()->create(array_replace([
        'user_id' => $user->id,
        'name' => 'Permission Job',
        'description' => 'Permission test job',
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

test('guests are redirected from protected spa routes and denied protected autocron apis', function () {
    $this->get('/cron-jobs')->assertRedirect('/login');
    $this->get('/admin')->assertRedirect('/login');

    $this->getJson('/api/client/cron-jobs')->assertUnauthorized();
    $this->getJson('/api/admin-api/cron-jobs')->assertUnauthorized();
    $this->getJson('/api/user')->assertUnauthorized();
});

test('authenticated client can load user context but cannot access admin autocron or admin management apis', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    autocronPermissionSubscription($user);

    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('role', 'user');

    $this->getJson('/api/admin-api/cron-jobs')
        ->assertForbidden()
        ->assertJsonPath('status', false);

    $this->postJson('/api/admin-api/packages', [
        'name' => 'Forbidden Package',
    ])->assertForbidden()
        ->assertJsonPath('status', false);

    $this->patchJson("/api/admin-api/users/{$user->id}/status", [
        'status' => 'blocked',
    ])->assertForbidden()
        ->assertJsonPath('status', false);
});

test('client cron job index and global logs only return the authenticated owners records', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    autocronPermissionSubscription($owner);
    autocronPermissionSubscription($otherUser);

    $ownerJob = autocronPermissionJob($owner, [
        'name' => 'Owner Job',
        'group_name' => 'Billing',
    ]);
    $otherJob = autocronPermissionJob($otherUser, [
        'name' => 'Other Job',
        'group_name' => 'Operations',
    ]);

    $ownerJob->logs()->create([
        'user_id' => $owner->id,
        'run_uuid' => (string) str()->uuid(),
        'attempt' => 1,
        'status' => 'success',
        'method' => 'GET',
        'url' => $ownerJob->url,
        'status_code' => 200,
        'duration_ms' => 50,
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $otherJob->logs()->create([
        'user_id' => $otherUser->id,
        'run_uuid' => (string) str()->uuid(),
        'attempt' => 1,
        'status' => 'failed',
        'method' => 'GET',
        'url' => $otherJob->url,
        'status_code' => 500,
        'duration_ms' => 60,
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    Sanctum::actingAs($owner);

    $this->getJson('/api/client/cron-jobs')
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $ownerJob->id)
        ->assertJsonPath('data.data.0.name', 'Owner Job')
        ->assertJsonPath('data.filters.groups.0', 'Billing');

    $this->getJson('/api/client/cron-jobs/logs')
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.url', $ownerJob->url);
});

dataset('foreign cron job endpoints', [
    'show' => ['GET', '/api/client/cron-jobs/%d'],
    'update' => ['PATCH', '/api/client/cron-jobs/%d'],
    'delete' => ['DELETE', '/api/client/cron-jobs/%d'],
    'run now' => ['POST', '/api/client/cron-jobs/%d/run-now'],
    'logs' => ['GET', '/api/client/cron-jobs/%d/logs'],
    'stats' => ['GET', '/api/client/cron-jobs/%d/stats'],
]);

test('client cannot access or mutate another users cron job resources', function (string $method, string $uriPattern) {
    Queue::fake();

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    autocronPermissionSubscription($owner, [
        'allow_run_now' => true,
    ]);
    autocronPermissionSubscription($intruder, [
        'allow_run_now' => true,
    ]);

    $job = autocronPermissionJob($owner, [
        'name' => 'Owners Only',
    ]);

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

    $uri = sprintf($uriPattern, $job->id);
    $payload = $method === 'PATCH'
        ? [
            'name' => 'Intruder Update',
            'description' => 'Trying to update another user job',
            'url' => $job->url,
            'method' => $job->method->value,
            'body_type' => $job->body_type->value,
            'interval_seconds' => $job->interval_seconds,
            'timezone' => $job->timezone,
        ]
        : [];
    $response = match ($method) {
        'GET' => $this->getJson($uri),
        'PATCH' => $this->patchJson($uri, $payload),
        'DELETE' => $this->deleteJson($uri),
        'POST' => $this->postJson($uri, $payload),
    };

    $response->assertNotFound();

    expect($job->fresh()->name)->toBe('Owners Only')
        ->and($job->fresh()->deleted_at)->toBeNull();

    Queue::assertNothingPushed();
})->with('foreign cron job endpoints');

test('admin can inspect autocron jobs across multiple users with owner context included', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $firstUser = User::factory()->create([
        'full_name' => 'User One',
    ]);
    $secondUser = User::factory()->create([
        'full_name' => 'User Two',
    ]);

    autocronPermissionSubscription($firstUser);
    autocronPermissionSubscription($secondUser);

    $firstJob = autocronPermissionJob($firstUser, [
        'name' => 'First User Job',
        'group_name' => 'Billing',
    ]);
    $secondJob = autocronPermissionJob($secondUser, [
        'name' => 'Second User Job',
        'group_name' => 'Operations',
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/admin-api/cron-jobs')
        ->assertOk()
        ->assertJsonCount(2, 'data.data')
        ->assertJsonPath('data.data.0.user.id', $secondUser->id)
        ->assertJsonPath('data.data.1.user.id', $firstUser->id);

    $this->getJson("/api/admin-api/cron-jobs/{$firstJob->id}")
        ->assertOk()
        ->assertJsonPath('data.cron_job.id', $firstJob->id)
        ->assertJsonPath('data.cron_job.user.id', $firstUser->id)
        ->assertJsonPath('data.cron_job.user.name', $firstUser->name);

    $this->getJson('/api/admin-api/cron-jobs?group_name=Operations')
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $secondJob->id);
});

test('client notification endpoints require auth and hide notifications owned by another user', function () {
    $this->getJson('/api/notifications')->assertUnauthorized();

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $ownersNotification = Notification::query()->create([
        'user_id' => $owner->id,
        'scope' => Notification::SCOPE_USER,
        'title' => 'Owner update',
        'content' => 'Only owner can read this.',
        'type' => 'info',
    ]);

    Sanctum::actingAs($intruder);

    $this->postJson("/api/notifications/{$ownersNotification->id}/read")
        ->assertNotFound();
});
