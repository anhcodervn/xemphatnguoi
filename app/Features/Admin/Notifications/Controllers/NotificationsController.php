<?php

namespace App\Features\Admin\Notifications\Controllers;

use App\Features\Admin\Notifications\Actions\DeleteAdminNotificationAction;
use App\Features\Admin\Notifications\Actions\ListAdminNotificationsAction;
use App\Features\Admin\Notifications\Actions\ShowAdminNotificationAction;
use App\Features\Admin\Notifications\Actions\StoreAdminNotificationAction;
use App\Features\Admin\Notifications\Actions\UpdateAdminNotificationAction;
use App\Features\Admin\Notifications\Requests\AdminNotificationIndexRequest;
use App\Features\Admin\Notifications\Requests\StoreAdminNotificationRequest;
use App\Features\Admin\Notifications\Requests\UpdateAdminNotificationRequest;
use App\Features\Admin\Notifications\Resources\AdminNotificationResource;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    public function index(
        AdminNotificationIndexRequest $request,
        ListAdminNotificationsAction $action,
    ): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function show(Notification $notification, ShowAdminNotificationAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: (new AdminNotificationResource($action->handle($notification)))->resolve()));
    }

    public function store(StoreAdminNotificationRequest $request, StoreAdminNotificationAction $action): JsonResponse
    {
        $notification = $action->handle($request->validated());

        return response()->json(
            ApiResponse::success(message: 'Tạo thông báo thành công.', data: (new AdminNotificationResource($notification))->resolve()),
            201
        );
    }

    public function update(UpdateAdminNotificationRequest $request, Notification $notification, UpdateAdminNotificationAction $action): JsonResponse
    {
        $notification = $action->handle($notification, $request->validated());

        return response()->json(ApiResponse::success(message: 'Cập nhật thông báo thành công.', data: (new AdminNotificationResource($notification))->resolve()));
    }

    public function destroy(Notification $notification, DeleteAdminNotificationAction $action): JsonResponse
    {
        $action->handle($notification);

        return response()->json(ApiResponse::success(message: 'Xóa thông báo thành công.'));
    }
}
