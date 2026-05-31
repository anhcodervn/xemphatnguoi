<?php

use App\Models\RechargeOrder;
use App\Models\User;
use App\Models\UserLog;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Webhook;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('webhooks');
    Schema::dropIfExists('user_logs');
    Schema::dropIfExists('wallet_transactions');
    Schema::dropIfExists('recharge_orders');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('full_name')->nullable();
        $table->string('avatar')->nullable();
        $table->string('google_id')->nullable()->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamp('last_login_at')->nullable();
        $table->string('last_login_ip', 45)->nullable();
        $table->string('referral_code')->nullable()->unique();
        $table->unsignedBigInteger('referred_by')->nullable();
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('wallets', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('recharge_orders', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('recharge_method_id')->nullable();
        $table->unsignedBigInteger('bank_account_id')->nullable();
        $table->string('order_code')->unique();
        $table->string('method')->nullable();
        $table->string('method_label')->nullable();
        $table->decimal('amount', 16, 2)->default(0);
        $table->decimal('bonus_amount', 16, 2)->default(0);
        $table->decimal('total_amount', 16, 2)->default(0);
        $table->string('bank_name')->nullable();
        $table->string('account_number')->nullable();
        $table->string('account_name')->nullable();
        $table->string('transfer_content')->nullable();
        $table->string('status')->default(RechargeOrder::STATUS_PENDING);
        $table->timestamp('requested_at')->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
    });

    Schema::create('wallet_transactions', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('wallet_id');
        $table->string('type');
        $table->decimal('amount', 16, 2)->default(0);
        $table->decimal('balance_before', 16, 2)->default(0);
        $table->decimal('balance_after', 16, 2)->default(0);
        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();
        $table->text('description')->nullable();
        $table->string('status')->default('success');
        $table->timestamps();
    });

    Schema::create('webhooks', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('bank_account_id')->nullable();
        $table->string('name');
        $table->string('url');
        $table->string('secret_key');
        $table->string('event_keyword')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('user_logs', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('action');
        $table->text('description')->nullable();
        $table->string('ip', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamps();
    });
});

function createPendingRechargeOrder(): RechargeOrder
{
    $user = User::query()->create([
        'username' => 'callback-user',
        'email' => 'callback@example.com',
        'password' => 'password',
    ]);

    return RechargeOrder::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => 25,
        'order_code' => 'DEP123456',
        'amount' => 100000,
        'bonus_amount' => 5000,
        'total_amount' => 105000,
        'transfer_content' => 'DEP123456',
        'status' => RechargeOrder::STATUS_PENDING,
        'requested_at' => now(),
        'expires_at' => now()->addHour(),
    ]);
}

function createPendingRechargeOrderWithCode(string $orderCode, int $bankId = 25): RechargeOrder
{
    $user = User::query()->create([
        'username' => 'callback-user-'.strtolower($orderCode),
        'email' => strtolower($orderCode).'@example.com',
        'password' => 'password',
    ]);

    return RechargeOrder::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankId,
        'order_code' => $orderCode,
        'amount' => 10000,
        'bonus_amount' => 0,
        'total_amount' => 10000,
        'transfer_content' => $orderCode,
        'status' => RechargeOrder::STATUS_PENDING,
        'requested_at' => now(),
        'expires_at' => now()->addHour(),
    ]);
}

function createCallbackWebhook(User $user, int $bankId = 25, string $secret = 'callback-secret'): Webhook
{
    return Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankId,
        'name' => 'Callback webhook',
        'url' => 'http://localhost:8000/cronjob/callback-apibankvn',
        'secret_key' => $secret,
        'status' => 'active',
    ]);
}

/**
 * @return array{bank_id:int,sign:string}
 */
function callbackSignaturePayload(int $bankId = 25, string $secret = 'callback-secret'): array
{
    return [
        'bank_id' => $bankId,
        'sign' => md5($secret.$bankId),
    ];
}

test('callback apibankvn approves recharge order and credits wallet when order code appears in description', function () {
    $order = createPendingRechargeOrder();
    createCallbackWebhook($order->user, 25);

    $response = $this
        ->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', [
            ...callbackSignaturePayload(25),
            'type' => 'credit',
            'amount' => 105000,
            'description' => 'Khach hang chuyen tien DEP123456 vao tai khoan',
        ]);

    $response->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.ignored', false)
        ->assertJsonPath('data.order_code', 'DEP123456');

    expect($order->fresh()->status)->toBe(RechargeOrder::STATUS_PAID)
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and((float) $order->user->wallet()->firstOrFail()->fresh()->balance)->toBe(105000.0)
        ->and((float) $order->user->wallet()->firstOrFail()->fresh()->total_recharge)->toBe(100000.0);

    expect(WalletTransaction::query()->count())->toBe(1)
        ->and(WalletTransaction::query()->first()?->reference_id)->toBe($order->id);

    expect(UserLog::query()->count())->toBe(1)
        ->and(UserLog::query()->first()?->action)->toBe('recharge_order_paid');
});

