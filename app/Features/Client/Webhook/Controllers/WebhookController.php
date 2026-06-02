<?php

namespace App\Features\Client\Webhook\Controllers;

use App\Exceptions\ApiException;
use App\Features\Client\Webhook\Actions\DispatchTransactionWebhookAction;
use App\Features\Client\Webhook\Actions\DispatchWebhookAction;
use App\Features\Client\Webhook\Actions\StoreWebhookAction;
use App\Features\Client\Webhook\Actions\UpdateWebhookAction;
use App\Features\Client\Webhook\Requests\StoreWebhookRequest;
use App\Features\Client\Webhook\Requests\UpdateWebhookRequest;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser($request);

        return response()->json([
            'status' => true,
            'data' => Webhook::query()
                ->where('user_id', $user->id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Webhook $webhook) => $this->serializeWebhook($webhook))
                ->all(),
        ]);
    }

    public function byBank(Request $request, BankAccount $bankAccount): JsonResponse
    {
        $user = $this->authenticatedUser($request);
        $this->ensureBankAccountOwnership($request, $bankAccount);
        $this->ensureBankAccountOperational($bankAccount);

        return response()->json([
            'status' => true,
            'data' => Webhook::query()
                ->where('user_id', $user->id)
                ->where('bank_account_id', $bankAccount->id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Webhook $webhook) => $this->serializeWebhook($webhook))
                ->all(),
        ]);
    }

    public function logs(Request $request, Webhook $webhook): JsonResponse
    {
        $this->ensureWebhookOwnership($request, $webhook);

        return response()->json([
            'status' => true,
            'data' => WebhookLog::query()
                ->where('webhook_id', $webhook->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->map(fn (WebhookLog $log) => $this->serializeWebhookLog($log))
                ->all(),
        ]);
    }

    public function dispatch(
        Request $request,
        BankAccount $bankAccount,
        DispatchWebhookAction $action,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        $this->ensureBankAccountOwnership($request, $bankAccount);
        $this->ensureBankAccountOperational($bankAccount);

        $validated = $request->validate([
            'event_keyword' => ['nullable', 'string', 'max:100'],
            'payload' => ['nullable', 'array'],
        ]);

        $dispatchedCount = $action->handle(
            $user,
            $bankAccount,
            strtolower(trim((string) ($validated['event_keyword'] ?? ''))),
            $validated['payload'] ?? [],
        );

        return response()->json([
            'status' => true,
            'message' => "Đã đưa {$dispatchedCount} webhook vào hàng chờ.",
            'data' => [
                'dispatched_count' => $dispatchedCount,
            ],
        ]);
    }

    public function dispatchTransaction(
        Request $request,
        BankAccount $bankAccount,
        BankTransaction $bankTransaction,
        DispatchTransactionWebhookAction $action,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        $this->ensureBankAccountOwnership($request, $bankAccount);
        $this->ensureBankAccountOperational($bankAccount);
        abort_if($bankTransaction->bank_account_id !== $bankAccount->id, 404);

        $dispatchedCount = $action->handle($user, $bankAccount, $bankTransaction);

        return response()->json([
            'status' => true,
            'message' => "Đã đưa {$dispatchedCount} webhook vào hàng chờ.",
            'data' => [
                'dispatched_count' => $dispatchedCount,
            ],
        ]);
    }

    public function store(
        StoreWebhookRequest $request,
        BankAccount $bankAccount,
        StoreWebhookAction $action,
    ): JsonResponse {
        $user = $this->authenticatedUser($request);
        $this->ensureBankAccountOwnership($request, $bankAccount);
        $this->ensureBankAccountOperational($bankAccount);

        $webhook = $action->handle($user, $bankAccount, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Tạo webhook thành công.',
            'data' => $this->serializeWebhook($webhook),
        ]);
    }

    public function update(
        UpdateWebhookRequest $request,
        Webhook $webhook,
        UpdateWebhookAction $action,
    ): JsonResponse {
        $this->ensureWebhookOwnership($request, $webhook);

        $updatedWebhook = $action->handle($webhook, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật webhook thành công.',
            'data' => $this->serializeWebhook($updatedWebhook),
        ]);
    }

    public function destroy(Request $request, Webhook $webhook): JsonResponse
    {
        $this->ensureWebhookOwnership($request, $webhook);

        $webhook->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa webhook thành công.',
        ]);
    }

    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    protected function ensureWebhookOwnership(Request $request, Webhook $webhook): void
    {
        abort_if($webhook->user_id !== $this->authenticatedUser($request)->id, 404);
    }

    protected function ensureBankAccountOwnership(Request $request, BankAccount $bankAccount): void
    {
        abort_if($bankAccount->user_id !== $this->authenticatedUser($request)->id, 404);
    }

    protected function ensureBankAccountOperational(BankAccount $bankAccount): void
    {
        if ($bankAccount->status !== 'active') {
            throw new ApiException('Thẻ này đang tắt. Vui lòng bật lại để sử dụng chức năng này.', 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeWebhook(Webhook $webhook): array
    {
        $eventKeyword = $webhook->event_keyword !== null
            ? trim((string) $webhook->event_keyword)
            : null;

        return [
            'id' => $webhook->id,
            'bank_account_id' => $webhook->bank_account_id,
            'name' => $webhook->name,
            'url' => $webhook->url,
            'secret_key' => $webhook->secret_key,
            'event_keyword' => $eventKeyword,
            'status' => $webhook->status,
            'created_at' => $webhook->created_at?->toDateTimeString(),
            'updated_at' => $webhook->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeWebhookLog(WebhookLog $log): array
    {
        return [
            'id' => $log->id,
            'event_keyword' => $log->event_keyword,
            'payload' => $log->payload,
            'response' => $log->response,
            'status_code' => $log->status_code,
            'attempt' => $log->attempt,
            'created_at' => $log->created_at?->toDateTimeString(),
            'updated_at' => $log->updated_at?->toDateTimeString(),
        ];
    }
}
