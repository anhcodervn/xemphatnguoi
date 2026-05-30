<?php

namespace App\Features\Admin\PackageOrder\Controllers;

use App\Features\Admin\PackageOrder\Actions\ListAdminPackageOrdersAction;
use App\Features\Admin\PackageOrder\Actions\ShowAdminPackageOrderAction;
use App\Features\Admin\PackageOrder\Requests\AdminPackageOrderIndexRequest;
use App\Features\Admin\PackageOrder\Resources\AdminPackageOrderDetailResource;
use App\Http\Controllers\Controller;
use App\Models\PackageOrder;
use App\Utils\ApiResponse;
use Illuminate\Http\JsonResponse;

class PackageOrderController extends Controller
{
    public function index(
        AdminPackageOrderIndexRequest $request,
        ListAdminPackageOrdersAction $action,
    ): JsonResponse {
        return response()->json(ApiResponse::success(data: $action->handle($request->validated())));
    }

    public function show(PackageOrder $order, ShowAdminPackageOrderAction $action): JsonResponse
    {
        return response()->json(ApiResponse::success(data: AdminPackageOrderDetailResource::make($action->handle($order))->resolve()));
    }
}