test('callback apibankvn ignores payload when description does not contain any recharge order code', function () {
    $order = createPendingRechargeOrder();
    createCallbackWebhook($order->user, 25);

    $response = $this
        ->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', [
            ...callbackSignaturePayload(25),
            'type' => 'credit',
            'amount' => 105000,
            'description' => 'Noi dung khong khop lenh nap nao',
        ]);

    $response->assertOk()
        ->assertJsonPath('data.ignored', true)
        ->assertJsonPath('data.reason', 'order_not_found');

    expect(RechargeOrder::query()->firstOrFail()->status)->toBe(RechargeOrder::STATUS_PENDING)
        ->and(WalletTransaction::query()->count())->toBe(0);
});

test('callback apibankvn does not double credit an already processed recharge order', function () {
    $order = createPendingRechargeOrder();
    createCallbackWebhook($order->user, 25);

    $payload = [
        ...callbackSignaturePayload(25),
        'type' => 'credit',
        'amount' => 105000,
        'description' => 'Khach hang chuyen tien DEP123456 vao tai khoan',
    ];

    $this->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', $payload)
        ->assertOk();

    $secondResponse = $this
        ->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', $payload);

    $secondResponse->assertOk()
        ->assertJsonPath('data.ignored', true)
        ->assertJsonPath('data.reason', 'order_not_found');

    expect(WalletTransaction::query()->count())->toBe(1)
        ->and((float) Wallet::query()->where('user_id', $order->user_id)->where('type', Wallet::TYPE_MAIN)->firstOrFail()->balance)->toBe(105000.0);
});

test('callback apibankvn rejects invalid signature', function () {
    $order = createPendingRechargeOrder();
    createCallbackWebhook($order->user, 25);

    $response = $this
        ->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', [
            ...callbackSignaturePayload(25),
            'type' => 'credit',
            'amount' => 105000,
            'description' => 'Khach hang chuyen tien DEP123456 vao tai khoan',
            'sign' => 'invalid-signature',
        ]);

    $response->assertUnauthorized()
        ->assertJsonPath('status', false)
        ->assertJsonPath('data.reason', 'invalid_signature');

    expect(RechargeOrder::query()->firstOrFail()->status)->toBe(RechargeOrder::STATUS_PENDING)
        ->and(WalletTransaction::query()->count())->toBe(0);
});

test('callback apibankvn can resolve transaction data from outbound webhook payload structure', function () {
    $order = createPendingRechargeOrder();
    createCallbackWebhook($order->user, 25);

    $response = $this
        ->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', [
            ...callbackSignaturePayload(25),
            'event_keyword' => '',
            'webhook_id' => 3,
            'bank_account_id' => 25,
            'payload' => [
                'source' => 'client-bank-manager.transaction',
                'bank_account_id' => 25,
                'transaction' => [
                    'id' => 137,
                    'transaction_id' => 'FT26151852008410',
                    'amount' => '105000.00',
                    'description' => 'CUSTOMER DEP123456. TU: NGUYEN TUAN ANH',
                    'transaction_time' => '2026-05-31 16:58:08',
                    'type' => 'credit',
                    'raw_data' => [
                        'creditAmount' => '105000',
                        'description' => 'CUSTOMER DEP123456. TU: NGUYEN TUAN ANH',
                        'DorCCode' => 'C',
                    ],
                ],
            ],
        ]);

    $response->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.ignored', false)
        ->assertJsonPath('data.order_code', 'DEP123456');

    expect($order->fresh()->status)->toBe(RechargeOrder::STATUS_PAID)
        ->and(WalletTransaction::query()->count())->toBe(1);
});

test('callback apibankvn matches recharge order when order code appears inside a different transaction description string', function () {
    $order = createPendingRechargeOrderWithCode('DEP260531095711CYUQ', 34);
    createCallbackWebhook($order->user, 34);

    $response = $this
        ->withHeaders(['X-Webhook-Secret' => 'callback-secret'])
        ->postJson('/cronjob/callback-apibankvn', [
            ...callbackSignaturePayload(34),
            'payload' => [
                'transaction' => [
                    'amount' => '10000.00',
                    'description' => 'CUSTOMER DEP260531095711CYUQ. TU: NGUYEN TUAN ANH',
                    'type' => 'credit',
                ],
            ],
        ]);

    $response->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.ignored', false)
        ->assertJsonPath('data.order_code', 'DEP260531095711CYUQ');

    expect($order->fresh()->status)->toBe(RechargeOrder::STATUS_PAID)
        ->and(WalletTransaction::query()->count())->toBe(1);
});
