<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('api:prune-logs')
    ->daily()
    ->withoutOverlapping();

Schedule::command('traffic-fines:dispatch-monitoring-checks')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('monitoring-subscriptions:renew')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
