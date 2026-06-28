<?php

use App\Models\ConfigRecharge;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

function adminRechargeUser(): User
{
    return User::factory()->create([
        'role' => 'admin',
    ]);
}

test('admin can create recharge config with qr template preview', function () {
    $admin = adminRechargeUser();

    $this->actingAs($admin)->postJson('/api/admin-api/recharge-config', [
        'provider' => 'manual',
        'bank_name' => 'Vietcombank',
        'account_name' => 'CONG TY AUTOCRON',
        'account_number' => '1029384688',
        'qr_template' => 'https://img.vietqr.io/image/MB-1029384688-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'noidung',
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.config.bank_name', 'Vietcombank')
        ->assertJsonPath('data.config.provider', 'manual')
        ->assertJsonPath('data.config.transfer_prefix', 'NOIDUNG')
        ->assertJsonPath('data.config.preview_transfer_content', 'NOIDUNGabcd123')
        ->assertJsonPath(
            'data.config.preview_qr_url',
            'https://img.vietqr.io/image/MB-1029384688-compact2.png?amount=500000&addInfo=NOIDUNGabcd123&accountName=CONG%20TY%20AUTOCRON'
        );

    $this->assertDatabaseHas('config_recharge', [
        'provider' => 'manual',
        'bank_name' => 'Vietcombank',
        'account_name' => 'CONG TY AUTOCRON',
        'account_number' => '1029384688',
        'qr_template' => 'https://img.vietqr.io/image/MB-1029384688-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'NOIDUNG',
        'is_active' => 1,
    ]);
});

test('admin can list toggle update and delete recharge config cards', function () {
    $admin = adminRechargeUser();

    $config = ConfigRecharge::query()->create([
        'provider' => 'manual',
        'bank_name' => 'MBBank',
        'account_name' => 'NGUYEN VAN A',
        'account_number' => '123456789',
        'qr_template' => 'https://img.vietqr.io/image/MB-123456789-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'NAP',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->getJson('/api/admin-api/recharge-config')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.configs')
        ->assertJsonPath('data.configs.0.id', $config->id);

    $this->actingAs($admin)->patchJson("/api/admin-api/recharge-config/{$config->id}", [
        'provider' => 'manual',
        'bank_name' => 'Techcombank',
        'account_name' => 'NGUYEN VAN B',
        'account_number' => '999999999',
        'qr_template' => 'https://img.vietqr.io/image/TCB-999999999-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'cron',
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.config.bank_name', 'Techcombank')
        ->assertJsonPath('data.config.transfer_prefix', 'CRON');

    $this->actingAs($admin)->patchJson("/api/admin-api/recharge-config/{$config->id}/toggle")
        ->assertOk()
        ->assertJsonPath('data.config.is_active', false);

    $this->actingAs($admin)->deleteJson("/api/admin-api/recharge-config/{$config->id}")
        ->assertOk()
        ->assertJsonPath('status', true);

    $this->assertDatabaseMissing('config_recharge', [
        'id' => $config->id,
    ]);
});

test('admin can verify apibankvn credentials and fetch bank accounts', function () {
    $admin = adminRechargeUser();

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), '/list-bank-accounts')) {
            return Http::response([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'bank_accounts' => [
                        [
                            'bank_id' => 25,
                            'bank_code' => 'mbbank',
                            'bank_name' => 'MBBank',
                            'bank_full_name' => 'Military Commercial Joint Stock Bank',
                            'bank_short_name' => 'MBBank',
                            'bank_logo' => null,
                            'bank_bg_color' => '#2563EB',
                            'account_name' => 'NGUYEN VAN A',
                            'account_number' => '0123456789',
                            'username' => 'partner_login_name',
                            'status' => 'active',
                            'last_sync_at' => now()->toISOString(),
                        ],
                    ],
                ],
            ]);
        }

        return Http::response([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'user' => [
                    'id' => 12,
                    'username' => 'partner_a',
                    'email' => 'partner@example.com',
                ],
                'permissions' => [
                    ['key' => 'bank-accounts.read'],
                ],
                'endpoints' => [
                    'GET /api/v1',
                    'GET /api/v1/list-bank-accounts',
                ],
            ],
        ]);
    });

    $this->actingAs($admin)->postJson('/api/admin-api/recharge-config/verify-credentials', [
        'api_key' => 'your_api_key',
        'api_secret' => 'your_api_secret',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.user.username', 'partner_a')
        ->assertJsonPath('data.bank_accounts.0.bank_id', 25)
        ->assertJsonPath('data.bank_accounts.0.account_number', '0123456789');
});

