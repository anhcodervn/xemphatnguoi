<?php

use App\Models\BankAccount;
use App\Models\RechargeMethod;
use App\Models\Webhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('recharge_methods');
    Schema::dropIfExists('webhooks');
    Schema::dropIfExists('bank_accounts');

    Schema::create('bank_accounts', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('bank_name');
        $table->string('account_name');
        $table->string('account_number');
        $table->text('username')->nullable();
        $table->text('password')->nullable();
        $table->text('token')->nullable();
        $table->text('data_login')->nullable();
        $table->text('proxy')->nullable();
        $table->string('status')->default('active');
        $table->timestamp('last_sync_at')->nullable();
        $table->timestamps();
    });

    Schema::create('webhooks', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('bank_account_id')->nullable();
        $table->string('name')->nullable();
        $table->string('url');
        $table->text('secret_key')->nullable();
        $table->string('event_keyword')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('recharge_methods', function ($table): void {
        $table->id();
        $table->string('code')->unique();
        $table->string('name');
        $table->text('secret_key')->nullable();
        $table->string('badge_type')->default('manual');
        $table->decimal('min_amount', 16, 2)->default(0);
        $table->decimal('max_amount', 16, 2)->default(0);
        $table->unsignedInteger('bonus_percentage')->default(0);
        $table->unsignedInteger('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->json('metadata')->nullable();
        $table->timestamps();
    });
});

test('security re-encrypt command rewrites legacy bank payloads and webhook secrets', function () {
    $legacyUsername = legacyEncodeBankValue('legacy-user');
    $legacyPassword = legacyEncodeBankValue('legacy-password');
    $legacyLoginPayload = legacyEncodeBankJson([
        'sessionId' => 'session-123',
        'browserToken' => 'browser-token',
    ]);

    $bankAccount = new BankAccount;
    $bankAccount->timestamps = false;
    $bankAccount->setRawAttributes([
        'id' => 1,
        'user_id' => null,
        'bank_name' => 'acb',
        'account_name' => 'Legacy Account',
        'account_number' => '123456789',
        'username' => $legacyUsername,
        'password' => $legacyPassword,
        'token' => null,
        'data_login' => $legacyLoginPayload,
        'proxy' => null,
        'status' => 'active',
        'last_sync_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bankAccount->save();

    DB::table('webhooks')->insert([
        'id' => 1,
        'name' => 'Legacy Webhook',
        'url' => 'https://example.com/webhook',
        'secret_key' => 'plain-legacy-secret',
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $webhook = Webhook::query()->findOrFail(1);

    DB::table('recharge_methods')->insert([
        'id' => 1,
        'code' => 'legacy-method',
        'name' => 'Legacy Method',
        'secret_key' => 'plain-recharge-secret',
        'badge_type' => 'manual',
        'min_amount' => 0,
        'max_amount' => 0,
        'bonus_percentage' => 0,
        'sort_order' => 0,
        'is_active' => 1,
        'metadata' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rechargeMethod = RechargeMethod::query()->findOrFail(1);

    $rawUsernameBefore = $bankAccount->fresh()->getRawOriginal('username');
    $rawSecretBefore = $webhook->fresh()->getRawOriginal('secret_key');
    $rawRechargeSecretBefore = $rechargeMethod->fresh()->getRawOriginal('secret_key');

    $this->artisan('security:reencrypt-sensitive-data')
        ->assertSuccessful();

    $bankAccount->refresh();
    $webhook->refresh();

    expect($rawUsernameBefore)->toContain('"v":"v2"')
        ->and($bankAccount->getRawOriginal('username'))->toContain('"v":"v3"')
        ->and($bankAccount->getRawOriginal('username'))->not->toBe($rawUsernameBefore)
        ->and($bankAccount->username)->toBe('legacy-user')
        ->and($bankAccount->password)->toBe('legacy-password')
        ->and($bankAccount->data_login)->toBe([
            'sessionId' => 'session-123',
            'browserToken' => 'browser-token',
        ])
        ->and($rawSecretBefore)->toBe('plain-legacy-secret')
        ->and($webhook->getRawOriginal('secret_key'))->not->toBe('plain-legacy-secret')
        ->and($webhook->secret_key)->toBe('plain-legacy-secret')
        ->and($rawRechargeSecretBefore)->toBe('plain-recharge-secret')
        ->and($rechargeMethod->fresh()->getRawOriginal('secret_key'))->not->toBe('plain-recharge-secret')
        ->and($rechargeMethod->fresh()->secret_key)->toBe('plain-recharge-secret');
});

test('security re-encrypt command supports pretend mode', function () {
    $bankAccount = new BankAccount;
    $bankAccount->timestamps = false;
    $bankAccount->setRawAttributes([
        'id' => 1,
        'user_id' => null,
        'bank_name' => 'acb',
        'account_name' => 'Pretend Account',
        'account_number' => '123456789',
        'username' => legacyEncodeBankValue('pretend-user'),
        'password' => null,
        'token' => null,
        'data_login' => null,
        'proxy' => null,
        'status' => 'active',
        'last_sync_at' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bankAccount->save();

    $rawBefore = $bankAccount->fresh()->getRawOriginal('username');

    $this->artisan('security:reencrypt-sensitive-data', ['--pretend' => true])
        ->assertSuccessful();

    expect($bankAccount->fresh()->getRawOriginal('username'))->toBe($rawBefore);
});

function legacyEncodeBankValue(string $value): string
{
    $cipher = 'AES-256-CBC';
    $key = hash('sha256', 'e1af337ba9f96d15e66e71c4c14156e80cef73602b8c51572203f77b76999809', true);
    $iv = random_bytes(openssl_cipher_iv_length($cipher));
    $cipherText = openssl_encrypt($value, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    expect($cipherText)->not->toBeFalse();

    $ivEncoded = base64_encode($iv);
    $cipherEncoded = base64_encode((string) $cipherText);
    $mac = hash_hmac('sha256', $ivEncoded.'.'.$cipherEncoded, $key);

    return 'bank:enc:'.json_encode([
        'v' => 'v2',
        'iv' => $ivEncoded,
        'value' => $cipherEncoded,
        'mac' => $mac,
    ], JSON_UNESCAPED_SLASHES);
}

function legacyEncodeBankJson(array $payload): string
{
    $encoded = [];

    foreach ($payload as $key => $value) {
        $encoded[$key] = legacyEncodeBankValue((string) $value);
    }

    return json_encode(
        $encoded,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
    );
}
