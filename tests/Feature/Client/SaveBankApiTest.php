<?php

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('client can save a bank account via api', function () {
    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'vcb',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/bank/save-bank', [
        'bank_code' => 'vcb',
        'display_name' => 'Tài khoản chính',
        'username' => 'vcb_user_01',
        'password' => 'secret-password',
        'account_number' => '001122334455',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.bank_name', 'vcb')
        ->assertJsonPath('data.account_name', 'Tài khoản chính')
        ->assertJsonPath('data.account_number', '001122334455')
        ->assertJsonPath('data.username', 'vcb_user_01')
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('bank_accounts', [
        'bank_name' => 'vcb',
        'account_name' => 'Tài khoản chính',
        'account_number' => '001122334455',
        'username' => 'vcb_user_01',
        'status' => 'active',
    ]);
});

test('saving the same bank account updates the existing record', function () {
    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'vcb',
        'is_active' => true,
    ]);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'vcb',
        'account_name' => 'Tên cũ',
        'account_number' => '001122334455',
        'username' => 'old_user',
        'password' => 'old-password',
        'status' => 'inactive',
    ]);

    $response = $this->postJson('/api/bank/save-bank', [
        'bank_code' => 'VCB',
        'display_name' => 'Tên mới',
        'username' => 'new_user',
        'password' => 'new-password',
        'account_number' => '001122334455',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Cập nhật tài khoản ngân hàng thành công.')
        ->assertJsonPath('data.id', $bankAccount->id)
        ->assertJsonPath('data.account_name', 'Tên mới')
        ->assertJsonPath('data.username', 'new_user')
        ->assertJsonPath('data.status', 'active');

    expect(BankAccount::query()->count())->toBe(1);

    $bankAccount->refresh();

    expect($bankAccount->account_name)->toBe('Tên mới')
        ->and($bankAccount->username)->toBe('new_user')
        ->and($bankAccount->password)->toBe('new-password')
        ->and($bankAccount->status)->toBe('active');
});
