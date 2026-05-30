<?php

use App\Exceptions\ApiException;
use App\Features\Client\Bank\Services\AcbService;
use App\Models\BankAccount;

test('acb service stores bank account when login succeeds', function () {
    /** @var AcbService&\Mockery\MockInterface $service */
    $service = \Mockery::mock(AcbService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('login')
        ->once()
        ->andReturn([
            'status' => 'success',
            'message' => 'Dang nhap thanh cong',
            'data' => [],
        ]);

    $bankAccount = $service->saveBank([
        'bank_code' => 'acb',
        'display_name' => 'ACB chính',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'account_number' => '001122334455',
    ]);

    expect($bankAccount->bank_name)->toBe('acb')
        ->and($bankAccount->account_name)->toBe('ACB chính')
        ->and($bankAccount->username)->toBe('acb_user')
        ->and($bankAccount->status)->toBe('active');

    $this->assertDatabaseHas('bank_accounts', [
        'bank_name' => 'acb',
        'account_name' => 'ACB chính',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'status' => 'active',
    ]);
});

test('acb service rolls back saved bank account when login fails', function () {
    /** @var AcbService&\Mockery\MockInterface $service */
    $service = \Mockery::mock(AcbService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('login')
        ->once()
        ->andReturn([
            'status' => 'error',
            'message' => 'Dang nhap that bai',
            'data' => [],
        ]);

    expect(fn () => $service->saveBank([
        'bank_code' => 'acb',
        'display_name' => 'ACB chính',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'account_number' => '001122334455',
    ]))->toThrow(ApiException::class, 'Dang nhap that bai');

    expect(BankAccount::query()->count())->toBe(0);
});
