<?php

use App\Models\Package;
use App\Models\User;

test('authenticated user can list packages with summary', function () {
    Package::factory()->count(3)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/admin-api/packages')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(3, 'data.packages.data')
        ->assertJsonPath('data.summary.total', 3);
});

test('authenticated user can create a package', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/admin-api/packages', [
        'name' => 'Gói Pro 30 ngày',
        'slug' => 'goi-pro-30-ngay',
        'description' => 'Mô tả gói',
        'price' => 299000,
        'duration_days' => 30,
        'account_limit' => 5,
        'can_buy_extra_account' => false,
        'extra_account_price' => 99999,
        'request_limit' => 10000,
        'request_per_minute' => 60,
        'concurrent_limit' => 1,
        'features' => ['API nhanh', 'Webhook'],
        'status' => 'active',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.slug', 'goi-pro-30-ngay')
        ->assertJsonPath('data.extra_account_price', '0.00');

    $this->assertDatabaseHas('packages', [
        'slug' => 'goi-pro-30-ngay',
        'status' => 'active',
    ]);
});

test('package slug must be unique', function () {
    $user = User::factory()->create();
    Package::factory()->create([
        'slug' => 'goi-pro-30-ngay',
    ]);

    $this->actingAs($user)
        ->postJson('/admin-api/packages', [
            'name' => 'Gói khác',
            'slug' => 'goi-pro-30-ngay',
            'description' => 'Mô tả',
            'price' => 199000,
            'duration_days' => 30,
            'account_limit' => 3,
            'can_buy_extra_account' => true,
            'extra_account_price' => 1000,
            'request_limit' => 1000,
            'request_per_minute' => 60,
            'concurrent_limit' => 1,
            'features' => ['A'],
            'status' => 'active',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug']);
});

test('authenticated user can update a package', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create([
        'slug' => 'goi-cu',
        'can_buy_extra_account' => true,
        'extra_account_price' => 10,
    ]);

    $this->actingAs($user)
        ->patchJson("/admin-api/packages/{$package->id}", [
            'name' => 'Gói mới',
            'slug' => 'goi-moi',
            'description' => 'Mô tả mới',
            'price' => 399000,
            'duration_days' => 60,
            'account_limit' => 10,
            'can_buy_extra_account' => false,
            'extra_account_price' => 15000,
            'request_limit' => 20000,
            'request_per_minute' => 120,
            'concurrent_limit' => 3,
            'features' => ['A', 'B'],
            'status' => 'inactive',
        ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.slug', 'goi-moi')
        ->assertJsonPath('data.status', 'inactive')
        ->assertJsonPath('data.extra_account_price', '0.00');

    $this->assertDatabaseHas('packages', [
        'id' => $package->id,
        'slug' => 'goi-moi',
        'status' => 'inactive',
    ]);
});

test('authenticated user can soft delete a package', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/admin-api/packages/{$package->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertSoftDeleted('packages', [
        'id' => $package->id,
    ]);
});
