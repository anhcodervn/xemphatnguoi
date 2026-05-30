<?php

use App\Models\User;

test('guests can register through the auth api endpoint', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'accept_terms' => '1',
    ]);

    $response
        ->assertCreated()
        ->assertJson([
            'status' => true,
            'message' => 'Đăng ký thành công.',
            'redirect' => route('auth.login'),
        ]);

    expect(User::where('username', 'testuser')->exists())->toBeTrue();
});
