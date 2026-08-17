<?php

use App\Models\ApiKey;
use App\Models\User;
use App\Support\ApiPermissionCatalog;

function apiKeyPayload(): array
{
    return [
        'name' => 'XemPhatNguoi API v1',
        'permissions' => ApiPermissionCatalog::keys(),
        'ip_whitelist' => ['*'],
    ];
}

it('publishes only the traffic fine permission', function (): void {
    expect(ApiPermissionCatalog::keys())->toBe(['traffic-fines.lookup'])
        ->and(ApiPermissionCatalog::keyed()['traffic-fines.lookup']['endpoints'])->toBe(['GET /api/v1/lookup']);
});

it('allows each user to create only one API credential pair', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/client/api-keys', apiKeyPayload())
        ->assertCreated()
        ->assertJsonPath('status', true)
        ->assertJsonStructure(['data' => ['api_key' => ['id', 'api_key'], 'api_secret']]);

    $this->actingAs($user)
        ->postJson('/api/client/api-keys', apiKeyPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('api_key');

    expect($user->apiKeys()->count())->toBe(1);
});

it('rotates only the secret of the existing API key', function () {
    $user = User::factory()->create();
    $created = $this->actingAs($user)
        ->postJson('/api/client/api-keys', apiKeyPayload())
        ->assertCreated();

    $apiKey = ApiKey::query()->whereBelongsTo($user)->sole();
    $originalKey = $apiKey->api_key;
    $originalSecret = $created->json('data.api_secret');

    $rotated = $this->actingAs($user)
        ->postJson("/api/client/api-keys/{$apiKey->id}/rotate-secret")
        ->assertSuccessful()
        ->assertJsonPath('data.api_key.api_key', $originalKey);

    $apiKey->refresh();

    expect($apiKey->matchesSecret($originalSecret))->toBeFalse()
        ->and($apiKey->matchesSecret($rotated->json('data.api_secret')))->toBeTrue();
});
