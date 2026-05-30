<?php

namespace App\Features\Admin\Mail\Controllers;

use App\Features\Admin\Mail\Actions\SearchMailUsersAction;
use App\Features\Admin\Mail\Actions\SendUserMailAction;
use App\Features\Admin\Mail\Requests\AdminMailUserSearchRequest;
use App\Features\Admin\Mail\Requests\SendUserMailRequest;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class MailController extends Controller
{
    public function users(AdminMailUserSearchRequest $request, SearchMailUsersAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function send(SendUserMailRequest $request, SendUserMailAction $action): JsonResponse
    {
        $result = $action->handle($request->validated());

        return response()->json(ApiResponse::success(
            message: 'Đã đưa email vào hàng đợi gửi.',
            data: $result,
        ));
    }
}
