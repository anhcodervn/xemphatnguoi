<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

test('client web notifications placeholder route is not registered', function () {
    expect(Route::has('client/notifications.index'))->toBeFalse();
});

test('client cannot create notifications through api route', function () {
    $this->postJson('/api/notifications')
        ->assertStatus(405);
});
