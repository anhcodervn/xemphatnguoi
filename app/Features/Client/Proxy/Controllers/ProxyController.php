<?php

namespace App\Features\Client\Proxy\Controllers;

use App\Features\Client\Proxy\Actions\ChangeProxyAction;
use App\Features\Client\Proxy\Actions\CheckProxyAction;
use App\Features\Client\Proxy\Actions\CheckProxyCountryAction;
use App\Features\Client\Proxy\Actions\FetchRotatingProxyAction;
use App\Features\Client\Proxy\Actions\OrderAction;
use App\Features\Client\Proxy\Actions\RenewProxyAction;
use App\Features\Client\Proxy\Requests\ChangeProxyRequest;
use App\Features\Client\Proxy\Requests\CheckProxyRequest;
use App\Features\Client\Proxy\Requests\FetchRotatingProxyRequest;
use App\Features\Client\Proxy\Requests\IndexProxyOrderRequest;
use App\Features\Client\Proxy\Requests\IndexUserProxyRequest;
use App\Features\Client\Proxy\Requests\RenewProxyRequest;
use App\Features\Client\Proxy\Requests\StoreProxyOrderRequest;
use App\Features\Client\Proxy\Services\DashboardService;
use App\Features\Client\Proxy\Services\ProxyCatalogService;
use App\Features\Client\Proxy\Services\ProxyCheckerService;
use App\Features\Client\Proxy\Services\ProxyService;
use App\Http\Controllers\Controller;
use App\Models\ProxyCheckBatch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProxyController extends Controller
{
    public function __construct(
        private readonly ProxyCatalogService $catalog,
        private readonly ProxyService $proxyService,
        private readonly DashboardService $dashboardService,
    ) {}

    public function products(): JsonResponse
    {
        return response()->json(['status' => true, 'data' => [
            'categories' => $this->catalog->categories(),
            'products' => $this->catalog->products(),
        ]]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->dashboardService->handle($this->user($request)),
        ]);
    }

    public function check(CheckProxyRequest $request, CheckProxyAction $action): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Danh sách proxy đã được đưa vào hàng đợi kiểm tra.',
            'data' => ['batch' => $action->handle($this->user($request), $request->validated())],
        ], 202);
    }

    public function checkStatus(Request $request, ProxyCheckerService $proxyCheckerService, string $batch): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'batch' => $proxyCheckerService->status($this->user($request), $batch, ProxyCheckBatch::TYPE_LIVE),
            ],
        ]);
    }

    public function checkCountry(CheckProxyRequest $request, CheckProxyCountryAction $action): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Danh sách proxy đã được đưa vào hàng đợi xác định quốc gia.',
            'data' => ['batch' => $action->handle($this->user($request), $request->validated())],
        ], 202);
    }

    public function checkCountryStatus(Request $request, ProxyCheckerService $proxyCheckerService, string $batch): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'batch' => $proxyCheckerService->status($this->user($request), $batch, ProxyCheckBatch::TYPE_COUNTRY),
            ],
        ]);
    }

    public function order(StoreProxyOrderRequest $request, OrderAction $action): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Mua proxy thành công.',
            'data' => $action->handle($this->user($request), $request->validated()),
        ]);
    }

    public function orders(IndexProxyOrderRequest $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->proxyService->orders($this->user($request), $request->validated()),
        ]);
    }

    public function showOrder(Request $request, int $order): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => ['order' => $this->proxyService->order($this->user($request), $order)],
        ]);
    }

    public function proxies(IndexUserProxyRequest $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $this->proxyService->proxies($this->user($request), $request->validated()),
        ]);
    }

    public function proxy(Request $request, int $proxy): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => ['proxy' => $this->proxyService->proxy($this->user($request), $proxy)],
        ]);
    }

    public function changeProxy(ChangeProxyRequest $request, ChangeProxyAction $action, int $proxy): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Yêu cầu đổi proxy đã được tiếp nhận.',
            'data' => $action->handle($this->user($request), $proxy, $request->validated()),
        ], 202);
    }

    public function fetchRotatingProxy(
        FetchRotatingProxyRequest $request,
        FetchRotatingProxyAction $action,
        int $proxy,
    ): JsonResponse {
        return response()->json([
            'status' => true,
            'data' => $action->handle($this->user($request), $proxy),
        ]);
    }

    public function renewProxy(RenewProxyRequest $request, RenewProxyAction $action, int $proxy): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Yêu cầu gia hạn proxy đã được tiếp nhận.',
            'data' => $action->handle($this->user($request), $proxy, $request->validated()),
        ], 202);
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
