<?php

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class);

test('api exception renders json for api routes without accept header', function () {
    $request = Request::create('/api/v1/recharge-orders', 'POST');

    $exception = new ApiException('Dữ liệu không hợp lệ.', 422, [
        'data' => [
            'errors' => [
                'bank_id' => ['Tài khoản ngân hàng là bắt buộc.'],
            ],
        ],
    ]);

    $response = $exception->render($request);

    expect($response)->not()->toBeNull();
    expect($response?->getStatusCode())->toBe(422);
    expect($response?->getData(true))->toMatchArray([
        'status' => false,
        'message' => 'Dữ liệu không hợp lệ.',
    ]);
    expect($response?->getData(true))->toHaveKey('data.errors.bank_id.0');
});
