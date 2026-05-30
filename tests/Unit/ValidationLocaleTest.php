<?php

use App\Features\Client\ApiKey\Requests\StoreApiKeyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('application validation locale defaults to vietnamese', function () {
    expect(config('app.locale'))->toBe('vi');
    expect(trans('validation.required', ['attribute' => 'email']))->toBe('email là bắt buộc.');
});

test('api key request validation messages are returned in vietnamese', function () {
    $request = new StoreApiKeyRequest();
    $validator = Validator::make(
        [
            'permissions' => ['profile.read'],
            'ip_whitelist' => ['not-an-ip'],
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('name'))->toBe('tên API key là bắt buộc.');
    expect($validator->errors()->first('ip_whitelist.0'))->toBe('Danh sách IP cho phép phải là địa chỉ IP hợp lệ hoặc ký tự *.');
});
