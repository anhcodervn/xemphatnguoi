<?php

namespace App\Features\Admin\Feedback\Controllers;

use App\Features\Admin\Feedback\Actions\ListAdminFeedbacksAction;
use App\Features\Admin\Feedback\Actions\UpdateAdminFeedbackStatusAction;
use App\Features\Admin\Feedback\Requests\AdminFeedbackIndexRequest;
use App\Features\Admin\Feedback\Requests\UpdateAdminFeedbackStatusRequest;
use App\Features\Admin\Feedback\Resources\AdminFeedbackResource;
use App\Http\Controllers\Controller;
use App\Models\ContactFeedback;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class FeedbackController extends Controller
{
    public function index(AdminFeedbackIndexRequest $request, ListAdminFeedbacksAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function updateStatus(
        UpdateAdminFeedbackStatusRequest $request,
        ContactFeedback $feedback,
        UpdateAdminFeedbackStatusAction $action,
    ): JsonResponse {
        $admin = $request->user();

        abort_unless($admin instanceof User, 401);

        $updated = $action->handle($feedback, $admin, (string) $request->validated('status'));

        return response()->json(ApiResponse::success(
            message: 'Đã cập nhật trạng thái góp ý.',
            data: ['feedback' => (new AdminFeedbackResource($updated))->resolve()],
        ));
    }
}
