<?php

namespace App\Features\Admin\Deposit\Controllers;

use App\Features\Admin\Deposit\Actions\ApproveDepositAction;
use App\Features\Admin\Deposit\Actions\ListAdminDepositsAction;
use App\Features\Admin\Deposit\Actions\RejectDepositAction;
use App\Features\Admin\Deposit\Actions\ShowAdminDepositAction;
use App\Features\Admin\Deposit\Requests\AdminDepositIndexRequest;
use App\Features\Admin\Deposit\Requests\AdminDepositRejectRequest;
use App\Features\Admin\Deposit\Resources\AdminDepositDetailResource;
use App\Http\Controllers\Controller;
use App\Models\RechargeOrder;
use App\Models\User;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function index(AdminDepositIndexRequest $request, ListAdminDepositsAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function show(RechargeOrder $deposit, ShowAdminDepositAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: AdminDepositDetailResource::make($action->handle($deposit))->resolve()));
    }

    public function approve(
        Request $request,
        RechargeOrder $deposit,
        ApproveDepositAction $action,
    ): JsonResponse {
        return response()->json(ApiResponse::success(
            'Duyệt yêu cầu nạp tiền thành công.',
            $action->handle($deposit, $this->admin($request)),
        ));
    }

    public function reject(
        AdminDepositRejectRequest $request,
        RechargeOrder $deposit,
        RejectDepositAction $action,
    ): JsonResponse {
        return response()->json(ApiResponse::success(
            'Từ chối yêu cầu nạp tiền thành công.',
            $action->handle($deposit, $request->validated(), $this->admin($request)),
        ));
    }

    private function admin(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
