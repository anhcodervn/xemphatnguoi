<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Tests\TestCase;

uses(TestCase::class);

test('image upload route is registered for authenticated admins', function (): void {
    /** @var Route|null $route */
    $route = app('router')->getRoutes()->getByName('admin.uploads.image.store');

    expect($route)->not->toBeNull()
        ->and($route?->uri())->toBe('api/uploads/image')
        ->and($route?->methods())->toContain('POST')
        ->and($route?->gatherMiddleware())->toContain('auth:sanctum', 'admin', 'throttle:30,1');

    $this->postJson('/api/uploads/image')->assertUnauthorized();
});

test('image upload route rejects non-admin users and invalid files', function (): void {
    $regularUser = new User;
    $regularUser->forceFill(['role' => 'user']);

    $this->actingAs($regularUser)
        ->postJson('/api/uploads/image', [
            'image' => UploadedFile::fake()->create('payload.txt', 1, 'text/plain'),
        ])
        ->assertForbidden();

    $admin = new User;
    $admin->forceFill(['role' => 'admin']);

    $this->actingAs($admin)
        ->postJson('/api/uploads/image', [
            'image' => UploadedFile::fake()->create('payload.txt', 1, 'text/plain'),
        ])
        ->assertUnprocessable()
        ->assertJsonPath('status', false);
});
