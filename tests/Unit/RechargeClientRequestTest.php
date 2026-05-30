<?php

use App\Features\Api\V1\Requests\StoreRechargeClientOrderRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('store recharge client order request requires bank id and amount in vietnamese', function () {
    $request = new StoreRechargeClientOrderRequest();
    $validator = Validator::make([], $request->rules(), $request->messages(), $request->attributes());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('bank_id'))->toBe('Tài khoản ngân hàng là bắt buộc.');
    expect($validator->errors()->first('amount'))->toBe('số tiền nạp là bắt buộc.');
});

test('store recharge client order request returns api json response on failed validation', function () {
    $request = new StoreRechargeClientOrderRequest();
    $validator = Validator::make([], $request->rules(), $request->messages(), $request->attributes());

    expect($validator->fails())->toBeTrue();

    $method = new ReflectionMethod($request, 'failedValidation');
    $method->setAccessible(true);

    try {
        $method->invoke($request, $validator);

        $this->fail('Expected HttpResponseException was not thrown.');
    } catch (\Throwable $exception) {
        expect($exception)->toBeInstanceOf(HttpResponseException::class);

        /** @var HttpResponseException $exception */
        $response = $exception->getResponse();

        expect($response->getStatusCode())->toBe(422);
        expect($response->getData(true))->toMatchArray([
            'status' => false,
            'message' => 'Tài khoản ngân hàng là bắt buộc.',
        ]);
        expect($response->getData(true))->toHaveKey('data.errors.bank_id.0');
    }
});
