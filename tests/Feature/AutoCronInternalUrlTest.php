<?php

use App\Jobs\RunHttpCronJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('internal cron endpoints reject requests without a valid key', function () {
    config()->set('services.internal_cron.key', 'secret-cron-key');

    $this->postJson('/api/internal/cron/dispatch-due')
        ->assertForbidden()
        ->assertJsonPath('status', false);
});

test('internal dispatch due endpoint queues due jobs with a valid key', function () {
    Queue::fake();

    config()->set('services.internal_cron.key', 'secret-cron-key');

    $user = User::factory()->create();
    autocronActiveSubscription($user, [
        'queue_name' => 'cron-default',
    ]);

    $job = autocronJob($user, [
        'next_run_at' => now()->subMinute(),
        'interval_seconds' => 60,
    ]);

    $this->postJson('/api/internal/cron/dispatch-due', [
        'limit' => 10,
    ], [
        'X-CRON-KEY' => 'secret-cron-key',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.command', 'cron:dispatch-due')
        ->assertJsonPath('data.exit_code', 0);

    Queue::assertPushed(RunHttpCronJob::class, function (RunHttpCronJob $queuedJob) use ($job): bool {
        return $queuedJob->cronJobId === $job->id && $queuedJob->queue === 'cron-default';
    });
});

test('internal reset usage quota endpoint forwards retention days to the command', function () {
    config()->set('services.internal_cron.key', 'secret-cron-key');

    $this->postJson('/api/internal/cron/reset-usage-quota', [
        'retention_days' => 30,
    ], [
        'X-CRON-KEY' => 'secret-cron-key',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.command', 'cron:reset-usage-quota')
        ->assertJsonPath('data.parameters.--retention-days', 30)
        ->assertJsonPath('data.exit_code', 0);
});
