<?php

use App\Models\BankAccount;
use App\Models\RechargeOrder;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

test('non admin user cannot access admin api', function () {
    $user = User::factory()->create([
        'role' => 'user',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/admin/users')
        ->assertForbidden()
        ->assertJsonPath('status', false);
});

test('admin can adjust user wallet balance', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/users/{$user->id}/wallet-adjust", [
        'type' => 'add',
        'amount' => 125000,
        'note' => 'Manual bonus',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.wallet.balance', 125000)
        ->assertJsonPath('data.transaction.amount', 125000);

    $this->assertDatabaseHas('wallets', [
        'user_id' => $user->id,
        'type' => 'main',
        'balance' => 125000.00,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'type' => 'adjustment',
        'reference_type' => User::class,
        'reference_id' => $admin->id,
        'status' => 'success',
    ]);
});

test('admin can approve pending deposit and credit wallet', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $user = User::factory()->create();
    $deposit = RechargeOrder::factory()->create([
        'user_id' => $user->id,
        'status' => RechargeOrder::STATUS_PENDING,
        'amount' => 500000,
        'bonus_amount' => 50000,
        'total_amount' => 550000,
    ]);

    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/deposits/{$deposit->id}/approve")
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit.status', RechargeOrder::STATUS_PAID);

    $this->assertDatabaseHas('recharge_orders', [
        'id' => $deposit->id,
        'status' => RechargeOrder::STATUS_PAID,
    ]);

    $this->assertDatabaseHas('wallets', [
        'user_id' => $user->id,
        'type' => 'main',
        'balance' => 550000.00,
        'total_recharge' => 500000.00,
    ]);

    $this->assertDatabaseHas('wallet_transactions', [
        'reference_type' => RechargeOrder::class,
        'reference_id' => $deposit->id,
        'type' => 'credit',
        'amount' => 550000.00,
        'status' => 'success',
    ]);
});

test('admin can test webhook and create webhook log', function () {
    Http::fake([
        'https://example.com/hook' => Http::response(['received' => true], 200),
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
    ]);
    $user = User::factory()->create();
    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'WEBHOOK BANK',
        'account_number' => '123456789',
        'status' => 'active',
    ]);
    $webhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Primary webhook',
        'url' => 'https://example.com/hook',
        'secret_key' => 'secret-key',
        'event_keyword' => 'transaction.created',
        'status' => 'active',
    ]);

    Sanctum::actingAs($admin);

    $this->postJson("/api/admin/webhooks/{$webhook->id}/test")
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.success', true)
        ->assertJsonPath('data.http_status', 200);

    $this->assertDatabaseHas('webhook_logs', [
        'webhook_id' => $webhook->id,
        'event_keyword' => 'admin.test',
        'status_code' => 200,
        'attempt' => 1,
    ]);
});
