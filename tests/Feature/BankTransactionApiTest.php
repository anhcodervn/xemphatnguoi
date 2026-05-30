<?php

use App\Features\Client\Bank\Services\AcbService;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

test('client can sync bank transactions and receive updated list', function () {
    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'acb',
        'name' => 'Ngan hang A Chau',
        'short_name' => 'ACB',
        'is_active' => true,
    ]);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'inactive',
    ]);

    BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => 'txn-001',
        'amount' => 100000,
        'description' => 'Mo ta cu',
        'transaction_time' => '2026-05-27 09:00:00',
        'type' => 'credit',
        'raw_data' => ['old' => true],
    ]);

    $acbService = \Mockery::mock(AcbService::class);
    $acbService->shouldReceive('fetchTransactions')
        ->once()
        ->withArgs(fn (BankAccount $account, int $rows = 20, bool $forceRefresh = false) => $account->is($bankAccount) && $rows === 1 && $forceRefresh === false)
        ->andReturn([
            'status' => 'success',
            'message' => 'Lay lich su giao dich thanh cong.',
            'data' => [
                'transactions' => [
                    [
                        'transactionId' => 'txn-001',
                        'amount' => '150000',
                        'description' => 'Mo ta moi',
                        'transactionTime' => '2026-05-27 10:30:00',
                        'type' => 'credit',
                    ],
                    [
                        'transactionId' => 'txn-002',
                        'amount' => '50000',
                        'description' => 'Thanh toan hoa don',
                        'transactionTime' => '2026-05-27 11:15:00',
                        'type' => 'debit',
                    ],
                ],
            ],
        ]);

    $this->app->instance(AcbService::class, $acbService);

    $this->getJson("/api/bank/transaction/{$bankAccount->id}?limit=1")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Lấy lịch sử giao dịch thành công.')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', 'txn-002')
        ->assertJsonPath('data.0.type', 'debit');

    $bankAccount->refresh();

    expect($bankAccount->status)->toBe('active')
        ->and($bankAccount->last_sync_at)->not->toBeNull();

    $updatedTransaction = BankTransaction::query()->where('transaction_id', 'txn-001')->firstOrFail();
    $newTransaction = BankTransaction::query()->where('transaction_id', 'txn-002')->firstOrFail();

    expect((string) $updatedTransaction->amount)->toBe('150000.00')
        ->and($updatedTransaction->description)->toBe('Mo ta moi')
        ->and($updatedTransaction->bank_account_id)->toBe($bankAccount->id)
        ->and((string) $newTransaction->amount)->toBe('50000.00')
        ->and($newTransaction->type)->toBe('debit');
});

test('client bank transactions api validates limit', function () {
    Sanctum::actingAs(User::factory()->create());

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $this->getJson("/api/bank/transaction/{$bankAccount->id}?limit=0")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['limit']);
});

test('client can sync bank transactions with millisecond timestamps from acb payload', function () {
    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'acb',
        'name' => 'Ngan hang A Chau',
        'short_name' => 'ACB',
        'is_active' => true,
    ]);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '16544961',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'inactive',
    ]);

    $acbService = \Mockery::mock(AcbService::class);
    $acbService->shouldReceive('fetchTransactions')
        ->once()
        ->withArgs(fn (BankAccount $account, int $rows = 20, bool $forceRefresh = false) => $account->is($bankAccount) && $forceRefresh === false)
        ->andReturn([
            'status' => 'success',
            'message' => 'Lay lich su giao dich thanh cong.',
            'data' => [
                'transactions' => [
                    [
                        'amount' => 42000,
                        'accountName' => 'TGTT THUONG GIA KHTN -CN VND',
                        'receiverName' => '',
                        'transactionNumber' => 3848,
                        'description' => 'ats717823 GD 6140IBT1cJCGQGFK 200526-08:30:57',
                        'bankName' => '',
                        'isOnline' => false,
                        'postingDate' => 1779210000000,
                        'accountOwner' => 'ADMIN USER',
                        'type' => 'IN',
                        'receiverAccountNumber' => '',
                        'currency' => 'VND',
                        'account' => 16544961,
                        'activeDatetime' => 1779804505000,
                        'effectiveDate' => 1779210000000,
                    ],
                ],
            ],
        ]);

    $this->app->instance(AcbService::class, $acbService);

    $this->getJson("/api/bank/transaction/{$bankAccount->id}")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', '3848_1779804505000');

    $transaction = BankTransaction::query()->firstOrFail();

    expect($transaction->transaction_id)->toBe('3848_1779804505000')
        ->and($transaction->transaction_time?->toDateTimeString())->toBe('2026-05-26 14:08:25')
        ->and($transaction->type)->toBe('credit')
        ->and((string) $transaction->amount)->toBe('42000.00');
});

test('client receives stored transactions during cooldown without calling bank api again', function () {
    Carbon::setTestNow('2026-05-27 12:00:00');

    Sanctum::actingAs(User::factory()->create());

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
        'last_sync_at' => now()->subSeconds(5),
    ]);

    BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => 'txn-cached-001',
        'amount' => 150000,
        'description' => 'Giao dich da luu',
        'transaction_time' => '2026-05-27 11:58:00',
        'type' => 'credit',
        'raw_data' => ['cached' => true],
    ]);

    $originalLastSyncAt = $bankAccount->last_sync_at?->toDateTimeString();

    $this->getJson("/api/bank/transaction/{$bankAccount->id}?limit=1")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', 'txn-cached-001')
        ->assertJsonPath('data.0.description', 'Giao dich da luu');

    $bankAccount->refresh();

    expect($bankAccount->last_sync_at?->toDateTimeString())->toBe($originalLastSyncAt)
        ->and(BankTransaction::query()->count())->toBe(1);

    Carbon::setTestNow();
});

test('client can force refresh bank transactions and bypass cooldown window', function () {
    Carbon::setTestNow('2026-05-27 12:00:00');

    Sanctum::actingAs(User::factory()->create());

    Bank::factory()->create([
        'code' => 'acb',
        'name' => 'Ngan hang A Chau',
        'short_name' => 'ACB',
        'is_active' => true,
    ]);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
        'last_sync_at' => now()->subSeconds(5),
    ]);

    BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => 'txn-cached-001',
        'amount' => 150000,
        'description' => 'Giao dich da luu',
        'transaction_time' => '2026-05-27 11:58:00',
        'type' => 'credit',
        'raw_data' => ['cached' => true],
    ]);

    $acbService = \Mockery::mock(AcbService::class);
    $acbService->shouldReceive('fetchTransactions')
        ->once()
        ->withArgs(fn (BankAccount $account, int $rows = 20, bool $forceRefresh = false) => $account->is($bankAccount) && $rows === 1 && $forceRefresh === true)
        ->andReturn([
            'status' => 'success',
            'message' => 'Lay lich su giao dich thanh cong.',
            'data' => [
                'transactions' => [
                    [
                        'transactionId' => 'txn-live-001',
                        'amount' => '90000',
                        'description' => 'Giao dich moi nhat',
                        'transactionTime' => '2026-05-27 11:59:00',
                        'type' => 'credit',
                    ],
                ],
            ],
        ]);

    $this->app->instance(AcbService::class, $acbService);

    $this->getJson("/api/bank/transaction/{$bankAccount->id}?limit=1&force_refresh=1")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.transaction_id', 'txn-live-001');

    $bankAccount->refresh();

    expect($bankAccount->last_sync_at?->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and(BankTransaction::query()->where('transaction_id', 'txn-live-001')->exists())->toBeTrue();

    Carbon::setTestNow();
});