test('client wallet overview returns selected config and config list', function () {
    $user = User::factory()->create([
        'id' => 123,
    ]);

    ConfigRecharge::query()->create([
        'provider' => 'manual',
        'bank_name' => 'ACB',
        'account_name' => 'AUTOCRON ACB',
        'account_number' => '09090909',
        'qr_template' => 'https://img.vietqr.io/image/ACB-09090909-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'NOIDUNG',
        'is_active' => true,
    ]);

    ConfigRecharge::query()->create([
        'provider' => 'manual',
        'bank_name' => 'MBBank',
        'account_name' => 'AUTOCRON MB',
        'account_number' => '123123123',
        'qr_template' => 'https://img.vietqr.io/image/MB-123123123-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'NAP',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $overviewResponse = $this->getJson('/api/client/wallet?amount=500000')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(2, 'data.recharge_configs')
        ->assertJsonPath('data.recharge_config.bank_name', 'MBBank')
        ->assertJsonPath('data.recharge_config.transfer_prefix', 'NAP');

    $overviewPayload = $overviewResponse->json('data.recharge_config');

    $overviewTransferContent = (string) $overviewPayload['transfer_content'];

    expect($overviewTransferContent)->toMatch('/^NAP[a-z0-9]{4}123$/')
        ->and($overviewPayload['qr_url'])->toBe("https://img.vietqr.io/image/MB-123123123-compact2.png?amount=500000&addInfo={$overviewTransferContent}&accountName=AUTOCRON%20MB");
});

test('client can create deposit request using selected recharge config', function () {
    $user = User::factory()->create([
        'id' => 456,
    ]);

    ConfigRecharge::query()->create([
        'provider' => 'manual',
        'bank_name' => 'Vietcombank',
        'account_name' => 'AUTOCRON VCB',
        'account_number' => '11112222',
        'qr_template' => 'https://img.vietqr.io/image/VCB-11112222-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'VCB',
        'is_active' => true,
    ]);

    $selectedConfig = ConfigRecharge::query()->create([
        'provider' => 'manual',
        'bank_name' => 'Techcombank',
        'account_name' => 'AUTOCRON TECH',
        'account_number' => '22223333',
        'qr_template' => 'https://img.vietqr.io/image/TCB-22223333-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'NAP',
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/client/wallet/deposit-requests', [
        'amount' => 250000,
        'config_id' => $selectedConfig->id,
    ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit_request.amount', 250000)
        ->assertJsonPath('data.deposit_request.status', 'pending');

    $transactionId = $response->json('data.deposit_request.id');
    $transferContent = (string) $response->json('data.deposit_request.content');

    expect($transferContent)->toMatch('/^NAP[a-z0-9]{4}456$/');

    $this->assertDatabaseHas('payment_transactions', [
        'id' => $transactionId,
        'user_id' => $user->id,
        'bank_code' => 'Techcombank',
        'account_number' => '22223333',
        'content' => $transferContent,
        'status' => 'pending',
    ]);

    $transaction = PaymentTransaction::query()->findOrFail($transactionId);

    expect($transaction->raw_data['recharge_config_id'] ?? null)->toBe($selectedConfig->id)
        ->and($transaction->raw_data['qr_url'] ?? null)->toBe("https://img.vietqr.io/image/TCB-22223333-compact2.png?amount=250000&addInfo={$transferContent}&accountName=AUTOCRON%20TECH");
});

test('client can create and sync apibankvn api deposit request into wallet balance', function () {
    Http::fake([
        'https://apibankvn.com/api/v1/recharge-orders' => Http::response([
            'status' => true,
            'data' => [
                'order' => [
                    'order_code' => 'RCL2606180001',
                    'client_order_code' => 'DEPAPI001',
                    'bank_id' => 25,
                    'method' => 'mb',
                    'method_label' => 'Chuyển khoản ngân hàng',
                    'amount' => 150000,
                    'bank_name' => 'MB',
                    'account_number' => '1900123456',
                    'account_name' => 'APIBANKVN',
                    'transfer_content' => 'NAPAPI001',
                    'status' => 'pending',
                    'requested_at' => now()->toISOString(),
                    'expires_at' => now()->addHour()->toISOString(),
                    'metadata' => [
                        'source' => 'api.v1',
                    ],
                ],
            ],
        ], 201),
        'https://apibankvn.com/api/v1/recharge-orders/RCL2606180001' => Http::response([
            'status' => true,
            'data' => [
                'order' => [
                    'order_code' => 'RCL2606180001',
                    'client_order_code' => 'DEPAPI001',
                    'bank_id' => 25,
                    'method' => 'mb',
                    'method_label' => 'Chuyển khoản ngân hàng',
                    'amount' => 150000,
                    'bank_name' => 'MB',
                    'account_number' => '1900123456',
                    'account_name' => 'APIBANKVN',
                    'transfer_content' => 'NAPAPI001',
                    'status' => 'paid',
                    'paid_at' => now()->toISOString(),
                    'requested_at' => now()->subMinutes(5)->toISOString(),
                    'expires_at' => now()->addHour()->toISOString(),
                    'metadata' => [
                        'source' => 'api.v1',
                    ],
                ],
            ],
        ]),
    ]);

    $user = User::factory()->create();

    $config = ConfigRecharge::query()->create([
        'provider' => 'apibankvn_api',
        'bank_name' => 'MB',
        'account_name' => 'APIBANKVN',
        'account_number' => '1900123456',
        'qr_template' => 'https://img.vietqr.io/image/MB-1900123456-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'CRON',
        'api_base_url' => 'https://apibankvn.com',
        'api_key' => 'partner-key',
        'api_secret' => 'partner-secret',
        'api_bank_id' => 25,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $createdResponse = $this->postJson('/api/client/wallet/deposit-requests', [
        'amount' => 150000,
        'config_id' => $config->id,
    ])
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit_request.bank_name', 'MB')
        ->assertJsonPath('data.deposit_request.account_number', '1900123456')
        ->assertJsonPath('data.deposit_request.status', 'pending');

    $createdTransferContent = (string) $createdResponse->json('data.deposit_request.content');

    expect($createdTransferContent)->toMatch('/^CRON[a-z0-9]{4}'.$user->id.'$/')
        ->and($createdResponse->json('data.deposit_request.qr_url'))
        ->toBe("https://img.vietqr.io/image/MB-1900123456-compact2.png?amount=150000&addInfo={$createdTransferContent}&accountName=APIBANKVN");

    Http::assertSent(function (Request $request) {
        if ($request->url() !== 'https://apibankvn.com/api/v1/recharge-orders') {
            return false;
        }

        return $request['bank_id'] === 25
            && $request['amount'] === 150000
            && $request['transfer_prefix'] === 'CRON'
            && is_string($request['transfer_content'])
            && str_starts_with($request['transfer_content'], 'CRON');
    });

    $transactionId = $createdResponse->json('data.deposit_request.id');

    PaymentTransaction::query()->whereKey($transactionId)->update([
        'transaction_code' => 'DEPAPI001',
    ]);

    $this->postJson("/api/client/wallet/deposit-requests/{$transactionId}/confirm")
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit_request.status', 'paid')
        ->assertJsonPath('data.deposit_request.content', $createdTransferContent);

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect((float) $wallet->balance)->toBe(150000.0)
        ->and((float) $wallet->total_recharge)->toBe(150000.0)
        ->and(WalletTransaction::query()->where('reference_type', PaymentTransaction::class)->where('reference_id', $transactionId)->count())->toBe(1);
});

test('apibankvn callback rejects invalid webhook secret', function () {
    ConfigRecharge::query()->create([
        'provider' => 'apibankvn_api',
        'bank_name' => 'MB',
        'account_name' => 'APIBANKVN',
        'account_number' => '1900123456',
        'qr_template' => 'https://img.vietqr.io/image/MB-1900123456-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'CRON',
        'api_base_url' => 'https://apibankvn.com',
        'api_key' => 'partner-key',
        'api_secret' => 'callback-secret',
        'api_bank_id' => 34,
        'is_active' => true,
    ]);

    $this->postJson('/api/recharge/callbacks/apibankvn', [
        'bank_id' => 34,
        'sign' => md5('wrong-secret34'),
        'client_order_code' => 'DEPINVALID001',
        'status' => 'paid',
    ], [
        'X-Webhook-Secret' => 'wrong-secret',
    ])
        ->assertForbidden()
        ->assertJsonPath('status', false);
});

test('apibankvn callback updates deposit status and credits wallet balance', function () {
    $user = User::factory()->create();

    $config = ConfigRecharge::query()->create([
        'provider' => 'apibankvn_api',
        'bank_name' => 'MB',
        'account_name' => 'APIBANKVN',
        'account_number' => '1900123456',
        'qr_template' => 'https://img.vietqr.io/image/MB-1900123456-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'CRON',
        'api_base_url' => 'https://apibankvn.com',
        'api_key' => 'partner-key',
        'api_secret' => 'partner-secret',
        'api_bank_id' => 25,
        'is_active' => false,
    ]);

    $transaction = PaymentTransaction::query()->create([
        'user_id' => $user->id,
        'bank_code' => 'MB',
        'account_number' => '1900123456',
        'transaction_code' => 'DEPAPI002',
        'amount' => 200000,
        'content' => 'CRONabcd'.$user->id,
        'status' => 'pending',
        'raw_data' => [
            'provider' => 'apibankvn_api',
            'method_id' => 'apibankvn_api',
            'method_name' => 'Apibankvn API',
            'recharge_config_id' => $config->id,
            'requested_transfer_content' => 'CRONabcd'.$user->id,
            'remote_order_code' => 'RCL2606270002',
            'remote_status' => 'pending',
        ],
    ]);

    $this->postJson('/api/recharge/callbacks/apibankvn', [
        'bank_id' => 25,
        'sign' => md5('partner-secret25'),
        'data' => [
            'order' => [
                'order_code' => 'RCL2606270002',
                'client_order_code' => 'DEPAPI002',
                'amount' => 200000,
                'bank_name' => 'MB',
                'account_number' => '1900123456',
                'account_name' => 'APIBANKVN',
                'transfer_content' => 'CRONabcd'.$user->id,
                'status' => 'paid',
                'paid_at' => now()->toISOString(),
            ],
        ],
    ], [
        'X-Webhook-Secret' => 'partner-secret',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit_request.status', 'paid')
        ->assertJsonPath('data.deposit_request.code', 'DEPAPI002');

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect((float) $wallet->balance)->toBe(200000.0)
        ->and((float) $wallet->total_recharge)->toBe(200000.0);

    $transaction->refresh();

    expect($transaction->status)->toBe('success')
        ->and($transaction->raw_data['remote_status'] ?? null)->toBe('paid')
        ->and($transaction->raw_data['confirmed_at'] ?? null)->not->toBeNull()
        ->and(WalletTransaction::query()->where('reference_type', PaymentTransaction::class)->where('reference_id', $transaction->id)->count())->toBe(1);
});

test('apibankvn bank transaction webhook matches deposit by description and credits wallet balance', function () {
    $user = User::factory()->create();

    $config = ConfigRecharge::query()->create([
        'provider' => 'apibankvn_api',
        'bank_name' => 'MB',
        'account_name' => 'APIBANKVN',
        'account_number' => '1900123456',
        'qr_template' => 'https://img.vietqr.io/image/MB-1900123456-compact2.png?amount={amount}&addInfo={nd}&accountName={account_name}',
        'transfer_prefix' => 'CRON',
        'api_base_url' => 'https://apibankvn.com',
        'api_key' => 'partner-key',
        'api_secret' => 'partner-secret',
        'api_bank_id' => 34,
        'is_active' => true,
    ]);

    $transaction = PaymentTransaction::query()->create([
        'user_id' => $user->id,
        'bank_code' => 'MB',
        'account_number' => '1900123456',
        'transaction_code' => 'DEPAPIHOOK001',
        'amount' => 200000,
        'content' => 'CRONabcd'.$user->id,
        'status' => 'pending',
        'raw_data' => [
            'provider' => 'apibankvn_api',
            'method_id' => 'apibankvn_api',
            'method_name' => 'Apibankvn API',
            'recharge_config_id' => $config->id,
            'requested_transfer_prefix' => 'CRON',
            'requested_transfer_content' => 'CRONabcd'.$user->id,
            'remote_order_code' => 'RCL260627HOOK1',
            'remote_status' => 'pending',
        ],
    ]);

    $this->postJson('/api/recharge/callbacks/apibankvn', [
        'event_keyword' => 'bank.transaction',
        'webhook_id' => 32,
        'bank_id' => 34,
        'bank_account_id' => 34,
        'sign' => md5('partner-secret34'),
        'payload' => [
            'source' => 'cron.bank-sync',
            'bank_account_id' => 34,
            'transaction' => [
                'id' => 178,
                'transaction_id' => '88079999999-20260620',
                'amount' => '200000.00',
                'description' => 'Nap tien CRONabcd'.$user->id.' cho tai khoan',
                'transaction_time' => '2026-06-20 23:22:14',
                'type' => 'credit',
                'raw_data' => [
                    'transaction_id' => '88079999999-20260620',
                    'amount' => 200000,
                    'description' => 'Nap tien CRONabcd'.$user->id.' cho tai khoan',
                    'transaction_time' => '2026-06-20 23:22:14',
                    'type' => 'credit',
                    'raw_data' => [
                        'accountNo' => '88079999999',
                    ],
                ],
            ],
        ],
    ], [
        'X-Webhook-Secret' => 'partner-secret',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit_request.status', 'paid')
        ->assertJsonPath('data.deposit_request.code', 'DEPAPIHOOK001');

    $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();

    expect((float) $wallet->balance)->toBe(200000.0)
        ->and((float) $wallet->total_recharge)->toBe(200000.0);

    $transaction->refresh();

    expect($transaction->status)->toBe('success')
        ->and(data_get($transaction->raw_data, 'callback_metadata.transaction_id'))->toBe('88079999999-20260620')
        ->and(data_get($transaction->raw_data, 'callback_metadata.transaction_description'))->toBe('Nap tien CRONabcd'.$user->id.' cho tai khoan')
        ->and(WalletTransaction::query()->where('reference_type', PaymentTransaction::class)->where('reference_id', $transaction->id)->count())->toBe(1);
});

test('client can confirm own deposit request and history returns processing status', function () {
    $user = User::factory()->create();

    $transaction = PaymentTransaction::query()->create([
        'user_id' => $user->id,
        'bank_code' => 'Vietinbank',
        'account_number' => '11112222',
        'transaction_code' => 'DEPTEST001',
        'amount' => 300000,
        'content' => 'NAP'.$user->id,
        'status' => 'pending',
        'raw_data' => [
            'method_id' => 'bank_transfer',
            'method_name' => 'Chuyển khoản ngân hàng',
            'expires_at' => now()->addDay()->toISOString(),
        ],
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/client/wallet/deposit-requests/{$transaction->id}/confirm")
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.deposit_request.status', 'processing');

    $this->getJson('/api/client/wallet/deposit-requests?status=processing')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.code', 'DEPTEST001')
        ->assertJsonPath('data.data.0.status', 'processing');
});

test('client cannot confirm another user deposit request', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $transaction = PaymentTransaction::query()->create([
        'user_id' => $owner->id,
        'bank_code' => 'BIDV',
        'account_number' => '55556666',
        'transaction_code' => 'DEPTEST002',
        'amount' => 120000,
        'content' => 'NAP'.$owner->id,
        'status' => 'pending',
        'raw_data' => [
            'method_id' => 'bank_transfer',
            'method_name' => 'Chuyển khoản ngân hàng',
            'expires_at' => now()->addDay()->toISOString(),
        ],
    ]);

    Sanctum::actingAs($intruder);

    $this->postJson("/api/client/wallet/deposit-requests/{$transaction->id}/confirm")
        ->assertNotFound();
});

test('admin can view recharge history list', function () {
    $admin = adminRechargeUser();
    $user = User::factory()->create([
        'email' => 'nap-tien@example.com',
    ]);

    PaymentTransaction::query()->create([
        'user_id' => $user->id,
        'bank_code' => 'MB',
        'account_number' => '123456789',
        'transaction_code' => 'DEPADMIN001',
        'amount' => 500000,
        'content' => 'NAP'.$user->id,
        'status' => 'matched',
        'raw_data' => [
            'account_name' => 'AUTOCRON MB',
            'confirmed_at' => now()->toISOString(),
        ],
    ]);

    $this->actingAs($admin)->getJson('/api/admin-api/recharge-history?search=DEPADMIN001')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.data.0.transaction_code', 'DEPADMIN001')
        ->assertJsonPath('data.data.0.status', 'processing')
        ->assertJsonPath('data.data.0.user.email', 'nap-tien@example.com');
});
