<?php

use App\Features\Admin\Upload\Requests\StoreImageUploadRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

test('image upload request rejects non-image content', function (): void {
    $request = new StoreImageUploadRequest;
    $validator = Validator::make([
        'image' => 'not-an-image',
    ], $request->rules(), $request->messages(), $request->attributes());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('image'))->toBeTrue();
});
