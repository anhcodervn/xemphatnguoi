<?php

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

test('authenticated user can logout through json endpoint', function () {
    $user = new User();
    $user->forceFill([
        'id' => 999,
        'username' => 'demo_user',
        'email' => 'demo@example.com',
    ]);

    $this->actingAs($user);

    $response = $this->postJson(route('logout'));

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Đăng xuất thành công.',
            'redirect' => route('login'),
        ]);

    $this->assertGuest();
});
