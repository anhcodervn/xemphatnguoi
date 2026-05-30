<?php

namespace App\Features\Admin\Webhook\Controllers;

use App\Features\Admin\Webhook\Actions\ListAdminWebhooksAction;
use App\Features\Admin\Webhook\Actions\ListWebhookLogsAction;
use App\Features\Admin\Webhook\Actions\ShowAdminWebhookAction;
use App\Features\Admin\Webhook\Actions\TestWebhookAction;
use App\Features\Admin\Webhook\Actions\ToggleWebhookAction;
use App\Features\Admin\Webhook\Requests\AdminWebhookIndexRequest;
use App\Features\Admin\Webhook\Requests\AdminWebhookLogIndexRequest;
use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function index(AdminWebhookIndexRequest $request, ListAdminWebhooksAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function show(Webhook $webhook, ShowAdminWebhookAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($webhook)));
    }

    public function toggle(Webhook $webhook, ToggleWebhookAction $action): JsonResponse
    {
        $updatedWebhook = $action->handle($webhook);

        return response()->json(ApiResponse::success(
            'Cập nhật trạng thái webhook thành công.',
            [
                'id' => $updatedWebhook->id,
                'status' => $updatedWebhook->status,
            ],
        ));
    }

    public function test(Webhook $webhook, TestWebhookAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(
            'Đã gửi test webhook.',
            $action->handle($webhook),
        ));
    }

    public function logs(
        AdminWebhookLogIndexRequest $request,
        Webhook $webhook,
        ListWebhookLogsAction $action,
    ): JsonResponse {
        return response()->json(ApiResponse::success(data: $action->handle($webhook, $request->validated())));
    }
}
