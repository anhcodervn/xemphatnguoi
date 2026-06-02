<?php

use App\Models\BankAccount;
use App\Models\RechargeMethod;
use App\Models\User;

test('authenticated user can list recharge methods with summary', function () {
    RechargeMethod::factory()->count(2)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/admin-api/recharge-methods')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(2, 'data.methods.data')
        ->assertJsonPath('data.summary.total', 2);
});

test('authenticated user can search recharge methods by bank information', function () {
    $user = User::factory()->create();

    RechargeMethod::factory()->create([
        'code' => 'vcb-main',
        'name' => 'Vietcombank chinh',
        'bank_name' => 'Vietcombank',
        'account_number' => '123456789',
        'account_name' => 'CONG TY ABC',
    ]);

    RechargeMethod::factory()->create([
        'code' => 'mb-backup',
        'name' => 'MB du phong',
        'bank_name' => 'MB Bank',
        'account_number' => '999888777',
        'account_name' => 'CONG TY XYZ',
    ]);

    $this->actingAs($user)
        ->getJson('/admin-api/recharge-methods?search=123456789')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.methods.data')
        ->assertJsonPath('data.methods.data.0.code', 'vcb-main');
});

test('authenticated user can create a recharge method with linked bank accounts', function () {
    $user = User::factory()->create();
    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'MAIN ACCOUNT',
        'account_number' => '1234567890',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->postJson('/admin-api/recharge-methods', [
        'code' => 'nap-tu-dong',
        'name' => 'Nap tu dong',
        'description' => 'Kenh nap tien uu tien',
        'badge_label' => 'Auto',
        'badge_type' => 'auto',
        'bank_name' => 'ACB',
        'account_number' => '1234567890',
        'account_name' => 'MAIN ACCOUNT',
        'secret_key' => 'acb-secret-key',
        'min_amount' => 10000,
        'max_amount' => 1000000,
        'bonus_percentage' => 5,
        'sort_order' => 1,
        'is_active' => true,
        'bank_account_ids' => [$bankAccount->id],
        'metadata' => ['channel' => 'priority'],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'nap-tu-dong');

    $this->assertDatabaseHas('recharge_methods', [
        'code' => 'nap-tu-dong',
        'name' => 'Nap tu dong',
        'is_active' => 1,
        'secret_key' => 'acb-secret-key',
    ]);

    $this->assertDatabaseHas('recharge_method_bank_account', [
        'bank_account_id' => $bankAccount->id,
        'sort_order' => 1,
        'is_active' => 1,
    ]);
});

test('authenticated user can create a recharge method with a human readable code input', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/admin-api/recharge-methods', [
            'code' => 'Vietcombank Chính',
            'name' => 'Vietcombank chính',
            'description' => 'Tài khoản nhận tiền mặc định',
            'badge_label' => 'Khuyến nghị',
            'badge_type' => 'manual',
            'bank_name' => 'Vietcombank',
            'account_number' => '123456789',
            'account_name' => 'CONG TY ABC',
            'secret_key' => 'vcb-secret-key',
            'min_amount' => 10000,
            'max_amount' => 1000000,
            'bonus_percentage' => 0,
            'sort_order' => 0,
            'is_active' => true,
            'bank_account_ids' => [],
            'metadata' => [],
        ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'vietcombank-chinh');
});

test('authenticated user can update a recharge method', function () {
    $user = User::factory()->create();
    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'mb',
        'account_name' => 'BACKUP ACCOUNT',
        'account_number' => '9988776655',
        'status' => 'active',
    ]);
    $rechargeMethod = RechargeMethod::factory()->create([
        'code' => 'nap-thu-cong',
        'badge_type' => 'manual',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patchJson("/admin-api/recharge-methods/{$rechargeMethod->id}", [
            'code' => 'nap-da-cap-nhat',
            'name' => 'Nap da cap nhat',
            'description' => 'Da doi cau hinh',
            'badge_label' => 'Manual',
            'badge_type' => 'manual',
            'bank_name' => 'MB',
            'account_number' => '9988776655',
            'account_name' => 'BACKUP ACCOUNT',
            'secret_key' => 'mb-secret-key',
            'min_amount' => 50000,
            'max_amount' => 2000000,
            'bonus_percentage' => 10,
            'sort_order' => 2,
            'is_active' => false,
            'bank_account_ids' => [$bankAccount->id],
            'metadata' => ['channel' => 'backup'],
        ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'nap-da-cap-nhat')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('recharge_methods', [
        'id' => $rechargeMethod->id,
        'code' => 'nap-da-cap-nhat',
        'is_active' => 0,
        'secret_key' => 'mb-secret-key',
    ]);
});

test('authenticated user can delete a recharge method', function () {
    $user = User::factory()->create();
    $rechargeMethod = RechargeMethod::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/admin-api/recharge-methods/{$rechargeMethod->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertDatabaseMissing('recharge_methods', [
        'id' => $rechargeMethod->id,
    ]);
});
