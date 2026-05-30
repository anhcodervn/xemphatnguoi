<?php

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('client can fetch linked bank account detail for editing', function () {
    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'acb',
        'name' => 'Ngân hàng Á Châu',
        'short_name' => 'ACB',
        'logo' => 'https://example.com/acb.png',
        'bg_color' => '#16A34A',
    ]);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tài khoản chính',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $this->getJson("/api/bank/accounts/{$bankAccount->id}")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.id', $bankAccount->id)
        ->assertJsonPath('data.bank_code', 'acb')
        ->assertJsonPath('data.bank_short_name', 'ACB')
        ->assertJsonPath('data.account_name', 'Tài khoản chính')
        ->assertJsonPath('data.account_number', '001122334455')
        ->assertJsonPath('data.username', 'acb_user');
});

test('client can update linked bank account without resubmitting password', function () {
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

    $this->putJson("/api/bank/accounts/{$bankAccount->id}", [
        'bank_code' => 'vcb',
        'display_name' => 'Tên mới',
        'username' => 'new_user',
        'account_number' => '9988776655',
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.id', $bankAccount->id)
        ->assertJsonPath('data.bank_code', 'vcb')
        ->assertJsonPath('data.account_name', 'Tên mới')
        ->assertJsonPath('data.account_number', '9988776655')
        ->assertJsonPath('data.username', 'new_user');

    $bankAccount->refresh();

    expect($bankAccount->account_name)->toBe('Tên mới')
        ->and($bankAccount->account_number)->toBe('9988776655')
        ->and($bankAccount->username)->toBe('new_user')
        ->and($bankAccount->password)->toBe('old-password')
        ->and($bankAccount->status)->toBe('active');
});

test('client can delete linked bank account', function () {
    Sanctum::actingAs(User::factory()->create());

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tài khoản cần xóa',
        'account_number' => '1122334455',
        'username' => 'delete_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $this->deleteJson("/api/bank/accounts/{$bankAccount->id}")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Xóa liên kết thẻ thành công.');

    $this->assertModelMissing($bankAccount);
});
