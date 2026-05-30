<?php

namespace App\Features\Client\Contact\Controllers;

use App\Features\Client\Contact\Actions\StoreContactFeedbackAction;
use App\Features\Client\Contact\Requests\StoreContactFeedbackRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(StoreContactFeedbackRequest $request, StoreContactFeedbackAction $action): JsonResponse
    {
        $user = $request->user();

        return response()->json(
            ApiResponse::success(
                message: 'Đã gửi góp ý thành công.',
                data: [
                    'feedback' => $action->handle($user instanceof User ? $user : null, $request->validated()),
                ],
            ),
        );
    }

    public function info(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(ApiResponse::success(data: [
            'name' => $user?->full_name ?? $user?->username ?? null,
            'email' => $user?->email,
            'phone' => $user?->phone,
        ]));
    }
}
