<?php

namespace App\Features\Client\Subscription\Controllers;

use App\Features\Client\Package\Requests\PayPackageOrderRequest;
use App\Features\Client\Package\Services\PackageCheckoutService;
use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Features\Client\Subscription\Requests\StorePackageOrderRequest;
use App\Features\Client\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\UserSubscription;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\SubscriptionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageOrderController extends Controller
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
            ->get()
            ->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'price' => (string) $package->price,
                'duration_days' => $package->duration_days,
                'account_limit' => $package->account_limit,
                'status' => $package->status->value,
            ])
            ->values();

        $subscriptions = $user->userSubscriptions()
            ->with(['package', 'extraAccountOrders'])
            ->latest('id')
            ->get();

        $packageOrders = $user->packageOrders()
            ->with(['package', 'subscription', 'sourceSubscription'])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (PackageOrder $order): array => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'price' => (string) $order->price,
                'final_amount' => (string) $order->final_amount,
                'payment_status' => $order->payment_status->value,
                'status' => $order->status->value,
                'paid_at' => $order->paid_at?->toISOString(),
                'expires_at' => $order->expires_at?->toISOString(),
                'package' => $order->package ? [
                    'id' => $order->package->id,
                    'name' => $order->package->name,
                    'slug' => $order->package->slug,
                ] : null,
            ])
            ->values();

        $extraAccountOrders = $subscriptions
            ->flatMap->extraAccountOrders
            ->sortByDesc('id')
            ->take(10)
            ->values()
            ->map(fn ($order): array => [
                'id' => $order->id,
                'quantity' => $order->quantity,
                'amount' => (string) $order->amount,
                'status' => $order->status->value,
                'paid_at' => $order->paid_at?->toISOString(),
                'created_at' => $order->created_at?->toISOString(),
            ])
            ->values();

        $activeSubscriptions = $subscriptions->where('status', SubscriptionStatus::Active);
        $totalQuota = $activeSubscriptions->sum(fn (UserSubscription $subscription) => $subscription->base_account_limit + $subscription->extra_account_limit);
        $usedQuota = $activeSubscriptions->sum('used_account');

        return response()->json([
            'status' => true,
            'data' => [
                'packages' => $packages,
                'subscriptions' => $subscriptions->map(fn (UserSubscription $subscription): array => [
                    'id' => $subscription->id,
                    'package_id' => $subscription->package_id,
                    'package_name' => $subscription->package_name,
                    'package_price' => (string) $subscription->package_price,
                    'base_account_limit' => $subscription->base_account_limit,
                    'extra_account_limit' => $subscription->extra_account_limit,
                    'used_account' => $subscription->used_account,
                    'status' => $subscription->status->value,
                    'starts_at' => $subscription->starts_at?->toISOString(),
                    'expires_at' => $subscription->expires_at?->toISOString(),
                    'package' => $subscription->package ? [
                        'id' => $subscription->package->id,
                        'name' => $subscription->package->name,
                        'slug' => $subscription->package->slug,
                    ] : null,
                ])->values(),
                'package_orders' => $packageOrders,
                'extra_account_orders' => $extraAccountOrders,
                'summary' => [
                    'active_subscription_count' => $activeSubscriptions->count(),
                    'total_quota' => $totalQuota,
                    'used_quota' => $usedQuota,
                    'available_quota' => max(0, $totalQuota - $usedQuota),
                ],
            ],
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
            'message' => 'Tạo đơn hàng gói thành công.',
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
