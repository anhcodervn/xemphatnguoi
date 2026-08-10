<?php

namespace App\Features\Client\Proxy\Services;

use App\Exceptions\ApiException;
use App\Features\Client\Wallet\Services\WalletService;
use App\Models\ProxyCategory;
use App\Models\ProxyOrder;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\User;
use App\Models\UserProxy;
use App\Service\Proxy\ProxyVn;
use App\Service\Reporting\ProxySalesReporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class OrderService
{
    private const PROVIDER_REQUEST_FAILED_MESSAGE = 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.';

    public function __construct(
        private readonly WalletService $walletService,
        private readonly ProxySalesReporter $proxySalesReporter,
    ) {}

    /**
     * Thực hiện toàn bộ quy trình mua proxy trong một transaction cơ sở dữ liệu.
     *
     * Mọi thay đổi cục bộ gồm đơn hàng, số dư ví, lịch sử giao dịch và proxy của
     * người dùng sẽ được rollback nếu bất kỳ bước nào phát sinh exception.
     * Việc gọi provider được thực hiện đồng bộ, không chuyển qua queue.
     *
     * @param  array{product_code: string, quantity: int, duration_days: int, protocol: string, idempotency_key: string}  $payload
     * @return array{order: array<string, mixed>, proxies: list<array<string, mixed>>}
     */
    public function order(User $user, array $payload, ProxyProduct $product): array
    {
        $shouldReport = false;
        $result = DB::transaction(function () use ($user, $payload, $product, &$shouldReport): array {
            // Chống tạo đơn và trừ tiền lặp lại khi client gửi lại cùng idempotency key.
            $existingOrder = ProxyOrder::query()
                ->with('userProxies')
                ->where('user_id', $user->id)
                ->where('idempotency_key', $payload['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if ($existingOrder instanceof ProxyOrder) {
                return $this->orderResult($existingOrder);
            }

            // Khóa sản phẩm trong suốt giao dịch để giá và cấu hình provider không bị thay đổi giữa chừng.
            $lockedProduct = ProxyProduct::query()
                ->with(['category', 'provider'])
                ->lockForUpdate()
                ->find($product->id);

            if (! $lockedProduct instanceof ProxyProduct) {
                $this->fail('product_code', 'Sản phẩm proxy không tồn tại hoặc đã ngừng bán.');
            }

            $provider = $this->validateOrder($payload, $lockedProduct);
            $unitPrice = (string) $lockedProduct->selling_price;
            $totalAmount = $this->totalAmount($unitPrice, $payload['quantity'], $payload['duration_days']);

            // Tạo đơn ở trạng thái chờ trước khi ghi nhận khoản trừ tiền từ ví người dùng.
            $order = ProxyOrder::query()->create([
                'user_id' => $user->id,
                'proxy_product_id' => $lockedProduct->id,
                'proxy_provider_id' => $provider->id,
                'order_code' => 'PXY-'.Str::upper((string) Str::ulid()),
                'idempotency_key' => $payload['idempotency_key'],
                'type' => ProxyOrder::TYPE_PURCHASE,
                'status' => ProxyOrder::STATUS_PENDING,
                'product_code' => $lockedProduct->code,
                'product_name' => $lockedProduct->name,
                'quantity' => $payload['quantity'],
                'duration_days' => $payload['duration_days'],
                'country_code' => $lockedProduct->country_code,
                'protocol' => $payload['protocol'],
                'unit_price' => $unitPrice,
                'total_amount' => $totalAmount,
                'ordered_at' => now(),
            ]);

            // WalletService khóa dòng ví, kiểm tra số dư, trừ tiền và tạo lịch sử giao dịch.
            // Nếu số dư không đủ, exception tại đây sẽ rollback cả đơn vừa tạo.
            $this->walletService->debit(
                user: $user,
                amount: (float) $totalAmount,
                referenceType: ProxyOrder::class,
                referenceId: $order->id,
                description: "Thanh toán đơn proxy {$order->order_code}",
            );

            // Gọi API provider trực tiếp. Mọi lỗi HTTP, kết nối hoặc dữ liệu đều làm transaction rollback.
            $providerResult = $this->purchaseFromProvider($provider, $lockedProduct, $payload, $order);

            // Chỉ lưu proxy cho người dùng sau khi provider trả về đủ số lượng và dữ liệu hợp lệ.
            foreach ($providerResult['proxy'] as $proxyData) {
                UserProxy::query()->create([
                    'user_id' => $user->id,
                    'proxy_product_id' => $lockedProduct->id,
                    'proxy_provider_id' => $provider->id,
                    'source_order_id' => $order->id,
                    'provider_proxy_id' => $proxyData['provider_proxy_id'],
                    'provider_code' => $proxyData['provider_code'],
                    'status' => UserProxy::STATUS_ACTIVE,
                    'country_code' => $proxyData['country_code'],
                    'protocol' => $proxyData['protocol'],
                    'host' => $proxyData['host'],
                    'port' => $proxyData['port'],
                    'username' => $proxyData['username'],
                    'password' => $proxyData['password'],
                    'response' => $proxyData['response'],
                    'expires_at' => $proxyData['expires_at'],
                ]);
            }

            // Hoàn tất đơn sau khi toàn bộ proxy đã được lưu thành công.
            $order->forceFill([
                'status' => ProxyOrder::STATUS_FULFILLED,
                'external_order_id' => collect($providerResult['proxy'])
                    ->pluck('provider_proxy_id')
                    ->implode(','),
                'fulfilled_at' => now(),
            ])->save();

            $shouldReport = true;

            return $this->orderResult($order->load('userProxies'));
        });

        if ($shouldReport) {
            $completedOrder = ProxyOrder::query()->find((int) $result['order']['id']);

            if ($completedOrder instanceof ProxyOrder) {
                $this->proxySalesReporter->reportFulfilled($completedOrder);
            }
        }

        return $result;
    }

    /**
     * Kiểm tra toàn bộ điều kiện nghiệp vụ trước khi mua proxy.
     *
     * Bao gồm trạng thái sản phẩm, danh mục, provider, phương thức mua tự động,
     * giới hạn số lượng, giao thức hỗ trợ, giá bán và mã sản phẩm phía provider.
     *
     * @param  array{product_code: string, quantity: int, duration_days: int, protocol: string, idempotency_key: string}  $payload
     */
    private function validateOrder(array $payload, ProxyProduct $product): ProxyProvider
    {
        if (! $product->is_active) {
            $this->fail('product_code', 'Sản phẩm proxy không tồn tại hoặc đã ngừng bán.');
        }

        $category = $product->category;

        if (! $category instanceof ProxyCategory || ! $category->is_active) {
            $this->fail('product_code', 'Danh mục của sản phẩm proxy hiện không hoạt động.');
        }

        $provider = $product->provider;

        if (! $provider instanceof ProxyProvider || ! $provider->is_active) {
            $this->fail('product_code', 'Sản phẩm proxy hiện chưa sẵn sàng để đặt mua.');
        }

        if ($provider->order_method !== ProxyProvider::ORDER_METHOD_AUTOMATIC) {
            $this->fail('product_code', 'Sản phẩm proxy chưa hỗ trợ đặt mua tự động.');
        }

        if ($payload['quantity'] > $product->max_quantity) {
            $this->fail('quantity', "Số lượng tối đa cho sản phẩm này là {$product->max_quantity} proxy.");
        }

        if (! in_array($payload['protocol'], $product->supportedProtocols(), true)) {
            $this->fail('protocol', 'Sản phẩm proxy không hỗ trợ giao thức đã chọn.');
        }

        if (! is_numeric($product->selling_price) || (float) $product->selling_price <= 0) {
            $this->fail('product_code', 'Giá bán của sản phẩm proxy không hợp lệ.');
        }

        if (blank($product->provider_product_code)) {
            $this->fail('product_code', 'Sản phẩm proxy chưa được cấu hình mã sản phẩm từ nhà cung cấp.');
        }

        return $provider;
    }

    /**
     * Chọn bộ xử lý phù hợp theo driver của provider và chuyển lỗi kết nối thành lỗi API.
     *
     * @param  array{product_code: string, quantity: int, duration_days: int, protocol: string, idempotency_key: string}  $payload
     * @return array{status: true, message: string, proxy: list<array{provider_proxy_id: string, provider_code: ?string, country_code: ?string, protocol: string, host: ?string, port: ?int, username: ?string, password: ?string, response: array<string, mixed>, expires_at: ?CarbonImmutable}>}
     */
    private function purchaseFromProvider(
        ProxyProvider $provider,
        ProxyProduct $product,
        array $payload,
        ProxyOrder $order,
    ): array {
        try {
            switch ($provider->driver) {
                case ProxyProvider::DRIVER_PROXY_VN:
                    $api = new ProxyVn($provider);

                    if ($this->isRotatingProduct($product)) {
                        $providerResult = $api->orderRotating([
                            'loaiproxy' => $product->provider_product_code,
                            'soluong' => $payload['quantity'],
                            'ngay' => $payload['duration_days'],
                            'type' => $payload['protocol'],
                        ]);

                        return [
                            'status' => true,
                            'message' => $providerResult['message'],
                            'proxy' => collect($providerResult['proxy'])
                                ->map(fn (array $item): array => $this->normalizeRotatingProxyData($item, $product, $payload))
                                ->values()
                                ->all(),
                        ];
                    } else {
                        $providerResult = $api->order([
                            'loaiproxy' => $product->provider_product_code,
                            'soluong' => $payload['quantity'],
                            'ngay' => $payload['duration_days'],
                            'type' => $payload['protocol'],
                        ]);
                    }

                    return [
                        'status' => true,
                        'message' => $providerResult['message'],
                        'proxy' => collect($providerResult['proxy'])
                            ->map(fn (array $item): array => $this->normalizeProxyData($item, $product, $payload))
                            ->values()
                            ->all(),
                    ];
                default:
                    throw new ApiException('Sản phẩm chưa được hỗ trợ mua auto, vui lòng liên hệ admin để được hỗ trợ.', 503);
            }
        } catch (Throwable $exception) {
            report($exception);

            throw new ApiException(
                self::PROVIDER_REQUEST_FAILED_MESSAGE,
                502,
            );
        }
    }

    /**
     * Đảm bảo provider trả về HTTP thành công trước khi phân tích nội dung response.
     */
    private function ensureSuccessfulHttpResponse(Response $response): void
    {
        if ($response->failed()) {
            throw new ApiException(
                "Nhà cung cấp proxy phản hồi lỗi HTTP {$response->status()}. Giao dịch đã được hoàn tác.",
                502,
            );
        }
    }

    /**
     * Lấy và kiểm tra URL gốc của provider để tránh gửi request đến URL sai cấu hình.
     */
    private function providerBaseUrl(ProxyProvider $provider, ?string $default = null): string
    {
        $baseUrl = trim((string) ($provider->credentials['base_url'] ?? $provider->api_base_url ?? $default ?? ''));
        $parts = parse_url($baseUrl);

        if (
            $baseUrl === ''
            || ! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || blank($parts['host'] ?? null)
        ) {
            throw new ApiException('Nhà cung cấp proxy chưa được cấu hình URL API hợp lệ.', 503);
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * Ghép URL gốc với đường dẫn API và loại bỏ dấu gạch chéo bị trùng.
     */
    private function providerEndpoint(string $baseUrl, string $path): string
    {
        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * Chuẩn hóa một proxy từ định dạng riêng của provider sang cấu trúc nội bộ.
     *
     * Bắt buộc phải có mã proxy, host và port hợp lệ. Nếu provider không trả thời
     * gian hết hạn thì hệ thống tính theo số ngày đã mua.
     *
     * @param  array<string, mixed>  $item
     * @param  array{product_code: string, quantity: int, duration_days: int, protocol: string, idempotency_key: string}  $payload
     * @return array{provider_proxy_id: string, provider_code: string, country_code: ?string, protocol: string, host: string, port: int, username: ?string, password: ?string, response: array<string, mixed>, expires_at: ?CarbonImmutable}
     */
    private function normalizeProxyData(array $item, ProxyProduct $product, array $payload): array
    {
        $providerProxyId = trim((string) ($item['idproxy'] ?? $item['provider_proxy_id'] ?? $item['id'] ?? ''));
        $host = trim((string) ($item['ip'] ?? $item['host'] ?? ''));
        $port = (int) ($item['port'] ?? 0);

        if ($providerProxyId === '' || $host === '' || $port < 1 || $port > 65535) {
            throw new ApiException('Dữ liệu proxy từ nhà cung cấp không hợp lệ. Giao dịch đã được hoàn tác.', 502);
        }

        $expiresAt = isset($item['time']) && is_numeric($item['time'])
            ? CarbonImmutable::createFromTimestamp((int) $item['time'])
            : now()->toImmutable()->addDays($payload['duration_days']);

        return [
            'provider_proxy_id' => $providerProxyId,
            'provider_code' => trim((string) ($item['provider_code'] ?? $providerProxyId)),
            'country_code' => $product->country_code,
            'protocol' => $payload['protocol'],
            'host' => $host,
            'port' => $port,
            'username' => filled($item['user'] ?? $item['username'] ?? null)
                ? (string) ($item['user'] ?? $item['username'])
                : null,
            'password' => filled($item['password'] ?? null) ? (string) $item['password'] : null,
            'response' => $item,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Chuẩn hóa key proxy xoay để lưu như mã truy cập, không tạo host hoặc tài khoản giả.
     *
     * Key được lưu trong provider_proxy_id có cast encrypted để hệ thống vẫn dùng được
     * khi đổi hoặc gia hạn. Resource chỉ công khai key này cho đúng chủ sở hữu proxy.
     *
     * @param  array<string, mixed>  $item
     * @param  array{product_code: string, quantity: int, duration_days: int, protocol: string, idempotency_key: string}  $payload
     * @return array{provider_proxy_id: string, provider_code: null, country_code: ?string, protocol: string, host: null, port: null, username: null, password: null, response: array<string, mixed>, expires_at: CarbonImmutable}
     */
    private function normalizeRotatingProxyData(array $item, ProxyProduct $product, array $payload): array
    {
        $accessKey = trim((string) ($item['keyxoay'] ?? ''));

        if ($accessKey === '') {
            throw new ApiException('Dữ liệu proxy xoay từ nhà cung cấp không hợp lệ. Giao dịch đã được hoàn tác.', 502);
        }

        return [
            'provider_proxy_id' => $accessKey,
            'provider_code' => null,
            'country_code' => $product->country_code,
            'protocol' => $payload['protocol'],
            'host' => null,
            'port' => null,
            'username' => null,
            'password' => null,
            'response' => $item,
            'expires_at' => now()->toImmutable()->addDays($payload['duration_days']),
        ];
    }

    /** Xác định sản phẩm được cấp dưới dạng key proxy xoay. */
    private function isRotatingProduct(ProxyProduct $product): bool
    {
        return ($product->settings['proxy_type'] ?? null) === 'rotating';
    }

    /**
     * Chuyển mã trạng thái nghiệp vụ của Proxy.vn thành thông báo tiếng Việt.
     */
    private function proxyVnErrorMessage(int $status): string
    {
        return match ($status) {
            101 => 'API key của nhà cung cấp proxy không hợp lệ.',
            102 => 'Số dư tại nhà cung cấp proxy không đủ. Giao dịch đã được hoàn tác.',
            103 => 'Nhà cung cấp proxy đang hết hàng. Giao dịch đã được hoàn tác.',
            201 => 'Nhà cung cấp không cấp đủ số lượng proxy. Giao dịch đã được hoàn tác.',
            default => 'Nhà cung cấp proxy xử lý đơn hàng thất bại. Giao dịch đã được hoàn tác.',
        };
    }

    /**
     * Tính tổng tiền theo công thức: đơn giá × số lượng × số ngày sử dụng.
     *
     * Ví hiện lưu hai chữ số thập phân nên số tiền được làm tròn đến hai chữ số,
     * sau đó định dạng bốn chữ số để khớp cột tiền của đơn proxy.
     */
    private function totalAmount(string $unitPrice, int $quantity, int $durationDays): string
    {
        $amount = round((float) $unitPrice * $quantity * $durationDays, 2);

        return number_format($amount, 4, '.', '');
    }

    /**
     * Chuyển model đơn hàng và các proxy đã cấp thành dữ liệu an toàn trả về API.
     *
     * @return array{order: array<string, mixed>, proxies: list<array<string, mixed>>}
     */
    private function orderResult(ProxyOrder $order): array
    {
        return [
            'order' => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'status' => $order->status,
                'product_code' => $order->product_code,
                'product_name' => $order->product_name,
                'quantity' => $order->quantity,
                'duration_days' => $order->duration_days,
                'country_code' => $order->country_code,
                'protocol' => $order->protocol,
                'unit_price' => $order->unit_price,
                'total_amount' => $order->total_amount,
                'external_order_id' => $order->external_order_id,
                'ordered_at' => $order->ordered_at?->toISOString(),
                'fulfilled_at' => $order->fulfilled_at?->toISOString(),
            ],
            'proxies' => $order->userProxies
                ->map(fn (UserProxy $proxy): array => [
                    'id' => $proxy->id,
                    'status' => $proxy->status,
                    'country_code' => $proxy->country_code,
                    'protocol' => $proxy->protocol,
                    'host' => $proxy->host,
                    'port' => $proxy->port,
                    'username' => $proxy->username,
                    'password' => $proxy->password,
                    'access_key' => blank($proxy->host) ? $proxy->provider_proxy_id : null,
                    'expires_at' => $proxy->expires_at?->toISOString(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Ném lỗi validation gắn với đúng trường để client có thể hiển thị thông báo.
     */
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
