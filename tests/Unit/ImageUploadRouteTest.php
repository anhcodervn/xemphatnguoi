<?php

use Illuminate\Routing\Route;
use Tests\TestCase;

uses(TestCase::class);

test('route upload ảnh yêu cầu auth sanctum', function () {
    /** @var Route|null $route */
    $route = app('router')->getRoutes()->getByName('client/uploads.image.store');

    expect($route)->not->toBeNull();
    expect($route?->uri())->toBe('api/uploads/image');
    expect($route?->methods())->toContain('POST');
    expect($route?->gatherMiddleware())->toContain('auth:sanctum');
});
