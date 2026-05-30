<?php

namespace App\Features\Client\ApiKey\Controllers;

use App\Features\Client\ApiKey\Requests\ApiKeyIndexRequest;
use App\Features\Client\ApiKey\Requests\StoreApiKeyRequest;
use App\Features\Client\ApiKey\Requests\UpdateApiKeyRequest;
use App\Features\Client\ApiKey\Resources\ApiKeyResource;
use App\Features\Client\ApiKey\Services\ApiKeyService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\ApiPermissionCatalog;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiKeyController extends Controller
{
    public function index(ApiKeyIndexRequest $request, ApiKeyService $apiKeyService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json(ApiResponse::success(data: $apiKeyService->paginate($user, $request->validated())));
    }

    public function store(StoreApiKeyRequest $request, ApiKeyService $apiKeyService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $result = $apiKeyService->create($user, $request->validated());

        return response()->json(ApiResponse::success(
            message: 'API key created successfully.',
            data: [
                'api_key' => ApiKeyResource::make($result['api_key'])->resolve(),
                'api_secret' => $result['plain_secret'],
                'permission_catalog' => ApiPermissionCatalog::selfService(),
            ],
        ), 201);
    }

    public function update(UpdateApiKeyRequest $request, ApiKey $apiKey, ApiKeyService $apiKeyService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $this->ensureOwnership($user, $apiKey);

        $apiKey = $apiKeyService->update($apiKey, $request->validated());

        return response()->json(ApiResponse::success(
            message: 'API key updated successfully.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey)->resolve(),
            ],
        ));
    }

    public function destroy(Request $request, ApiKey $apiKey, ApiKeyService $apiKeyService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $this->ensureOwnership($user, $apiKey);

        $apiKey = $apiKeyService->revoke($apiKey);

        return response()->json(ApiResponse::success(
            message: 'API key revoked successfully.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey)->resolve(),
            ],
        ));
    }

    public function rotateSecret(Request $request, ApiKey $apiKey, ApiKeyService $apiKeyService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $this->ensureOwnership($user, $apiKey);

        $result = $apiKeyService->rotateSecret($apiKey);

        return response()->json(ApiResponse::success(
            message: 'API credentials rotated successfully.',
            data: [
                'api_key' => ApiKeyResource::make($result['api_key'])->resolve(),
                'api_secret' => $result['plain_secret'],
            ],
        ));
    }

    public function logs(Request $request, ApiKey $apiKey, ApiKeyService $apiKeyService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $this->ensureOwnership($user, $apiKey);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status_code' => ['nullable', 'integer', 'between:100,599'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json(ApiResponse::success(data: $apiKeyService->logs($apiKey, $validated)));
    }

    public function permissions(): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'permissions' => ApiPermissionCatalog::selfService(),
            'note' => 'Only V1 permissions can be self-assigned here. Future V2 / V3 permissions must be granted by admin.',
        ]));
    }

    private function ensureOwnership(User $user, ApiKey $apiKey): void
    {
        if ((int) $apiKey->user_id !== (int) $user->id) {
            throw new NotFoundHttpException();
        }
    }
}
