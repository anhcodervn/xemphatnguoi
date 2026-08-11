<?php

namespace App\Features\Support\Controllers;

use App\Features\Support\Requests\SendSupportMessageRequest;
use App\Features\Support\Requests\SupportMessageIndexRequest;
use App\Features\Support\Services\SupportChatService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSupportController extends Controller
{
    public function __construct(private readonly SupportChatService $supportChatService) {}

    public function index(SupportMessageIndexRequest $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $this->supportChatService->clientThread(
            $this->user($request),
            $request->validated(),
        )));
    }

    public function store(SendSupportMessageRequest $request): JsonResponse
    {
        return response()->json(ApiResponse::success(
            'Đã gửi tin nhắn hỗ trợ.',
            $this->supportChatService->sendAsUser($this->user($request), $request->string('message')->toString()),
        ), 201);
    }

    public function markRead(Request $request): JsonResponse
    {
        return response()->json(ApiResponse::success(
            'Đã cập nhật trạng thái đọc.',
            $this->supportChatService->markClientRead($this->user($request)),
        ));
    }

    public function unread(Request $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'stats' => $this->supportChatService->unreadStats($this->user($request)),
        ]));
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
