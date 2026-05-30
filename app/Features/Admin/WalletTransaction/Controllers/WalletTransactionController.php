<?php

namespace App\Features\Admin\WalletTransaction\Controllers;

use App\Features\Admin\WalletTransaction\Actions\ListAdminWalletTransactionsAction;
use App\Features\Admin\WalletTransaction\Requests\AdminWalletTransactionIndexRequest;
use App\Http\Controllers\Controller;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class WalletTransactionController extends Controller
{
    public function index(
        AdminWalletTransactionIndexRequest $request,
        ListAdminWalletTransactionsAction $action,
    ): JsonResponse {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }
}
