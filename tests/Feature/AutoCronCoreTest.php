<?php

use App\Features\Cron\Services\CronRunnerService;
use App\Features\Cron\Support\CronPackageCatalog;
use App\Jobs\RunHttpCronJob;
use App\Models\CronJob;
use App\Models\CronUsageCounter;
use App\Models\Package;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

function autocronActiveSubscription(User $user, array $limitOverrides = []): UserSubscription
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

function autocronJob(User $user, array $attributes = []): CronJob
{
    return CronJob::query()->create(array_replace([
        'user_id' => $user->id,
        'name' => 'Healthcheck',
        'description' => 'Basic job',
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

test('client cannot create an autocron job without an active subscription', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'No plan job',
        'url' => 'https://example.com/health',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);

    expect(CronJob::query()->count())->toBe(0);
});

test('client can store cron job group and filter cron jobs by group', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 300,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'Billing healthcheck',
        'group_name' => 'Billing',
        'url' => 'https://example.com/billing',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
    ])->assertCreated()
        ->assertJsonPath('data.cron_job.group_name', 'Billing');

    autocronJob($user, [
        'name' => 'Ops monitor',
        'group_name' => 'Operations',
    ]);

    $this->getJson('/api/client/cron-jobs?group_name=Billing')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.group_name', 'Billing')
        ->assertJsonPath('data.filters.groups.0', 'Billing')
        ->assertJsonPath('data.filters.groups.1', 'Operations');
});

test('client cannot create an autocron job below package interval or above package capacity', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'max_cron_jobs' => 1,
        'min_interval_seconds' => 300,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'Too fast job',
        'url' => 'https://example.com/health',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 60,
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);

    autocronJob($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'Second job',
        'url' => 'https://example.com/health',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 300,
    ])
        ->assertStatus(422)
        ->assertJsonPath('status', false);

    expect($user->cronJobs()->count())->toBe(1);
});

test('cron runner blocks localhost targets and records a blocked log', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user);

    $job = autocronJob($user, [
        'url' => 'http://127.0.0.1/internal',
        'next_run_at' => now()->subMinute(),
    ]);

    app(CronRunnerService::class)->run($job, (string) str()->uuid(), 1);

    $job->refresh();
    $log = $job->logs()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log?->status?->value)->toBe('blocked')
        ->and($job->total_runs)->toBe(1)
        ->and($job->total_failed)->toBe(1);
});

test('dispatch due command queues due autocron jobs and advances next run', function () {
    Queue::fake();

    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'queue_name' => 'cron-default',
    ]);

    $job = autocronJob($user, [
        'next_run_at' => now()->subMinute(),
        'interval_seconds' => 300,
    ]);

    $originalNextRun = $job->next_run_at;

    $this->artisan('cron:dispatch-due --limit=10')->assertExitCode(0);

    Queue::assertPushed(RunHttpCronJob::class, function (RunHttpCronJob $queuedJob) use ($job): bool {
        return $queuedJob->cronJobId === $job->id && $queuedJob->queue === 'cron-default';
    });

    expect($job->fresh()->next_run_at?->gt($originalNextRun))->toBeTrue();
});

test('dispatch due command advances overdue jobs from current time instead of replaying stale next run', function () {
    Queue::fake();

    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'queue_name' => 'cron-default',
    ]);

    $job = autocronJob($user, [
        'next_run_at' => now()->subMinutes(10),
        'interval_seconds' => 60,
    ]);

    $startedAt = now();

    $this->artisan('cron:dispatch-due --limit=10')->assertExitCode(0);

    $job->refresh();

    expect($job->next_run_at)->not->toBeNull()
        ->and($job->next_run_at?->gte($startedAt->copy()->addSeconds(55)))->toBeTrue();
});

test('dispatch due scheduler repeats every second to support one-second intervals', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($scheduledEvent) => str_contains((string) $scheduledEvent->command, 'cron:dispatch-due'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');

    $reflection = new ReflectionClass($event);
    $repeatSeconds = null;

    if ($reflection->hasProperty('repeatSeconds')) {
        $property = $reflection->getProperty('repeatSeconds');
        $property->setAccessible(true);
        $repeatSeconds = $property->getValue($event);
    }

    expect($repeatSeconds)->toBe(1);
});

test('dispatch due command advances cron expressions beyond the current slot so they queue only once', function () {
    Queue::fake();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-30 00:30:01', 'Asia/Ho_Chi_Minh'));

    try {
        $user = User::factory()->create();
        autocronActiveSubscription($user, [
            'queue_name' => 'cron-default',
        ]);

        $job = autocronJob($user, [
            'cron_expression' => '30 0 * * *',
            'interval_seconds' => null,
            'next_run_at' => now()->subSecond(),
        ]);

        $this->artisan('cron:dispatch-due --limit=10')->assertExitCode(0);
        $this->artisan('cron:dispatch-due --limit=10')->assertExitCode(0);

        Queue::assertPushed(RunHttpCronJob::class, 1);

        expect($job->fresh()->next_run_at)->not->toBeNull()
            ->and($job->fresh()->next_run_at?->format('Y-m-d H:i:s'))->toBe('2026-07-01 00:30:00');
    } finally {
        CarbonImmutable::setTestNow();
    }
});

test('client can create a one-second interval job when the package allows it', function () {
    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'min_interval_seconds' => 1,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/client/cron-jobs', [
        'name' => 'One second pulse',
        'url' => 'https://example.com/pulse',
        'method' => 'GET',
        'body_type' => 'none',
        'interval_seconds' => 1,
    ])
        ->assertCreated()
        ->assertJsonPath('data.cron_job.interval_seconds', 1);
});

test('run http cron job stores a success log and updates counters', function () {
    Http::fake([
        'https://example.com/*' => Http::response('ok', 200),
    ]);

    $user = User::factory()->create();
    autocronActiveSubscription($user);

    $job = autocronJob($user, [
        'url' => 'https://example.com/health',
        'expected_status_codes' => [200],
    ]);

    (new RunHttpCronJob($job->id, (string) str()->uuid()))->handle(app(CronRunnerService::class));

    $job->refresh();
    $log = $job->logs()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log?->status?->value)->toBe('success')
        ->and($job->total_runs)->toBe(1)
        ->and($job->total_success)->toBe(1)
        ->and($job->consecutive_failures)->toBe(0);
});

test('run http cron job stores a timeout log when the request cannot connect', function () {
    Http::fake([
        'https://example.com/*' => Http::failedConnection(),
    ]);

    $user = User::factory()->create();
    autocronActiveSubscription($user);

    $job = autocronJob($user, [
        'url' => 'https://example.com/timeout',
    ]);

    (new RunHttpCronJob($job->id, (string) str()->uuid()))->handle(app(CronRunnerService::class));

    $job->refresh();
    $log = $job->logs()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log?->status?->value)->toBe('timeout')
        ->and($job->total_runs)->toBe(1)
        ->and($job->total_failed)->toBe(1);
});

test('dispatch due command blocks jobs when the monthly run quota is exhausted', function () {
    Queue::fake();

    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'monthly_run_quota' => 1,
        'daily_run_quota' => null,
    ]);

    CronUsageCounter::query()->create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'month' => now()->format('Y-m'),
        'total_runs' => 1,
        'successful_runs' => 1,
        'failed_runs' => 0,
    ]);

    $job = autocronJob($user, [
        'next_run_at' => now()->subMinute(),
    ]);

    $this->artisan('cron:dispatch-due --limit=10')->assertExitCode(0);

    Queue::assertNothingPushed();

    $log = $job->fresh()->logs()->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log?->status?->value)->toBe('blocked');
});
