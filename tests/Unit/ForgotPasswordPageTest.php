<?php

use Illuminate\Support\Facades\Password;
use Tests\TestCase;

uses(TestCase::class);

test('forgot password page renders successfully', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
    $response->assertSee('Quên mật khẩu?');
    $response->assertSee('Gửi liên kết đặt lại mật khẩu');
});

test('forgot password request returns success json response', function () {
    Password::shouldReceive('broker->sendResetLink')
        ->once()
        ->with(['email' => 'user@example.com']);

    $response = $this->postJson(route('auth.forgot-password.submit'), [
        'email' => 'USER@example.com',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.',
        ]);
});

test('reset password page prefills email from reset link query string', function () {
    $response = $this->get(route('password.reset', [
        'token' => 'sample-token',
        'email' => 'user@example.com',
    ]));

    $response->assertOk();
    $response->assertSee('value="user@example.com"', false);
    $response->assertSee('type="hidden" name="email" value="user@example.com"', false);
    $response->assertSee('disabled', false);
});
