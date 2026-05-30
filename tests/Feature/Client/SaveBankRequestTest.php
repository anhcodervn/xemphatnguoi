<?php

use App\Features\Client\Bank\Requests\SaveBankRequest;
use App\Models\Bank;
use Illuminate\Support\Facades\Validator;

test('save bank request accepts a valid payload', function () {
    Bank::factory()->create([
        'code' => 'vcb',
        'is_active' => true,
    ]);

    $request = new SaveBankRequest();

    $validator = Validator::make(
        [
            'bank_code' => 'vcb',
            'display_name' => 'Tài khoản chính',
            'username' => 'vcb_user_01',
            'password' => 'secret-password',
            'account_number' => '001122334455',
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeFalse();
});

test('save bank request rejects inactive bank codes', function () {
    Bank::factory()->create([
        'code' => 'vcb',
        'is_active' => false,
    ]);

    $request = new SaveBankRequest();

    $validator = Validator::make(
        [
            'bank_code' => 'vcb',
            'display_name' => 'Tài khoản chính',
            'username' => 'vcb_user_01',
            'password' => 'secret-password',
            'account_number' => '001122334455',
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('bank_code'))->toBeTrue();
});

test('save bank request rejects invalid account number and short display name', function () {
    Bank::factory()->create([
        'code' => 'vcb',
        'is_active' => true,
    ]);

    $request = new SaveBankRequest();

    $validator = Validator::make(
        [
            'bank_code' => 'vcb',
            'display_name' => 'A',
            'username' => 'ab',
            'password' => 'secret-password',
            'account_number' => '12A 34',
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('display_name'))->toBeTrue()
        ->and($validator->errors()->has('username'))->toBeTrue()
        ->and($validator->errors()->has('account_number'))->toBeTrue();
});
