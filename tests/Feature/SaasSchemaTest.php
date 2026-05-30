<?php

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('saas tables are created with required columns', function () {
    expect(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('user_sessions'))->toBeTrue()
        ->and(Schema::hasTable('api_keys'))->toBeTrue()
        ->and(Schema::hasTable('wallets'))->toBeTrue()
        ->and(Schema::hasTable('wallet_transactions'))->toBeTrue()
        ->and(Schema::hasTable('packages'))->toBeTrue()
        ->and(Schema::hasTable('user_packages'))->toBeTrue()
        ->and(Schema::hasTable('package_orders'))->toBeTrue()
        ->and(Schema::hasTable('user_subscriptions'))->toBeTrue()
        ->and(Schema::hasTable('extra_account_orders'))->toBeTrue()
        ->and(Schema::hasTable('accounts'))->toBeTrue()
        ->and(Schema::hasTable('payment_transactions'))->toBeTrue()
        ->and(Schema::hasTable('api_logs'))->toBeTrue()
        ->and(Schema::hasTable('notifications'))->toBeTrue()
        ->and(Schema::hasTable('user_logs'))->toBeTrue()
        ->and(Schema::hasTable('banks'))->toBeTrue()
        ->and(Schema::hasTable('bank_accounts'))->toBeTrue()
        ->and(Schema::hasTable('bank_transactions'))->toBeTrue()
        ->and(Schema::hasTable('recharge_methods'))->toBeTrue()
        ->and(Schema::hasTable('recharge_method_bank_account'))->toBeTrue()
        ->and(Schema::hasTable('webhooks'))->toBeTrue()
        ->and(Schema::hasTable('webhook_logs'))->toBeTrue()
        ->and(Schema::hasTable('coupons'))->toBeTrue()
        ->and(Schema::hasTable('settings'))->toBeTrue();

    expect(Schema::hasColumns('users', [
        'username',
        'email',
        'phone',
        'full_name',
        'avatar',
        'role',
        'status',
        'referral_code',
        'referred_by',
        'deleted_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('api_logs', [
        'user_id',
        'api_key_id',
        'endpoint',
        'method',
        'request_data',
        'response_data',
        'status_code',
        'response_time_ms',
        'created_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('payment_transactions', [
        'transaction_code',
        'amount',
        'raw_data',
        'status',
    ]))->toBeTrue();

    expect(Schema::hasColumns('wallets', [
        'user_id',
        'type',
        'balance',
        'hold_balance',
        'total_recharge',
        'total_spent',
    ]))->toBeTrue();

    expect(Schema::hasColumns('packages', [
        'duration_days',
        'account_limit',
        'can_buy_extra_account',
        'extra_account_price',
    ]))->toBeTrue();

    expect(Schema::hasColumns('user_subscriptions', [
        'user_id',
        'package_id',
        'order_id',
        'package_name',
        'package_price',
        'base_account_limit',
        'extra_account_limit',
        'used_account',
        'starts_at',
        'expires_at',
        'status',
    ]))->toBeTrue();

    expect(Schema::hasColumns('bank_accounts', [
        'bank_name',
        'account_name',
        'account_number',
        'username',
        'password',
        'token',
        'data_login',
        'proxy',
        'status',
        'last_sync_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('recharge_methods', [
        'code',
        'name',
        'description',
        'badge_label',
        'badge_type',
        'bank_name',
        'account_number',
        'account_name',
        'min_amount',
        'max_amount',
        'bonus_percentage',
        'sort_order',
        'is_active',
        'metadata',
    ]))->toBeTrue();

    expect(Schema::hasColumns('banks', [
        'code',
        'name',
        'short_name',
        'logo',
        'bg_color',
        'is_active',
        'sort_order',
        'metadata',
    ]))->toBeTrue();
});

test('user model keeps backward compatible name access and auto-generates username', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    expect($user->username)->toBe('test')
        ->and($user->full_name)->toBe('Test User')
        ->and($user->name)->toBe('Test User')
        ->and($user->wallet)->not->toBeNull()
        ->and($user->wallet?->type)->toBe('main')
        ->and((string) $user->wallet?->balance)->toBe('0.00');
});

test('user can own multiple wallet types while main wallet remains default', function () {
    $user = User::factory()->create();

    $user->wallets()->create([
        'type' => 'bonus',
        'balance' => 25,
        'hold_balance' => 5,
        'total_recharge' => 25,
        'total_spent' => 0,
    ]);

    expect($user->fresh()->wallets)->toHaveCount(2)
        ->and($user->fresh()->wallet?->type)->toBe('main')
        ->and($user->fresh()->wallets->pluck('type')->sort()->values()->all())->toBe(['bonus', 'main']);
});

test('bank account casts data_login to array', function () {
    $bankAccount = new BankAccount();
    $bankAccount->data_login = [
        'provider' => 'acb',
        'access_token' => 'token-example',
        'refresh_token' => 'refresh-example',
    ];

    expect($bankAccount->data_login)->toBeArray()
        ->and($bankAccount->data_login['provider'])->toBe('acb')
        ->and($bankAccount->data_login['access_token'])->toBe('token-example');
});

test('bank model casts metadata and activation settings', function () {
    $bank = new Bank();
    $bank->metadata = [
        'country' => 'VN',
        'supports_api' => true,
    ];
    $bank->is_active = true;
    $bank->sort_order = 10;

    expect($bank->metadata)->toBeArray()
        ->and($bank->metadata['country'])->toBe('VN')
        ->and($bank->is_active)->toBeTrue()
        ->and($bank->sort_order)->toBeInt();
});
