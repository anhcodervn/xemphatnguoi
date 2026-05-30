<?php

use App\Features\Client\Bank\Actions\SaveBankAction;
use App\Features\Client\Bank\Services\AcbService;
use App\Models\Bank;
use App\Models\BankAccount;

test('save bank action delegates acb payloads to acb service', function () {
    Bank::factory()->create([
        'code' => 'acb',
        'is_active' => true,
    ]);

    $payload = [
        'bank_code' => 'acb',
        'display_name' => 'ACB chính',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'account_number' => '001122334455',
    ];

    $expectedBankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'ACB chính',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $acbService = \Mockery::mock(AcbService::class);
    $acbService->shouldReceive('saveBank')
        ->once()
        ->with($payload)
        ->andReturn($expectedBankAccount);

    $action = new SaveBankAction($acbService);

    $result = $action->handle($payload);

    expect($result->is($expectedBankAccount))->toBeTrue();
});
