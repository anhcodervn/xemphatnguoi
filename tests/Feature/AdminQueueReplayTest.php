<?php

use App\Models\QueueLog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function createFailedQueueJob(string $uuid): void
{
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Test exception',
        'failed_at' => now(),
    ]);
}

test('admin can replay a failed queue log by its original job uuid', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $uuid = (string) Str::uuid();
    createFailedQueueJob($uuid);

    $log = QueueLog::query()->create([
        'job_uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'job_name' => 'Tests\\Fixtures\\ExampleJob',
        'status' => 'failed',
        'attempts' => 1,
        'failed_at' => now(),
    ]);

    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:retry', [
            'id' => [$uuid],
            '--no-interaction' => true,
        ])
        ->andReturnUsing(function () use ($uuid): int {
            DB::table('failed_jobs')->where('uuid', $uuid)->delete();

            return 0;
        });

    $this->actingAs($admin)
        ->postJson("/api/admin-api/queues/logs/{$log->id}/replay")
        ->assertOk()
        ->assertJsonPath('status', true);

    expect(DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();
});

test('admin can replay directly from the failed jobs table by uuid', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $uuid = (string) Str::uuid();
    createFailedQueueJob($uuid);

    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:retry', [
            'id' => [$uuid],
            '--no-interaction' => true,
        ])
        ->andReturnUsing(function () use ($uuid): int {
            DB::table('failed_jobs')->where('uuid', $uuid)->delete();

            return 0;
        });

    $this->actingAs($admin)
        ->postJson("/api/admin-api/queues/failed-jobs/{$uuid}/retry")
        ->assertOk()
        ->assertJsonPath('status', true);
});

test('queue log list only marks failed logs with an existing failed job as replayable', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $uuid = (string) Str::uuid();
    createFailedQueueJob($uuid);

    $replayableLog = QueueLog::query()->create([
        'job_uuid' => $uuid,
        'queue' => 'default',
        'status' => 'failed',
    ]);

    QueueLog::query()->create([
        'job_uuid' => (string) Str::uuid(),
        'queue' => 'default',
        'status' => 'failed',
    ]);

    $this->actingAs($admin)
        ->getJson('/api/admin-api/queues/logs')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $replayableLog->id,
            'can_replay' => true,
        ])
        ->assertJsonFragment(['can_replay' => false]);
});

test('replay returns not found when the original failed job no longer exists', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $log = QueueLog::query()->create([
        'job_uuid' => (string) Str::uuid(),
        'queue' => 'default',
        'status' => 'failed',
    ]);

    $this->actingAs($admin)
        ->postJson("/api/admin-api/queues/logs/{$log->id}/replay")
        ->assertNotFound()
        ->assertJsonPath('status', false);
});

test('non admin cannot replay queue jobs', function () {
    $user = User::factory()->create(['role' => 'user']);
    $log = QueueLog::query()->create([
        'job_uuid' => (string) Str::uuid(),
        'queue' => 'default',
        'status' => 'failed',
    ]);

    $this->actingAs($user)
        ->postJson("/api/admin-api/queues/logs/{$log->id}/replay")
        ->assertForbidden();
});
