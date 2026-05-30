<?php

use App\Features\Api\V1\Requests\ListBankTransactionsRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('list bank transactions request returns api json response on failed validation', function () {
    $request = new ListBankTransactionsRequest();
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
