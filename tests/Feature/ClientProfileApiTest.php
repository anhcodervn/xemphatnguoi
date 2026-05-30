<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

test('authenticated user can view profile overview', function () {
    $user = User::factory()->create([
        'full_name' => 'Nguyen Van A',
        'email' => 'user@example.com',
        'phone' => '0909123456',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.full_name', 'Nguyen Van A')
        ->assertJsonPath('data.email', 'user@example.com')
        ->assertJsonPath('data.security.has_2fa', false);
});

test('authenticated user can update profile and activity log is recorded', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->patchJson('/api/profile', [
        'full_name' => 'Tran Thi B',
        'email' => 'updated@example.com',
        'phone' => '0988111222',
        'avatar' => 'https://cdn.test/avatar.png',
        'username' => 'tranthib',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.full_name', 'Tran Thi B')
        ->assertJsonPath('data.email', 'updated@example.com')
        ->assertJsonPath('data.avatar', 'https://cdn.test/avatar.png');

    $user->refresh();

    expect($user->full_name)->toBe('Tran Thi B')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->phone)->toBe('0988111222')
        ->and($user->username)->toBe('tranthib')
        ->and($user->email_verified_at)->toBeNull();

    $this->assertDatabaseHas('user_logs', [
        'user_id' => $user->id,
        'action' => 'profile_update',
    ]);
});

test('authenticated user can update password and record user log', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->putJson('/api/profile/password', [
        'current_password' => 'password',
        'password' => 'NewPassword123',
        'password_confirmation' => 'NewPassword123',
        'logout_other_devices' => false,
    ])
        ->assertOk()
        ->assertJsonPath('status', true);

    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue();

    $this->assertDatabaseHas('user_logs', [
        'user_id' => $user->id,
        'action' => 'password_change',
    ]);
});

test('authenticated user can logout other devices', function () {
    $user = User::factory()->create();
    $user->createToken('device-1');
    $user->createToken('device-2');

    $user->userSessions()->create([
        'token' => 'current-session',
        'ip' => '127.0.0.1',
        'user_agent' => 'Symfony',
        'last_activity_at' => now(),
    ]);

    $user->userSessions()->create([
        'token' => 'other-session',
        'ip' => '10.10.10.10',
        'user_agent' => 'Mozilla/5.0 Chrome',
        'last_activity_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/profile/logout-other-devices', [
        'current_password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Đã đăng xuất các thiết bị khác.');

    $this->assertDatabaseHas('user_logs', [
        'user_id' => $user->id,
        'action' => 'logout_other_devices',
    ]);

    expect($user->tokens()->count())->toBe(0)
        ->and($user->userSessions()->count())->toBe(1)
        ->and($user->userSessions()->first()?->token)->toBe('current-session');
});

test('authenticated user can filter user logs', function () {
    $user = User::factory()->create();

    $user->userLogs()->create([
        'action' => 'login',
        'description' => 'Đăng nhập tài khoản',
        'ip' => '1.1.1.1',
        'user_agent' => 'Mozilla/5.0 Chrome',
    ]);

    $user->userLogs()->create([
        'action' => 'profile_update',
        'description' => 'Cập nhật hồ sơ',
        'ip' => '2.2.2.2',
        'user_agent' => 'Mozilla/5.0 Safari',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/profile/user-logs?action=login&search=1.1.1.1')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.action', 'login')
        ->assertJsonPath('data.data.0.browser', 'Chrome');
});

test('authenticated user can filter wallet transactions', function () {
    $user = User::factory()->create();
    $wallet = $user->wallet()->firstOrFail();

    $wallet->transactions()->create([
        'type' => 'credit',
        'amount' => 500000,
        'balance_before' => 0,
        'balance_after' => 500000,
        'description' => 'Nạp tiền qua ngân hàng',
        'status' => 'success',
    ]);

    $wallet->transactions()->create([
        'type' => 'adjustment',
        'amount' => 50000,
        'balance_before' => 500000,
        'balance_after' => 550000,
        'description' => 'Thưởng bonus nạp tiền',
        'status' => 'success',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/profile/wallet-transactions?type=bonus&search=bonus')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.type', 'bonus')
        ->assertJsonPath('data.data.0.status', 'success');
});
