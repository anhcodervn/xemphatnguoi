<?php

use App\Models\Coupon;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('coupon is started when starts_at is exactly now', function () {
    Carbon::setTestNow($now = Carbon::parse('2026-06-02 10:00:00'));

    $coupon = new Coupon([
        'starts_at' => $now->copy(),
    ]);

    expect($coupon->isStarted())->toBeTrue();

    Carbon::setTestNow();
});
