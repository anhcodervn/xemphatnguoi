<?php

namespace App\Features\Support\Controllers;

use App\Features\Support\Requests\AdminStartSupportConversationRequest;
use App\Features\Support\Requests\AdminSupportConversationIndexRequest;
use App\Features\Support\Requests\AdminSupportUserSearchRequest;
use App\Features\Support\Requests\SendSupportMessageRequest;
use App\Features\Support\Requests\SupportMessageIndexRequest;
use App\Features\Support\Services\SupportChatService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSupportController extends Controller
{
    public function __construct(private readonly SupportChatService $supportChatService) {}

    public function index(AdminSupportConversationIndexRequest $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $this->supportChatService->adminConversations($request->validated())));
    }

    public function show(SupportMessageIndexRequest $request, int $conversation): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $this->supportChatService->adminThread($conversation, $request->validated())));
    }

    public function store(AdminStartSupportConversationRequest $request): JsonResponse
    {
        return response()->json(ApiResponse::success(
            'Đã gửi tin nhắn hỗ trợ.',
            $this->supportChatService->startAsAdmin(
                $this->admin($request),
                $request->integer('user_id'),
                $request->string('message')->toString(),
            ),
        ), 201);
    }

    public function reply(SendSupportMessageRequest $request, int $conversation): JsonResponse
    {
        return response()->json(ApiResponse::success(
            'Đã gửi phản hồi.',
            $this->supportChatService->sendAsAdmin($this->admin($request), $conversation, $request->string('message')->toString()),
        ), 201);
    }

    public function markRead(int $conversation): JsonResponse
    {
        return response()->json(ApiResponse::success(
            'Đã cập nhật trạng thái đọc.',
            $this->supportChatService->markAdminRead($conversation),
        ));
    }

    public function users(AdminSupportUserSearchRequest $request): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'users' => $this->supportChatService->searchUsers($request->string('search')->toString()),
        ]));
    }

    public function unread(): JsonResponse
    {
        return response()->json(ApiResponse::success(data: [
            'stats' => $this->supportChatService->unreadStats(),
        ]));
    }

    private function admin(Request $request): User
    {
        /** @var User $admin */
        $admin = $request->user();

        return $admin;
    }
}
