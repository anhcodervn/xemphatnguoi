<?php

use App\Features\Cron\Support\CronPackageCatalog;
use App\Models\Package;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('admin can update autocron package limits', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $package = Package::factory()->create([
        'package_limits' => CronPackageCatalog::defaults(),
    ]);

    Sanctum::actingAs($admin);

    $payload = [
        'name' => $package->name,
        'slug' => $package->slug,
        'description' => 'Updated AutoCron package',
        'price' => 199000,
        'duration_days' => 30,
        'features' => ['Cron jobs', 'Alerts'],
        'status' => 'active',
        'package_limits' => array_replace(CronPackageCatalog::defaults(), [
            'max_cron_jobs' => 25,
            'min_interval_seconds' => 180,
            'monthly_run_quota' => 150000,
            'concurrent_runs_limit' => 3,
            'queue_name' => 'cron-default',
            'allow_alerts' => true,
            'allow_run_now' => true,
        ]),
    ];

    $this->patchJson("/api/admin-api/packages/{$package->id}", $payload)
        ->assertOk()
        ->assertJsonPath('status', true);

    $package->refresh();

    expect($package->package_limits['max_cron_jobs'])->toBe(25)
        ->and($package->account_limit)->toBe(25)
        ->and($package->request_limit)->toBe(150000)
        ->and($package->concurrent_limit)->toBe(3);
});
