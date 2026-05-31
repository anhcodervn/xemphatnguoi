<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Queue::fake();

    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->nullable()->unique();
        $table->string('phone')->nullable()->unique();
        $table->string('full_name')->nullable();
        $table->string('avatar')->nullable();
        $table->string('google_id')->nullable()->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('role')->default('user');
        $table->string('status')->default('active');
        $table->timestamp('last_login_at')->nullable();
        $table->string('last_login_ip', 45)->nullable();
        $table->string('referral_code')->nullable()->unique();
        $table->unsignedBigInteger('referred_by')->nullable();
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('wallets', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Config::set('services.google.client_id', 'google-client-id');
    Config::set('services.google.client_secret', 'google-client-secret');
    Config::set('services.google.redirect', 'http://localhost/auth/google/callback');
});

test('google redirect sends user to google oauth screen', function () {
    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com/o/oauth2/v2/auth');
    expect(session()->has('google_oauth_state'))->toBeTrue();
});

test('google callback creates user and logs them in', function () {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'google-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]),
        'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
            'sub' => 'google-user-123',
            'email' => 'google-user@example.com',
            'name' => 'Google User',
            'picture' => 'https://cdn.example.com/avatar.png',
            'email_verified' => true,
        ]),
    ]);

    $response = $this->withSession([
        'google_oauth_state' => 'valid-google-state',
    ])->get(route('auth.google.callback', [
        'state' => 'valid-google-state',
        'code' => 'google-auth-code',
    ]));

    $response->assertRedirect('/');

    $user = User::query()->where('email', 'google-user@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user?->google_id)->toBe('google-user-123');
    expect($user?->full_name)->toBe('Google User');
    expect($user?->avatar)->toBe('https://cdn.example.com/avatar.png');
    expect($user?->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('google callback rejects invalid state', function () {
    $response = $this->withSession([
        'google_oauth_state' => 'expected-state',
    ])->get(route('auth.google.callback', [
        'state' => 'invalid-state',
        'code' => 'google-auth-code',
    ]));

    $response->assertRedirect(route('auth.login'));
    $response->assertSessionHas('auth_google_error');
    $this->assertGuest();
});
