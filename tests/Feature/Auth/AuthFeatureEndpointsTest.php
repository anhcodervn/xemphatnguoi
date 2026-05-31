<?php

use App\Jobs\SaveUserLogJob;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;

test('users can log in through the auth web submit endpoint', function () {
    Queue::fake();

    $user = User::factory()->create([
        'username' => 'loginuser',
        'email' => 'login@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson(route('auth.login.submit'), [
        'login' => 'login@example.com',
        'password' => 'password',
        'remember' => true,
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Đăng nhập thành công.',
            'redirect' => url('/'),
        ]);

    $this->assertAuthenticatedAs($user);
    $this->get('/')->assertOk();

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'login');
});

test('session authenticated users can retrieve the current user through api user', function () {
    $user = User::factory()->create([
        'username' => 'statefuluser',
        'email' => 'stateful@example.com',
        'password' => 'password',
    ]);

    $this->postJson(route('auth.login.submit'), [
        'login' => 'stateful@example.com',
        'password' => 'password',
    ])->assertOk();

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email)
        ->assertJsonPath('wallet.type', 'main')
        ->assertJsonPath('wallet.balance', '0.00')
        ->assertJsonMissingPath('password');
});

test('users can log in through the auth api endpoint and receive a sanctum token', function () {
    Queue::fake();

    $user = User::factory()->create([
        'username' => 'api_login_user',
        'email' => 'api-login@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'login' => 'api_login_user',
        'password' => 'password',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Đăng nhập thành công.',
            'token_type' => 'Bearer',
        ])
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.wallet.type', 'main')
        ->assertJsonPath('user.wallet.balance', '0.00');

    expect(PersonalAccessToken::query()->count())->toBe(1)
        ->and($response->json('access_token'))->not->toBeEmpty();

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user->id && $job->action === 'api_login');
});

test('login rejects invalid credentials under the shared login field', function () {
    User::factory()->create([
        'username' => 'wrongtarget',
        'email' => 'wrongtarget@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/auth/login', [
        'login' => 'wrongtarget',
        'password' => 'invalid-password',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['login']);
});

test('forgot password demo returns a preview reset url for known users', function () {
    $user = User::factory()->create([
        'email' => 'forgot@example.com',
    ]);

    $response = $this->postJson(route('auth.forgot-password.submit'), [
        'email' => $user->email,
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
        ]);

    expect($response->json('preview_reset_url'))
        ->toBeString()
        ->toContain('/reset-password/');
});

test('users can register through the auth web submit endpoint', function () {
    Queue::fake();

    $response = $this->postJson(route('auth.register.submit'), [
        'name' => 'Blade User',
        'username' => 'bladeuser',
        'email' => 'blade@example.com',
        'phone' => '0911222333',
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

    $user = User::where('email', 'blade@example.com')->first();

    expect($user)->not->toBeNull()
        ->and(Wallet::where('user_id', $user?->id)->exists())->toBeTrue()
        ->and($response->json('user.wallet.type'))->toBe('main')
        ->and($response->json('user.wallet.balance'))->toBe('0.00');

    Queue::assertPushed(SaveUserLogJob::class, fn (SaveUserLogJob $job): bool => $job->userId === $user?->id && $job->action === 'register');
});
