<?php

namespace App\Features\Api\V1\Controllers;

use App\Features\Api\V1\Actions\MatchRechargeClientOrdersAction;
use App\Features\Api\V1\Actions\StoreRechargeClientOrderAction;
use App\Features\Api\V1\Requests\ListBankTransactionsRequest;
use App\Features\Api\V1\Requests\StoreRechargeClientOrderRequest;
use App\Features\Api\V1\Resources\RechargeClientResource;
use App\Features\Client\Bank\Actions\TransactionBankAction;
use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Features\Client\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\BankAccount;
use App\Models\RechargeClient;
use App\Models\User;
use App\Support\ApiPermissionCatalog;
use App\Support\Enums\SubscriptionStatus;
use App\Utils\ApiResponse;
use App\Utils\EncodeBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class V1Controller extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json(ApiResponse::success(data: [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'permissions' => ApiPermissionCatalog::selfService(),
            'endpoints' => [
                'GET /api/v1/me',
                'GET /api/v1/list-bank-accounts',
                'POST /api/v1/transactions',
                'POST /api/v1/recharge-orders',
                'GET /api/v1/recharge-orders/{orderCode}',
            ],
        ]));
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $user->loadMissing('wallet');

        return response()->json(ApiResponse::success(data: [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'full_name' => $user->full_name,
            'status' => $user->status,
            'wallet' => $this->walletService->getWalletInfo($user),
            'active_subscription_count' => $user->userSubscriptions()
                ->where('status', SubscriptionStatus::Active)
                ->count(),
        ]));
    }

    public function listBankAccounts(): JsonResponse
    {
        /** @var User|null $user */
        $user = request()->user();
        abort_unless($user instanceof User, 401);

        $bankAccounts = BankAccount::query()
            ->leftJoin('banks', 'banks.code', '=', 'bank_accounts.bank_name')
            ->where('bank_accounts.user_id', $user->id)
            ->where('bank_accounts.status', 'active')
            ->orderByDesc('bank_accounts.updated_at')
            ->orderByDesc('bank_accounts.id')
            ->get([
                'bank_accounts.id',
                'bank_accounts.bank_name as bank_code',
                'bank_accounts.account_name',
                'bank_accounts.account_number',
                'bank_accounts.username',
                'bank_accounts.status',
                'bank_accounts.last_sync_at',
                'banks.name as bank_full_name',
                'banks.short_name as bank_short_name',
                'banks.logo as bank_logo',
                'banks.bg_color as bank_bg_color',
            ])
            ->map(fn ($bankAccount): array => [
                'bank_id' => $bankAccount->id,
                'bank_code' => $bankAccount->bank_code,
                'bank_name' => $bankAccount->bank_short_name ?: $bankAccount->bank_full_name ?: strtoupper((string) $bankAccount->bank_code),
                'bank_full_name' => $bankAccount->bank_full_name,
                'bank_short_name' => $bankAccount->bank_short_name,
                'bank_logo' => $bankAccount->bank_logo,
                'bank_bg_color' => $bankAccount->bank_bg_color ?: '#2563EB',
                'account_name' => $bankAccount->account_name,
                'account_number' => $bankAccount->account_number,
                'username' => EncodeBank::decode($bankAccount->username),
                'status' => $bankAccount->status,
                'last_sync_at' => $bankAccount->last_sync_at?->toISOString(),
            ]);

        return response()->json(ApiResponse::success(data: [
            'bank_accounts' => $bankAccounts,
        ]));
    }

    public function listTransactions(
        ListBankTransactionsRequest $request,
        TransactionBankAction $transactionBankAction,
        MatchRechargeClientOrdersAction $matchRechargeClientOrdersAction,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $bankAccount = BankAccount::query()
            ->where('user_id', $user->id)
            ->findOrFail($request->integer('bank_id'));
        $result = $transactionBankAction->handleWithChanges(
            $bankAccount,
            $request->integer('limit', 20),
            $request->boolean('force_refresh'),
        );
        $matchedRechargeClients = $matchRechargeClientOrdersAction->handle($bankAccount, $result['new_transactions']);

        return response()->json(ApiResponse::success(data: [
            'bank_id' => $bankAccount->id,
            'transactions' => $result['transactions'],
            'new_transactions' => $result['new_transactions'],
            'matched_recharge_clients' => $matchedRechargeClients,
        ]));
    }

    public function storeRechargeOrder(
        StoreRechargeClientOrderRequest $request,
        StoreRechargeClientOrderAction $storeRechargeClientOrderAction,
        RecordUserLogAction $recordUserLogAction,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        $order = $storeRechargeClientOrderAction->handle($user, $apiKey, $request->validated());
        $recordUserLogAction->handle(
            $user,
            'api_recharge_client_order_created',
            sprintf('Created recharge client order %s via API key.', $order->order_code),
            $request,
        );

        return response()->json(ApiResponse::success(
            message: 'Tạo lệnh nạp cho đối tác thành công.',
            data: [
                'order' => RechargeClientResource::make($order)->resolve(),
            ],
        ), 201);
    }

    public function showRechargeOrder(Request $request, string $orderCode): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $order = RechargeClient::query()
            ->whereBelongsTo($user)
            ->where('order_code', $orderCode)
            ->first();

        if (! $order instanceof RechargeClient) {
            throw new NotFoundHttpException;
        }

        if (
            in_array($order->status, [RechargeClient::STATUS_PENDING, RechargeClient::STATUS_PROCESSING], true)
            && $order->expires_at !== null
            && $order->expires_at->isPast()
        ) {
            $order->forceFill([
                'status' => RechargeClient::STATUS_EXPIRED,
            ])->save();
            $order->refresh();
        }

        return response()->json(ApiResponse::success(data: [
            'order' => RechargeClientResource::make($order)->resolve(),
        ]));
    }
}
