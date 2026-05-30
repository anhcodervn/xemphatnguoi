<?php

use App\Models\RechargeMethod;
use App\Models\Setting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('admin can update and view recharge syntax in option settings', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $payload = [
        'terms_of_use' => [],
        'privacy_policy' => [],
        'refund_policy' => [],
        'recharge_syntax' => 'PAY',
    ];

    $this->actingAs($admin)
        ->patchJson('/admin-api/settings/options', $payload)
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.settings.recharge_syntax', 'PAY');

    $this->actingAs($admin)
        ->getJson('/admin-api/settings/options')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.settings.recharge_syntax', 'PAY');

    $this->assertDatabaseHas('settings', [
        'key' => 'recharge_syntax',
        'value' => 'PAY',
        'type' => 'string',
    ]);

    expect(Setting::query()->where('key', 'options')->first()?->value)->toContain('PAY');
});

test('recharge overview and created order use recharge syntax from settings', function () {
    $user = User::factory()->create();

    Setting::query()->updateOrCreate(
        ['key' => 'recharge_syntax'],
        ['value' => 'CK', 'type' => 'string'],
    );

    RechargeMethod::factory()->create([
        'code' => 'vietcombank',
        'name' => 'Vietcombank',
        'bank_name' => 'Vietcombank',
        'account_number' => '9363449824',
        'account_name' => 'NGUYEN TUAN ANH',
        'min_amount' => 10000,
        'max_amount' => 50000000,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/recharge')
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.recharge_syntax', 'CK')
        ->assertJsonPath('data.transfer_content_preview', 'CK'.$user->id);

    $this->postJson('/api/recharge/orders', [
        'method' => 'vietcombank',
        'amount' => 50000,
    ])
        ->assertOk()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.transfer_content', 'CK'.$user->id);

    $this->assertDatabaseHas('recharge_orders', [
        'user_id' => $user->id,
        'transfer_content' => 'CK'.$user->id,
    ]);
});
