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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $keys = $user->apiKeys()
            ->withCount('logs')
            ->latest('id')
            ->limit(1)
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
            'note' => 'API key dùng để gọi API tra cứu phạt nguội V1.',
        ]));
    }

    public function store(StoreApiKeyRequest $request): JsonResponse
    {
        $user = $this->user($request);
        [$apiKey, $credentials] = DB::transaction(function () use ($request, $user): array {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->apiKeys()->exists()) {
                throw ValidationException::withMessages([
                    'api_key' => 'Mỗi tài khoản chỉ được sử dụng một cặp API Key và API Secret.',
                ]);
            }

            $credentials = ApiKey::generateCredentials();
            $apiKey = $lockedUser->apiKeys()->create([
                'key_type' => ApiKey::TYPE_WALLET,
                'name' => $request->validated('name'),
                'api_key' => $credentials['api_key'],
                'api_secret_hash' => Hash::make($credentials['api_secret']),
                'api_secret_encrypted' => $credentials['api_secret'],
                'permissions' => $request->validated('permissions'),
                'ip_whitelist' => $request->validated('ip_whitelist', []),
                'expired_at' => $request->validated('expired_at'),
                'status' => ApiKey::STATUS_ACTIVE,
            ]);

            return [$apiKey, $credentials];
        });

        return response()->json(ApiResponse::success(
            message: 'Tạo API key thành công.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey->loadCount('logs'))->resolve(),
                'api_secret' => $credentials['api_secret'],
                'permission_catalog' => ApiPermissionCatalog::all(),
            ],
        ), 201);
    }

    public function update(UpdateApiKeyRequest $request, ApiKey $apiKey): JsonResponse
    {
        $apiKey = $this->ownedKey($apiKey, $this->user($request));

        $apiKey->fill($request->validated());
        $apiKey->save();

        return response()->json(ApiResponse::success(
            message: 'Cập nhật API key thành công.',
            data: [
                'api_key' => ApiKeyResource::make($apiKey->fresh()->loadCount('logs'))->resolve(),
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
                'api_key' => ApiKeyResource::make($apiKey->fresh()->loadCount('logs'))->resolve(),
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
