<?php

use App\Models\Bank;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('client bank api returns only active banks ordered by sort order', function () {
    Sanctum::actingAs(User::factory()->create());

    $firstBank = Bank::factory()->create([
        'code' => 'acb',
        'name' => 'ACB',
        'sort_order' => 10,
        'is_active' => true,
    ]);

    $secondBank = Bank::factory()->create([
        'code' => 'vcb',
        'name' => 'Vietcombank',
        'sort_order' => 20,
        'is_active' => true,
    ]);

    Bank::factory()->create([
        'code' => 'hidden-bank',
        'name' => 'Hidden Bank',
        'sort_order' => 5,
        'is_active' => false,
    ]);

    $this->getJson('/api/bank')
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', $firstBank->code)
        ->assertJsonPath('data.1.code', $secondBank->code);
});
