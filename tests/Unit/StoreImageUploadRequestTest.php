<?php

use App\Features\Client\Upload\Requests\StoreImageUploadRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('request upload ảnh validate bắt buộc là file ảnh', function () {
    $request = new StoreImageUploadRequest;

    $validator = Validator::make([
        'image' => 'not-an-image',
    ], $request->rules(), $request->messages(), $request->attributes());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('image'))->toBeTrue();
});
