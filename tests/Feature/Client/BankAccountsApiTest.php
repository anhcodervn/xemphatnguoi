<?php

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('client bank accounts api returns linked accounts with bank metadata', function () {
    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'acb',
        'name' => 'Ngân hàng Á Châu',
        'short_name' => 'ACB',
        'logo' => 'https://example.com/acb.png',
        'bg_color' => '#16A34A',
    ]);

    $olderAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tài khoản cũ',
        'account_number' => '11112222',
        'username' => 'older_user',
        'password' => 'secret-password',
        'status' => 'inactive',
        'updated_at' => now()->subDay(),
    ]);

    $latestAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tài khoản mới',
        'account_number' => '33334444',
        'username' => 'latest_user',
        'password' => 'secret-password',
        'status' => 'active',
        'updated_at' => now(),
    ]);

    $this->getJson('/api/bank/accounts')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $latestAccount->id)
        ->assertJsonPath('data.0.bank_code', 'acb')
        ->assertJsonPath('data.0.bank_name', 'ACB')
        ->assertJsonPath('data.0.bank_logo', 'https://example.com/acb.png')
        ->assertJsonPath('data.0.bank_bg_color', '#16A34A')
        ->assertJsonPath('data.0.account_name', 'Tài khoản mới')
        ->assertJsonPath('data.0.status', 'active')
        ->assertJsonPath('data.1.id', $olderAccount->id);
});
