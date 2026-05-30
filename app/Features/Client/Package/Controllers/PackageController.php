<?php

namespace App\Features\Client\Package\Controllers;

use App\Features\Client\Package\Requests\PayPackageOrderRequest;
use App\Features\Client\Package\Requests\QuotePackageOrderRequest;
use App\Features\Client\Package\Services\PackageCheckoutService;
use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Features\Client\Subscription\Requests\StorePackageOrderRequest;
use App\Features\Client\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageCheckoutService $packageCheckoutService,
        private readonly WalletService $walletService,
        private readonly RecordUserLogAction $recordUserLogAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $packages = Package::query()
            ->where('status', PackageStatus::Active)
            ->orderBy('price')
            ->get([
                'id',
                'name',
                'slug',
                'description',
                'price',
                'duration_days',
                'account_limit',
                'can_buy_extra_account',
                'extra_account_price',
                'request_limit',
                'request_per_minute',
                'concurrent_limit',
                'features',
                'status',
            ]);

        $activeSubscriptions = $user->userSubscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->with('package:id,name,slug')
            ->latest('id')
            ->get();

        $latestOrders = $user->packageOrders()
            ->with(['package:id,name,slug', 'sourceSubscription'])
            ->latest('id')
            ->limit(5)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'packages' => $packages,
                'active_subscription_package_ids' => $activeSubscriptions->pluck('package_id')->unique()->values(),
                'summary' => [
                    'active_subscription_count' => $activeSubscriptions->count(),
                    'latest_order_count' => $latestOrders->count(),
                    'wallet_balance' => $this->walletService->getWalletInfo($user)['balance'],
                ],
                'latest_orders' => $latestOrders,
            ],
        ]);
    }

    public function quote(QuotePackageOrderRequest $request): JsonResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));
        $quote = $this->packageCheckoutService->quote(
            $request->user(),
            $package,
            $request->validated(),
        );

        return response()->json([
            'status' => true,
            'data' => $quote->toArray(),
        ]);
    }

    public function store(StorePackageOrderRequest $request): JsonResponse
    {
        $package = Package::query()->findOrFail($request->integer('package_id'));

        $packageOrder = $this->packageCheckoutService->createOrder(
            $request->user(),
            $package,
            $request->validated(),
        );
        $this->recordUserLogAction->handle(
            $request->user(),
            'package_order_created',
            sprintf('Tạo đơn hàng gói %s', $packageOrder->order_code),
            $request,
        );

        return response()->json([
            'status' => true,
            'message' => 'Đã tạo đơn hàng gói. Vui lòng hoàn tất bước thanh toán.',
            'data' => $packageOrder->fresh(['package', 'sourceSubscription']),
        ], 201);
    }

    public function pay(PayPackageOrderRequest $request, PackageOrder $packageOrder): JsonResponse
    {
        $this->authorize('manage', $packageOrder);

        $result = $this->packageCheckoutService->payWithWallet(
            $request->user(),
            $packageOrder,
        );
        $this->recordUserLogAction->handle(
            $request->user(),
            'package_order_paid',
            sprintf('Thanh toán đơn hàng gói %s bằng ví chính', $result['order']->order_code),
            $request,
        );

        return response()->json([
            'status' => true,
            'message' => 'Đơn hàng gói đã được thanh toán bằng ví chính.',
            'data' => [
                'order' => $result['order'],
                'subscription' => $result['subscription'],
                'wallet' => $this->walletService->getWalletInfo($request->user()),
            ],
        ]);
    }
}
