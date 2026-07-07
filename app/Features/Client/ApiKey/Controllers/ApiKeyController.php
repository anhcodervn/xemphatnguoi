<?php

namespace App\Features\Client\ApiKey\Controllers;

use App\Features\Client\ApiKey\Requests\StoreApiKeyRequest;
use App\Features\Client\ApiKey\Requests\UpdateApiKeyRequest;
use App\Features\Client\ApiKey\Resources\ApiKeyResource;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\ApiPermissionCatalog;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $keys = $user->apiKeys()
            ->with(['subscription:id,package_id,package_name,expires_at,status'])
            ->withCount('logs')
            ->orderByRaw("case when key_type = 'wallet' then 0 else 1 end")
            ->latest('id')
            ->get();

        return response()->json(ApiResponse::success(data: [
            'data' => ApiKeyResource::collection($keys)->resolve(),
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $keys->count(),
                'total' => $keys->count(),
            ],
            'permissions' => ApiPermissionCatalog::all(),
        ]));
    }

    public function permissions(Request $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'permissions' => ApiPermissionCatalog::all(),
            'note' => 'API key ví dùng để gọi captcha API V1 và trừ trực tiếp số dư ví. API key gói được tạo tự động khi mua gói.',
        ]));
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $user = $this->user($request);
        $credentials = ApiKey::generateCredentials();

        $apiKey = $user->apiKeys()->create([
            'key_type' => ApiKey::TYPE_WALLET,
            'user_subscription_id' => null,
            'name' => $request->validated('name'),
            'api_key' => $credentials['api_key'],
            'api_secret_hash' => Hash::make($credentials['api_secret']),
            'api_secret_encrypted' => $credentials['api_secret'],
            'permissions' => $request->validated('permissions'),
            'ip_whitelist' => $request->validated('ip_whitelist', []),
            'expired_at' => $request->validated('expired_at'),
            'status' => ApiKey::STATUS_ACTIVE,
        ]);

        return response()->json(ApiResponse::success(
            message: 'Tạo API key thành công.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey->load(['subscription'])->loadCount('logs'))->resolve(),
                'api_secret' => $credentials['api_secret'],
                'permission_catalog' => ApiPermissionCatalog::all(),
            ],
        ), 201);
    }

    public function update(UpdateApiKeyRequest $request, ApiKey $apiKey): JsonResponse
    {
        $apiKey = $this->ownedKey($apiKey, $this->user($request));

        abort_if($apiKey->isPackageKey(), 422, 'API key gói được tạo tự động và không hỗ trợ sửa thủ công.');

        $apiKey->fill($request->validated());
        $apiKey->save();

        return response()->json(ApiResponse::success(
            message: 'Cập nhật API key thành công.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey->fresh()->load(['subscription'])->loadCount('logs'))->resolve(),
            ],
        ));
    }

    public function rotateSecret(ApiKey $apiKey, Request $request): JsonResponse
    {
        $apiKey = $this->ownedKey($apiKey, $this->user($request));
        $credentials = ApiKey::generateCredentials();

        $apiKey->forceFill([
            'api_secret_hash' => Hash::make($credentials['api_secret']),
            'api_secret_encrypted' => $credentials['api_secret'],
            'status' => ApiKey::STATUS_ACTIVE,
        ])->save();

        return response()->json(ApiResponse::success(
            message: 'Đổi API secret thành công.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey->fresh()->load(['subscription'])->loadCount('logs'))->resolve(),
                'api_secret' => $credentials['api_secret'],
            ],
        ));
    }

    private function ownedKey(ApiKey $apiKey, User $user): ApiKey
    {
        abort_unless($apiKey->user_id === $user->id, 404);

        return $apiKey;
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
