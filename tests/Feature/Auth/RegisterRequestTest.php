<?php

use App\Features\Auth\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

test('register request accepts a valid payload', function () {
    $request = new RegisterRequest();

    $validator = Validator::make(
        [
            'username' => 'valid_user',
            'email' => 'valid@example.com',
            'phone' => '0123456789',
            'full_name' => 'Valid User',
            'password' => 'password',
            'password_confirmation' => 'password',
            'accept_terms' => '1',
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeFalse();
});

test('register request rejects duplicated username email and phone', function () {
    User::factory()->create([
        'username' => 'existing_user',
        'email' => 'existing@example.com',
        'phone' => '0999888777',
    ]);

    $request = new RegisterRequest();

    $validator = Validator::make(
        [
            'username' => 'existing_user',
            'email' => 'existing@example.com',
            'phone' => '0999888777',
            'password' => 'password',
            'password_confirmation' => 'password',
            'accept_terms' => '1',
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('username'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('phone'))->toBeTrue();
});

test('register request rejects usernames that look like emails', function () {
    $request = new RegisterRequest();

    $validator = Validator::make(
        [
            'username' => 'user@example.com',
            'email' => 'valid@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'accept_terms' => '1',
        ],
        $request->rules(),
        $request->messages(),
        $request->attributes(),
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('username'))->toBeTrue();
});
