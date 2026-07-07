<?php

namespace App\Features\Client\Package\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Package\Data\PackageQuoteData;
use App\Features\Client\Subscription\Actions\CreateUserSubscriptionFromPaidOrderAction;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ApiKey;
use App\Models\Coupon;
use App\Models\CouponLog;
use App\Models\Package;
use App\Models\PackageOrder;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\Enums\PackageOrderStatus;
use App\Support\Enums\PackageStatus;
use App\Support\Enums\PaymentStatus;
use App\Utils\SendMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PackageCheckoutService
{
    private const QUOTE_TTL_MINUTES = 15;

    public function __construct(
        private readonly CreateUserSubscriptionFromPaidOrderAction $createUserSubscriptionFromPaidOrderAction,
        private readonly WalletService $walletService,
    ) {}

    /**
     * @param  array{coupon_code?:mixed}  $payload
     */
    public function quote(User $user, Package $package, array $payload = []): PackageQuoteData
    {
        if ($package->status !== PackageStatus::Active) {
            throw new ApiException('Gói hiện không khả dụng.', 422);
        }

        $price = (float) $package->price;
        $couponCode = $this->normalizeCouponCode($payload['coupon_code'] ?? null);
        $coupon = null;
        $discountAmount = 0.0;

        if ($couponCode !== null) {
            $coupon = $this->resolveAvailableCoupon($couponCode, $user, $package, $price);
            $discountAmount = $this->calculateCouponDiscount($coupon, $price);
        }

        return new PackageQuoteData(
            package: $package,
            sourceSubscription: null,
            quoteType: 'new_purchase',
            price: $price,
            discountAmount: $discountAmount,
            creditAmount: 0.0,
            finalAmount: max(0, $price - $discountAmount),
            expiresAt: now()->addMinutes(self::QUOTE_TTL_MINUTES),
            coupon: $coupon,
        );
    }

    /**
     * @param  array{coupon_code?:mixed,payment_method?:mixed,auto_renew_enabled?:mixed}  $payload
     */
    public function createOrder(User $user, Package $package, array $payload = []): PackageOrder
    {
        $quote = $this->quote($user, $package, $payload);

        $existingPendingOrder = PackageOrder::query()
            ->where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->whereNull('source_subscription_id')
            ->where('discount_amount', $quote->discountAmount)
            ->where('auto_renew_enabled', (bool) ($payload['auto_renew_enabled'] ?? false))
            ->where('payment_status', PaymentStatus::Pending)
            ->where('status', PackageOrderStatus::Pending)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        if ($existingPendingOrder instanceof PackageOrder) {
            return $existingPendingOrder;
        }

        return PackageOrder::query()->create([
            'user_id' => $user->id,
            'package_id' => $package->id,
            'source_subscription_id' => null,
            'order_code' => $this->generateOrderCode(),
            'price' => $quote->price,
            'discount_amount' => $quote->discountAmount,
            'credit_amount' => 0,
            'final_amount' => $quote->finalAmount,
            'payment_method' => $payload['payment_method'] ?? null,
            'auto_renew_enabled' => (bool) ($payload['auto_renew_enabled'] ?? false),
            'payment_status' => PaymentStatus::Pending,
            'status' => PackageOrderStatus::Pending,
            'paid_at' => null,
            'expires_at' => $quote->expiresAt,
        ]);
    }

    /**
     * @return array{
     *     order:PackageOrder,
     *     subscription:UserSubscription,
     *     package_api_key:array{
     *         api_key:ApiKey,
     *         api_secret:string|null,
     *         is_new:bool
     *     }|null
     * }
     */
    public function payWithWallet(User $user, PackageOrder $packageOrder): array
    {
        $shouldNotify = false;

        $result = DB::transaction(function () use ($user, $packageOrder, &$shouldNotify): array {
            $lockedOrder = PackageOrder::query()
                ->with(['package', 'subscription'])
                ->whereKey($packageOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->user_id !== $user->id) {
                throw new ApiException('Bạn không có quyền thao tác với đơn hàng này.', 403);
            }

            if ($lockedOrder->payment_status === PaymentStatus::Paid && $lockedOrder->subscription !== null) {
                return [
                    'order' => $lockedOrder,
                    'subscription' => $lockedOrder->subscription,
                    'package_api_key' => $this->findPackageApiKey($lockedOrder->subscription),
                ];
            }

            if ($lockedOrder->status === PackageOrderStatus::Cancelled || $lockedOrder->payment_status === PaymentStatus::Cancelled) {
                throw new ApiException('Đơn hàng này đã bị hủy.', 422);
            }

            if ($lockedOrder->expires_at !== null && $lockedOrder->expires_at->isPast()) {
                $lockedOrder->forceFill([
                    'payment_status' => PaymentStatus::Cancelled,
                    'status' => PackageOrderStatus::Cancelled,
                ])->save();

                throw new ApiException('Đơn hàng đã hết hạn. Vui lòng tạo đơn mới.', 422);
            }

            if ((float) $lockedOrder->final_amount > 0) {
                $this->walletService->debit(
                    user: $user,
                    amount: (float) $lockedOrder->final_amount,
                    referenceType: PackageOrder::class,
                    referenceId: $lockedOrder->id,
                    description: sprintf('Mua gói captcha qua đơn hàng %s', $lockedOrder->order_code),
                );
            }

            $paidAt = now();

            $lockedOrder->forceFill([
                'payment_method' => 'wallet',
                'payment_status' => PaymentStatus::Paid,
                'status' => PackageOrderStatus::Completed,
                'paid_at' => $paidAt,
            ])->save();

            $coupon = $this->findBestCouponForOrder($user, $lockedOrder);
            if ($coupon instanceof Coupon) {
                $coupon->increment('used_count');

                CouponLog::query()->create([
                    'coupon_id' => $coupon->id,
                    'user_id' => $user->id,
                    'package_order_id' => $lockedOrder->id,
                    'action' => 'applied_package_order',
                    'status' => 'success',
                    'order_amount' => (float) $lockedOrder->price,
                    'discount_amount' => (float) $lockedOrder->discount_amount,
                    'note' => sprintf('Áp dụng coupon cho đơn %s', $lockedOrder->order_code),
                    'payload' => [
                        'quote_type' => 'package_order',
                    ],
                ]);
            }

            $subscription = $this->createUserSubscriptionFromPaidOrderAction->handle($lockedOrder->fresh(['package']));
            $packageApiKey = $this->ensurePackageApiKey($user, $subscription);
            $shouldNotify = true;

            return [
                'order' => $lockedOrder->fresh(['package', 'subscription']),
                'subscription' => $subscription->fresh(['package', 'apiKeys']),
                'package_api_key' => $packageApiKey,
            ];
        });

        if ($shouldNotify) {
            $this->sendPackagePaymentNotification($user, $result['order'], $result['subscription'], 'wallet');
        }

        return $result;
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'PKG-'.Str::upper(Str::random(10));
        } while (PackageOrder::query()->where('order_code', $code)->exists());

        return $code;
    }

    private function normalizeCouponCode(mixed $couponCode): ?string
    {
        if (! is_string($couponCode)) {
            return null;
        }

        $normalized = strtoupper(trim($couponCode));

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveAvailableCoupon(string $couponCode, User $user, Package $package, float $price): Coupon
    {
        $coupon = Coupon::query()
            ->where('code', $couponCode)
            ->where('is_active', true)
            ->first();

        if (! $coupon instanceof Coupon || ! $coupon->isAvailable()) {
            throw new ApiException('Coupon không hợp lệ hoặc đã hết hiệu lực.', 422);
        }

        if ($coupon->min_order_amount !== null && (float) $coupon->min_order_amount > $price) {
            throw new ApiException('Đơn hàng chưa đạt giá trị tối thiểu để áp dụng coupon.', 422);
        }

        $packageIds = $coupon->applicable_package_ids ?? [];
        if (is_array($packageIds) && $packageIds !== [] && ! in_array($package->id, $packageIds, true)) {
            throw new ApiException('Coupon không áp dụng cho gói đã chọn.', 422);
        }

        if ($coupon->first_order_only && $user->packageOrders()->where('payment_status', PaymentStatus::Paid)->exists()) {
            throw new ApiException('Coupon này chỉ áp dụng cho đơn hàng đầu tiên.', 422);
        }

        if ($coupon->max_usage_per_user !== null) {
            $userUsageCount = CouponLog::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->count();

            if ($userUsageCount >= $coupon->max_usage_per_user) {
                throw new ApiException('Bạn đã dùng hết lượt coupon cho tài khoản này.', 422);
            }
        }

        return $coupon;
    }

    private function calculateCouponDiscount(Coupon $coupon, float $price): float
    {
        $discount = $coupon->type === Coupon::TYPE_PERCENT
            ? round($price * ((float) $coupon->value / 100), 2)
            : (float) $coupon->value;

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, (float) $coupon->max_discount_amount);
        }

        return max(0, min($discount, $price));
    }

    private function findBestCouponForOrder(User $user, PackageOrder $order): ?Coupon
    {
        $discountAmount = (float) $order->discount_amount;
        if ($discountAmount <= 0) {
            return null;
        }

        $coupons = Coupon::query()
            ->where('is_active', true)
            ->get();

        foreach ($coupons as $coupon) {
            try {
                $validatedCoupon = $this->resolveAvailableCoupon(
                    $coupon->code,
                    $user,
                    $order->package,
                    (float) $order->price,
                );
            } catch (ApiException) {
                continue;
            }

            $calculatedDiscount = $this->calculateCouponDiscount($validatedCoupon, (float) $order->price);
            if (abs($calculatedDiscount - $discountAmount) < 0.0001) {
                return $validatedCoupon;
            }
        }

        return null;
    }

    /**
     * @return array{api_key:ApiKey, api_secret:string|null, is_new:bool}|null
     */
    private function ensurePackageApiKey(User $user, UserSubscription $subscription): ?array
    {
        $existingKey = ApiKey::query()
            ->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_PACKAGE)
            ->where('user_subscription_id', $subscription->id)
            ->first();

        if ($existingKey instanceof ApiKey) {
            return [
                'api_key' => $existingKey,
                'api_secret' => $existingKey->api_secret_encrypted,
                'is_new' => false,
            ];
        }

        $credentials = ApiKey::generateCredentials();

        $apiKey = ApiKey::query()->create([
            'user_id' => $user->id,
            'key_type' => ApiKey::TYPE_PACKAGE,
            'user_subscription_id' => $subscription->id,
            'name' => sprintf('Gói %s', $subscription->package_name),
            'api_key' => $credentials['api_key'],
            'api_secret_hash' => Hash::make($credentials['api_secret']),
            'api_secret_encrypted' => $credentials['api_secret'],
            'permissions' => [
                'captcha-services.read',
                'captcha-tasks.create',
                'captcha-tasks.read',
            ],
            'ip_whitelist' => ['*'],
            'expired_at' => $subscription->expires_at,
            'status' => ApiKey::STATUS_ACTIVE,
        ]);

        return [
            'api_key' => $apiKey,
            'api_secret' => $credentials['api_secret'],
            'is_new' => true,
        ];
    }

    /**
     * @return array{api_key:ApiKey, api_secret:string|null, is_new:bool}|null
     */
    private function findPackageApiKey(UserSubscription $subscription): ?array
    {
        $apiKey = ApiKey::query()
            ->where('user_id', $subscription->user_id)
            ->where('key_type', ApiKey::TYPE_PACKAGE)
            ->where('user_subscription_id', $subscription->id)
            ->first();

        if (! $apiKey instanceof ApiKey) {
            return null;
        }

        return [
            'api_key' => $apiKey,
            'api_secret' => $apiKey->api_secret_encrypted,
            'is_new' => false,
        ];
    }

    private function sendPackagePaymentNotification(
        User $user,
        PackageOrder $order,
        UserSubscription $subscription,
        string $paymentMethod,
    ): void {
        SendMessage::sendInfoReport('Người dùng đăng ký gói mới thành công', [
            'User ID' => $user->id,
            'Username' => $user->username,
            'Package' => $order->package?->name ?? $subscription->package_name,
            'Order code' => $order->order_code,
            'Thanh toán' => $paymentMethod,
            'Giá gói' => $order->price,
            'Giảm giá' => $order->discount_amount,
            'Thành tiền' => $order->final_amount,
            'Subscription ID' => $subscription->id,
            'Bắt đầu' => $subscription->starts_at,
            'Hết hạn' => $subscription->expires_at,
        ]);
    }
}
