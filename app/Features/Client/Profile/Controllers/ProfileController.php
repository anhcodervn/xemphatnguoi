<?php

namespace App\Features\Client\Profile\Controllers;

use App\Features\Client\Profile\Actions\LogoutOtherDevicesAction;
use App\Features\Client\Profile\Actions\UpdatePasswordAction;
use App\Features\Client\Profile\Actions\UpdateProfileAction;
use App\Features\Client\Profile\Requests\LogoutOtherDevicesRequest;
use App\Features\Client\Profile\Requests\UpdatePasswordRequest;
use App\Features\Client\Profile\Requests\UpdateProfileRequest;
use App\Features\Client\Profile\Requests\UserLogIndexRequest;
use App\Features\Client\Profile\Requests\WalletTransactionIndexRequest;
use App\Features\Client\Profile\Resources\ProfileResource;
use App\Features\Client\Profile\Services\ProfileService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request, ProfileService $profileService): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'status' => true,
            'data' => ProfileResource::make($profileService->profile($user))->resolve(),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $action, ProfileService $profileService): JsonResponse
    {
        $user = $this->user($request);
        $updatedUser = $action->handle($user, $request->validated(), $request);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thông tin tài khoản thành công.',
            'data' => ProfileResource::make($profileService->profile($updatedUser))->resolve(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request, UpdatePasswordAction $action): JsonResponse
    {
        $user = $this->user($request);
        $action->handle($user, $request->validated(), $request);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật mật khẩu thành công.',
        ]);
    }

    public function logoutOtherDevices(LogoutOtherDevicesRequest $request, LogoutOtherDevicesAction $action): JsonResponse
    {
        $user = $this->user($request);
        $action->handle($user, $request->validated(), $request);

        return response()->json([
            'status' => true,
            'message' => 'Đã đăng xuất các thiết bị khác.',
        ]);
    }

    public function userLogs(UserLogIndexRequest $request, ProfileService $profileService): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'status' => true,
            'data' => $profileService->userLogs($user, $request->validated()),
        ]);
    }

    public function walletTransactions(WalletTransactionIndexRequest $request, ProfileService $profileService): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'status' => true,
            'data' => $profileService->walletTransactions($user, $request->validated()),
        ]);
    }

    private function user(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_if(! $user instanceof User, 401);

        return $user;
    }
}
