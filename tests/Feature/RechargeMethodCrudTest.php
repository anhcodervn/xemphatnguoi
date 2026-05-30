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

test('authenticated user can create a recharge method with linked bank accounts', function () {
    $user = User::factory()->create();

    $firstBankAccount = BankAccount::query()->create([
        'bank_name' => 'VCB',
        'account_name' => 'ACCOUNT ONE',
        'account_number' => '1234567890',
        'status' => 'active',
    ]);

    $secondBankAccount = BankAccount::query()->create([
        'bank_name' => 'ACB',
        'account_name' => 'ACCOUNT TWO',
        'account_number' => '0987654321',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->postJson('/admin-api/recharge-methods', [
            'code' => 'banking-main',
            'name' => 'Chuyển khoản chính',
            'description' => 'Đối soát tự động',
            'badge_label' => 'Tự động',
            'badge_type' => 'auto',
            'bank_name' => 'Fallback',
            'account_number' => '0000000000',
            'account_name' => 'FALLBACK ACCOUNT',
            'min_amount' => 50000,
            'max_amount' => 100000000,
            'bonus_percentage' => 10,
            'sort_order' => 1,
            'is_active' => true,
            'bank_account_ids' => [$firstBankAccount->id, $secondBankAccount->id],
            'metadata' => [
                'channel' => 'banking',
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'banking-main')
        ->assertJsonPath('data.bank_accounts.0.id', $firstBankAccount->id)
        ->assertJsonPath('data.bank_accounts.1.id', $secondBankAccount->id);

    $method = RechargeMethod::query()->where('code', 'banking-main')->firstOrFail();

    expect($method->bankAccounts)->toHaveCount(2)
        ->and($method->bankAccounts[0]->pivot->sort_order)->toBe(1)
        ->and($method->bankAccounts[1]->pivot->sort_order)->toBe(2);
});

test('authenticated user can update a recharge method', function () {
    $user = User::factory()->create();
    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'MB',
        'account_name' => 'UPDATED ACCOUNT',
        'account_number' => '111100002222',
        'status' => 'active',
    ]);
    $method = RechargeMethod::factory()->create([
        'code' => 'banking-old',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->patchJson("/admin-api/recharge-methods/{$method->id}", [
            'code' => 'banking-new',
            'name' => 'Chuyển khoản mới',
            'description' => 'Cập nhật',
            'badge_label' => 'Thủ công',
            'badge_type' => 'manual',
            'bank_name' => 'Manual Bank',
            'account_number' => '222233334444',
            'account_name' => 'MANUAL ACCOUNT',
            'min_amount' => 100000,
            'max_amount' => 200000000,
            'bonus_percentage' => 5,
            'sort_order' => 3,
            'is_active' => false,
            'bank_account_ids' => [$bankAccount->id],
            'metadata' => [
                'channel' => 'manual',
            ],
        ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.code', 'banking-new')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.bank_accounts.0.id', $bankAccount->id);

    $this->assertDatabaseHas('recharge_methods', [
        'id' => $method->id,
        'code' => 'banking-new',
        'is_active' => false,
    ]);
});

test('authenticated user can delete a recharge method', function () {
    $user = User::factory()->create();
    $method = RechargeMethod::factory()->create();

    $this->actingAs($user)
        ->deleteJson("/admin-api/recharge-methods/{$method->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertDatabaseMissing('recharge_methods', [
        'id' => $method->id,
    ]);
});
