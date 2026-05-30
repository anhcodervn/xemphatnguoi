<?php

use App\Models\BankAccount;
use App\Models\RechargeMethod;
use App\Models\RechargeOrder;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('client recharge overview returns wallet methods stats and paginated history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    RechargeOrder::factory()->for($user)->create([
        'order_code' => 'DEP-PAID-001',
        'status' => RechargeOrder::STATUS_PAID,
        'amount' => 500000,
        'bonus_amount' => 50000,
        'total_amount' => 550000,
    ]);

    RechargeOrder::factory()->for($user)->create([
        'order_code' => 'DEP-PENDING-002',
        'status' => RechargeOrder::STATUS_PENDING,
        'amount' => 300000,
        'bonus_amount' => 30000,
        'total_amount' => 330000,
    ]);

    RechargeOrder::factory()->for($otherUser)->create([
        'order_code' => 'DEP-OTHER-003',
    ]);

    $this->getJson('/api/recharge?search=DEP&status=all&per_page=10')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.bonus_percentage', 10)
        ->assertJsonPath('data.minimum_amount', 50000)
        ->assertJsonCount(4, 'data.methods')
        ->assertJsonPath('data.methods.0.active', true)
        ->assertJsonPath('data.methods.3.active', false)
        ->assertJsonPath('data.stats.total_recharge', '500000')
        ->assertJsonPath('data.stats.total_bonus', '50000')
        ->assertJsonPath('data.stats.total_orders', 1)
        ->assertJsonPath('data.history.meta.total', 2)
        ->assertJsonPath('data.history.data.0.order_code', 'DEP-PENDING-002');
});

test('client can create recharge order', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/recharge/orders', [
        'method' => 'banking',
        'amount' => 500000,
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Tạo yêu cầu nạp tiền thành công.')
        ->assertJsonPath('data.method', 'banking')
        ->assertJsonPath('data.method_label', 'Chuyển khoản ngân hàng')
        ->assertJsonPath('data.amount', '500000.00')
        ->assertJsonPath('data.bonus_amount', '50000.00')
        ->assertJsonPath('data.total_amount', '550000.00')
        ->assertJsonPath('data.status', RechargeOrder::STATUS_PENDING);

    $order = RechargeOrder::query()->firstOrFail();

    expect($order->user_id)->toBe($user->id)
        ->and($order->bank_name)->toBe('Vietcombank')
        ->and($order->account_number)->toBe('1029384756')
        ->and($order->account_name)->toBe('CONG TY TNHH CLIENT PANEL')
        ->and($order->transfer_content)->toStartWith('NAP-BANKING-');
});

test('client recharge order uses linked bank account when method has multiple bank accounts', function () {
    $user = User::factory()->create();

    $firstBankAccount = BankAccount::query()->create([
        'bank_name' => 'ACB',
        'account_name' => 'PRIMARY ACCOUNT',
        'account_number' => '111122223333',
        'status' => 'active',
    ]);

    $secondBankAccount = BankAccount::query()->create([
        'bank_name' => 'MB',
        'account_name' => 'SECONDARY ACCOUNT',
        'account_number' => '999900001111',
        'status' => 'active',
    ]);

    $method = RechargeMethod::query()->create([
        'code' => 'bank-transfer',
        'name' => 'Chuyển khoản nhiều tài khoản',
        'badge_type' => 'auto',
        'bank_name' => 'Fallback Bank',
        'account_number' => '0000000000',
        'account_name' => 'FALLBACK ACCOUNT',
        'min_amount' => 50_000,
        'max_amount' => 100_000_000,
        'bonus_percentage' => 12,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $method->bankAccounts()->attach($secondBankAccount->id, [
        'sort_order' => 2,
        'is_active' => true,
    ]);
    $method->bankAccounts()->attach($firstBankAccount->id, [
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/recharge/orders', [
        'method' => 'bank-transfer',
        'amount' => 500000,
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.method', 'bank-transfer')
        ->assertJsonPath('data.bonus_amount', '60000.00')
        ->assertJsonPath('data.bank_name', 'ACB')
        ->assertJsonPath('data.account_number', '111122223333')
        ->assertJsonPath('data.account_name', 'PRIMARY ACCOUNT');

    $order = RechargeOrder::query()->firstOrFail();

    expect($order->recharge_method_id)->toBe($method->id)
        ->and($order->bank_account_id)->toBe($firstBankAccount->id)
        ->and($order->bank_name)->toBe('ACB')
        ->and($order->account_number)->toBe('111122223333')
        ->and($order->account_name)->toBe('PRIMARY ACCOUNT')
        ->and($order->bonus_amount)->toBe('60000.00');
});

test('client can view own recharge order only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $order = RechargeOrder::factory()->for($user)->create([
        'order_code' => 'DEP-SHOW-001',
    ]);

    $otherOrder = RechargeOrder::factory()->for($otherUser)->create();

    $this->getJson("/api/recharge/orders/{$order->id}")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.order_code', 'DEP-SHOW-001');

    $this->getJson("/api/recharge/orders/{$otherOrder->id}")
        ->assertNotFound();
});

test('client recharge order validation rejects unsupported method and low amount', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/recharge/orders', [
        'method' => 'crypto',
        'amount' => 1000,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['method', 'amount']);
});

test('client cannot create recharge order with inactive method', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/recharge/orders', [
        'method' => 'card',
        'amount' => 500000,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['method']);
});

test('client recharge overview expires stale pending and processing orders before returning history', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $expiredPendingOrder = RechargeOrder::factory()->for($user)->create([
        'order_code' => 'DEP-EXPIRED-001',
        'status' => RechargeOrder::STATUS_PENDING,
        'expires_at' => now()->subMinute(),
    ]);

    $expiredProcessingOrder = RechargeOrder::factory()->for($user)->create([
        'order_code' => 'DEP-EXPIRED-002',
        'status' => RechargeOrder::STATUS_PROCESSING,
        'expires_at' => now()->subSeconds(30),
    ]);

    $activePendingOrder = RechargeOrder::factory()->for($user)->create([
        'order_code' => 'DEP-PENDING-003',
        'status' => RechargeOrder::STATUS_PENDING,
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->getJson('/api/recharge?status=all&per_page=10')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.history.data.0.order_code', 'DEP-PENDING-003')
        ->assertJsonPath('data.history.data.1.order_code', 'DEP-EXPIRED-002')
        ->assertJsonPath('data.history.data.1.status', RechargeOrder::STATUS_EXPIRED)
        ->assertJsonPath('data.history.data.2.order_code', 'DEP-EXPIRED-001')
        ->assertJsonPath('data.history.data.2.status', RechargeOrder::STATUS_EXPIRED);

    expect($expiredPendingOrder->fresh()->status)->toBe(RechargeOrder::STATUS_EXPIRED)
        ->and($expiredProcessingOrder->fresh()->status)->toBe(RechargeOrder::STATUS_EXPIRED)
        ->and($activePendingOrder->fresh()->status)->toBe(RechargeOrder::STATUS_PENDING);
});
