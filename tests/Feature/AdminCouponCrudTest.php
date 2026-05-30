<?php

use App\Models\Coupon;
use App\Models\Package;
use App\Models\User;

function adminUser(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

test('admin can list coupons with summary data', function () {
    Coupon::factory()->count(3)->create();
    $admin = adminUser();

    $this->actingAs($admin)
        ->getJson('/admin-api/coupons')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(3, 'data.coupons.data')
        ->assertJsonPath('data.summary.total', 3);
});

test('admin can create a coupon with usage requirements and log entry', function () {
    $admin = adminUser();
    $package = Package::factory()->create();

    $response = $this->actingAs($admin)->postJson('/admin-api/coupons', [
        'code' => 'summer-2026',
        'name' => 'Khuyến mãi mùa hè',
        'description' => 'Giảm giá cho khách hàng mùa hè',
        'type' => 'percent',
        'value' => 15,
        'min_order_amount' => 100000,
        'max_discount_amount' => 50000,
        'max_usage' => 100,
        'max_usage_per_user' => 2,
        'starts_at' => now()->subHour()->toDateTimeString(),
        'expired_at' => now()->addDays(10)->toDateTimeString(),
        'first_order_only' => true,
        'is_active' => true,
        'applicable_package_ids' => [$package->id],
        'requirements' => [
            'note' => 'Áp dụng cho user đủ điều kiện',
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'SUMMER-2026')
        ->assertJsonPath('data.first_order_only', true)
        ->assertJsonPath('data.applicable_package_ids.0', $package->id);

    $coupon = Coupon::query()->where('code', 'SUMMER-2026')->firstOrFail();

    expect($coupon->requirements)->toMatchArray([
        'note' => 'Áp dụng cho user đủ điều kiện',
    ]);

    $this->assertDatabaseHas('coupon_logs', [
        'coupon_id' => $coupon->id,
        'admin_id' => $admin->id,
        'action' => 'created',
        'status' => 'success',
    ]);
});

test('admin can update a coupon and keep code normalized', function () {
    $admin = adminUser();
    $coupon = Coupon::factory()->create([
        'code' => 'WELCOME50',
        'name' => 'Coupon cũ',
        'type' => Coupon::TYPE_FIXED,
        'value' => 50000,
    ]);

    $this->actingAs($admin)
        ->patchJson("/admin-api/coupons/{$coupon->id}", [
            'code' => 'vip-member',
            'name' => 'Coupon VIP',
            'description' => 'Ưu đãi cho nhóm VIP',
            'type' => 'percent',
            'value' => 20,
            'min_order_amount' => 200000,
            'max_discount_amount' => 80000,
            'max_usage' => 80,
            'max_usage_per_user' => 1,
            'starts_at' => now()->toDateTimeString(),
            'expired_at' => now()->addDays(5)->toDateTimeString(),
            'first_order_only' => false,
            'is_active' => false,
            'applicable_package_ids' => [],
            'requirements' => [
                'note' => 'Cập nhật điều kiện',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'VIP-MEMBER')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'code' => 'VIP-MEMBER',
        'name' => 'Coupon VIP',
        'is_active' => 0,
    ]);

    $this->assertDatabaseHas('coupon_logs', [
        'coupon_id' => $coupon->id,
        'admin_id' => $admin->id,
        'action' => 'updated',
        'status' => 'success',
    ]);
});

test('admin can soft delete a coupon and record delete log', function () {
    $admin = adminUser();
    $coupon = Coupon::factory()->create();

    $this->actingAs($admin)
        ->deleteJson("/admin-api/coupons/{$coupon->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertSoftDeleted('coupons', [
        'id' => $coupon->id,
    ]);

    $this->assertDatabaseHas('coupon_logs', [
        'coupon_id' => $coupon->id,
        'admin_id' => $admin->id,
        'action' => 'deleted',
        'status' => 'info',
    ]);
});

test('admin can view coupon logs', function () {
    $admin = adminUser();
    $coupon = Coupon::factory()->create([
        'code' => 'LOG-COUPON',
        'name' => 'Coupon có log',
    ]);

    $coupon->logs()->create([
        'admin_id' => $admin->id,
        'action' => 'updated',
        'status' => 'success',
        'note' => 'Đã cập nhật coupon',
        'payload' => ['source' => 'test'],
    ]);

    $this->actingAs($admin)
        ->getJson('/admin-api/coupons/logs?search=LOG-COUPON')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.logs.data')
        ->assertJsonPath('data.logs.data.0.coupon.code', 'LOG-COUPON')
        ->assertJsonPath('data.logs.data.0.action', 'updated');
});
