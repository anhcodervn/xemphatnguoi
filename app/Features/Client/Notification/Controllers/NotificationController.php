<?php

namespace App\Features\Client\Notification\Controllers;

use App\Features\Client\Notification\Actions\ListClientNotificationsAction;
use App\Features\Client\Notification\Actions\MarkClientNotificationAsReadAction;
use App\Features\Client\Notification\Requests\ClientNotificationIndexRequest;
use App\Features\Client\Notification\Requests\MarkNotificationReadRequest;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationController extends Controller
{
    public function index(
        ClientNotificationIndexRequest $request,
        ListClientNotificationsAction $action,
    ): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json(ApiResponse::success(data: $action->handle($user, $request->validated())));
    }

    public function markRead(
        MarkNotificationReadRequest $request,
        Notification $notification,
        MarkClientNotificationAsReadAction $action,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $canAccess = $notification->scope === Notification::SCOPE_SYSTEM
            || ($notification->scope === Notification::SCOPE_USER && (int) $notification->user_id === (int) $user->id);

        if (! $canAccess) {
            throw new NotFoundHttpException();
        }

        $action->handle($user, $notification);

        return response()->json(ApiResponse::success(message: 'Đã đánh dấu đã xem.'));
    }
}
