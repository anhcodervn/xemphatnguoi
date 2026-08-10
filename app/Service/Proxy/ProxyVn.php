<?php

namespace App\Service\Proxy;

use App\Exceptions\ApiException;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\UserProxy;
use App\Utils\RandomHelper;
use App\Utils\SendMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

class ProxyVn
{
    private const REQUEST_FAILED_MESSAGE = 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.';

    /** @var array<string, string> */
    private readonly array $credentials;

    /** @var array<string, mixed> */
    private readonly array $settings;

    private readonly string $apiKey;

    private readonly string $baseUrl;

    private readonly string $purchasePath;

    private readonly string $username;

    private readonly string $password;

    /**
     * Nhận provider và gắn toàn bộ cấu hình đã được model giải mã vào service.
     *
     * Accessor credentials của ProxyProvider chịu trách nhiệm giải mã dữ liệu.
     * Không gọi Crypt tại đây để tránh giải mã hai lần và vẫn tương thích dữ liệu cũ.
     */
    public function __construct(private readonly ProxyProvider $provider)
    {
        $credentials = $provider->credentials;

        $this->credentials = is_array($credentials) ? $credentials : [];
        $this->settings = is_array($provider->settings) ? $provider->settings : [];
        $this->apiKey = trim((string) ($this->credentials['key']
            ?? $this->credentials['api_key']
            ?? $this->credentials['access_token']
            ?? ''));
        $this->baseUrl = rtrim((string) ($this->credentials['base_url']
            ?? $provider->api_base_url
            ?? 'https://proxy.vn/apiv2'), '/');
        $this->purchasePath = (string) ($this->settings['purchase_path'] ?? 'muaproxy.php');
        $this->username = (string) ($this->credentials['user'] ?? $this->credentials['username'] ?? 'random');
        $this->password = (string) ($this->credentials['password'] ?? 'random');
    }

    /**
     * Gửi yêu cầu mua và chuẩn hóa kết quả giống nhau cho cả một hoặc nhiều proxy.
     *
     * @param  array{loaiproxy: string, soluong?: int, ngay?: int, type?: string}  $payload
     * @return array{status: true, message: string, proxy: list<array<string, mixed>>}
     */
    public function order(array $payload): array
    {
        $this->ensureApiKeyIsConfigured();

        $proxyType = trim($payload['loaiproxy']);
        $quantity = (int) ($payload['soluong'] ?? 1);

        if ($proxyType === '' || $quantity < 1) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        if ($proxyType === 'rand_nha_mang') {
            $carriers = ['Viettel', 'FPT', 'VNPT'];
            $proxyType = $carriers[random_int(0, count($carriers) - 1)];
        }

        $username = $this->username === 'random' ? RandomHelper::RandText(8) : $this->username;
        $password = $this->password === 'random' ? 'dailyproxy'.RandomHelper::RandText(8) : $this->password;
        $response = $this->curlGet($this->endpoint($this->purchasePath), [
            'key' => $this->apiKey,
            'loaiproxy' => $proxyType,
            'soluong' => $quantity,
            'ngay' => $payload['ngay'] ?? 1,
            'type' => strtoupper((string) ($payload['type'] ?? 'HTTP')),
            'user' => $username,
            'password' => $password,
        ]);

        return $this->normalizeOrderResponse($response, $quantity);
    }

    /**
     * Đổi một proxy tĩnh và trả về đúng một proxy đã được provider cấp lại.
     *
     * @return array{status: true, message: string, proxy: list<array<string, mixed>>}
     */
    public function changeProxy(UserProxy $proxy, ProxyProduct $product): array
    {
        $this->ensureApiKeyIsConfigured();

        if (($product->settings['proxy_type'] ?? null) === 'rotating') {
            throw new RuntimeException('Không hỗ trợ đổi proxy cho sản phẩm xoay. Vui lòng mua lại proxy mới.');
        }

        $providerProductCode = $this->resolveProviderProductCode($proxy, $product);
        $providerProxyId = trim((string) $proxy->provider_proxy_id);

        if ($providerProductCode === '' || $providerProxyId === '') {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $username = $this->username === 'random' ? RandomHelper::RandText(8) : $this->username;
        $password = $this->password === 'random' ? 'dailyproxy'.RandomHelper::RandText(8) : $this->password;
        $response = $this->curlGet($this->endpoint((string) ($this->settings['change_path'] ?? 'doiproxy.php')), [
            'loaiproxy' => $providerProductCode,
            'loaiproxynhan' => $providerProductCode,
            'key' => $this->apiKey,
            'type' => strtoupper($proxy->protocol),
            'user' => $username,
            'password' => $password,
            'idproxy' => $providerProxyId,
        ], 'change_proxy');

        return $this->normalizeChangeResponse($response);
    }

    /**
     * Lấy proxy hiện tại từ key xoay mà không thay đổi dữ liệu đã lưu.
     *
     * @return array{status: true, message: string, proxy: string, protocol: string}
     */
    public function getRotatingProxy(UserProxy $proxy, ProxyProduct $product, ?string $protocol = null): array
    {
        if (($product->settings['proxy_type'] ?? null) !== 'rotating') {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $accessKey = trim((string) $proxy->provider_proxy_id);

        if ($accessKey === '') {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $response = $this->curlGet('https://proxyxoay.shop/api/get.php', [
            'key' => $accessKey,
            'nhamang' => trim((string) ($product->settings['rotating_carrier'] ?? 'random')) ?: 'random',
            'tinhthanh' => trim((string) ($product->settings['rotating_province'] ?? '0')) ?: '0',
            'whitelist' => trim((string) ($product->settings['rotating_whitelist'] ?? '')),
        ]);

        return $this->normalizeRotatingProxyResponse($response, $protocol ?? $proxy->protocol);
    }

    /**
     * Gia hạn proxy tĩnh hoặc proxy xoay theo số ngày đã thanh toán.
     *
     * @return array{status: true, message: string, expires_at: ?int}
     */
    public function renewProxy(UserProxy $proxy, ProxyProduct $product, int $durationDays): array
    {
        $this->ensureApiKeyIsConfigured();

        if ($durationDays < 1) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $providerProxyId = trim((string) $proxy->provider_proxy_id);

        if ($providerProxyId === '') {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        if (($product->settings['proxy_type'] ?? null) === 'rotating') {
            $duration = $this->resolveRotatingDuration($durationDays);
            $renewPath = match ($duration['period']) {
                'month' => 'apigiahanthang.php',
                'week' => 'apigiahantuan.php',
                default => 'apigiahanngay.php',
            };

            $response = $this->curlGet($this->endpoint("https://proxy.vn/proxyxoay/{$renewPath}"), [
                'key' => $this->apiKey,
                'keyxoay' => $providerProxyId,
                'thoigian' => $duration['units'],
            ], 'renew_rotating_proxy');

            return $this->normalizeRenewResponse($response);
        }

        $providerProductCode = $this->resolveProviderProductCode($proxy, $product);

        if ($providerProductCode === '') {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $response = $this->curlGet($this->endpoint((string) ($this->settings['renew_path'] ?? 'giahanproxy.php')), [
            'loaiproxy' => $providerProductCode,
            'key' => $this->apiKey,
            'ngay' => $durationDays,
            'idproxy' => $providerProxyId,
        ], 'renew_static_proxy');

        return $this->normalizeRenewResponse($response);
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $response
     * @return array{status: true, message: string, proxy: list<array<string, mixed>>}
     */
    private function normalizeChangeResponse(array $response): array
    {
        $items = array_is_list($response) ? $response : [$response];
        $changedProxies = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            $status = (int) ($item['status'] ?? 0);

            if ($status !== 100) {
                $this->failProviderResponse($item, 'change_proxy');
            }

            $providerProxyId = trim((string) ($item['idproxy'] ?? ''));
            $host = trim((string) ($item['ip'] ?? ''));
            $port = (int) ($item['port'] ?? 0);

            if ($providerProxyId === '' || $host === '' || $port < 1 || $port > 65535) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            $changedProxies[] = $item;
        }

        if (count($changedProxies) !== 1) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return [
            'status' => true,
            'message' => 'Đổi proxy thành công',
            'proxy' => $changedProxies,
        ];
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $response
     * @return array{status: true, message: string, proxy: string, protocol: string}
     */
    private function normalizeRotatingProxyResponse(array $response, string $requestedProtocol): array
    {
        if (array_is_list($response)) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        if ((int) ($response['status'] ?? 0) !== 100) {
            $this->failProviderResponse($response, 'get_rotating_proxy');
        }

        $protocol = mb_strtolower($requestedProtocol);
        $responseKey = in_array($protocol, ['socks4', 'socks5'], true) ? 'proxysocks5' : 'proxyhttp';
        $resolvedProtocol = $responseKey === 'proxysocks5' ? 'socks5' : 'http';
        $proxyValue = is_string($response[$responseKey] ?? null)
            ? trim($response[$responseKey])
            : '';
        $parts = explode(':', $proxyValue, 4);
        $host = trim((string) ($parts[0] ?? ''));
        $port = (int) ($parts[1] ?? 0);

        if ($proxyValue === '' || filter_var($host, FILTER_VALIDATE_IP) === false || $port < 1 || $port > 65535) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return [
            'status' => true,
            'message' => 'Lấy proxy xoay thành công.',
            'proxy' => $proxyValue,
            'protocol' => $resolvedProtocol,
        ];
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>  $response
     * @return array{status: true, message: string, expires_at: ?int}
     */
    private function normalizeRenewResponse(array $response): array
    {
        $items = array_is_list($response) ? $response : [$response];
        $successfulItem = null;

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            if ((int) ($item['status'] ?? 0) !== 100) {
                $this->failProviderResponse($item, 'renew_proxy');
            }

            $successfulItem = $item;
        }

        if (! is_array($successfulItem)) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $expiresAt = is_numeric($successfulItem['time'] ?? null)
            ? (int) $successfulItem['time']
            : null;

        return [
            'status' => true,
            'message' => 'Gia hạn proxy thành công',
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Chỉ lấy các phần tử status 100, bỏ phần tử tổng kết status 200 và kiểm tra số lượng.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $response
     * @return array{status: true, message: string, proxy: list<array<string, mixed>>}
     */
    private function normalizeOrderResponse(array $response, int $expectedQuantity): array
    {
        $items = array_is_list($response) ? $response : [$response];
        $proxies = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $status = (int) ($item['status'] ?? 0);

            if (in_array($status, [101, 102, 103, 104, 201], true)) {
                $this->failProviderResponse($item, 'order_proxy');
            }

            if ($status !== 100) {
                continue;
            }

            $providerProxyId = trim((string) ($item['idproxy'] ?? ''));
            $host = trim((string) ($item['ip'] ?? ''));
            $port = (int) ($item['port'] ?? 0);

            if ($providerProxyId === '' || $host === '' || $port < 1 || $port > 65535) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            $proxies[] = $item;
        }

        if (count($proxies) !== $expectedQuantity) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return [
            'status' => true,
            'message' => 'Mua hàng thành công',
            'proxy' => array_values($proxies),
        ];
    }

    public function orderRotating(array $payload): array
    {
        $this->ensureApiKeyIsConfigured();

        $durationDays = (int) ($payload['ngay'] ?? 1);
        $quantity = (int) ($payload['soluong'] ?? 1);

        if ($durationDays < 1) {
            throw new ApiException('Số ngày mua proxy xoay phải lớn hơn 0.');
        }

        $duration = $this->resolveRotatingDuration($durationDays);
        $url = match ($duration['period']) {
            'month' => 'apimuathang.php',
            'week' => 'apimuatuan.php',
            default => 'apimuangay.php',
        };

        if ($quantity < 1) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        $response = $this->usesDemoResponse()
            ? $this->demoResponse($quantity)
            : $this->curlGet($this->endpoint("https://proxy.vn/proxyxoay/{$url}"), [
                'key' => $this->apiKey,
                'thoigian' => $duration['units'],
                'soluong' => $quantity,
            ], 'order_rotating');

        return $this->normalizeRotatingOrderResponse($response, $quantity);
    }

    /**
     * Tạo response mẫu đúng số lượng để kiểm tra luồng proxy xoay mà không gọi provider.
     *
     * @return list<array<string, int|string>>
     */
    private function demoResponse(int $quantity): array
    {
        $fixedKeys = [
            'jfLFRqLnPgnDaRWzmdxnga',
            'EVZFDvVqohncMpDUEzrqjt',
        ];
        $response = [];

        for ($index = 0; $index < $quantity; $index++) {
            $response[] = [
                'status' => 100,
                'keyxoay' => $fixedKeys[$index] ?? substr(hash('sha256', "proxy-vn-demo-{$index}"), 0, 22),
            ];
        }

        $response[] = [
            'status' => 100,
            'soluong' => $quantity,
            'comen' => "successful transaction {$quantity} key xoay",
        ];

        return $response;
    }

    /** Chỉ bật demo khi settings của provider yêu cầu rõ ràng. */
    private function usesDemoResponse(): bool
    {
        return filter_var(
            $this->settings['use_demo_response'] ?? false,
            FILTER_VALIDATE_BOOL,
        );
    }

    /**
     * Chuẩn hóa danh sách key proxy xoay và loại bỏ phần tử tổng kết của Proxy.vn.
     *
     * @param  array<string, mixed>|list<array<string, mixed>>  $response
     * @return array{status: true, message: string, proxy: list<array<string, mixed>>}
     */
    private function normalizeRotatingOrderResponse(array $response, int $expectedQuantity): array
    {
        $items = array_is_list($response) ? $response : [$response];
        $proxies = [];
        $accessKeys = [];
        $reportedQuantity = null;
        $summaryCount = 0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            $status = (int) ($item['status'] ?? 0);

            if (! in_array($status, [100, 200], true)) {
                $this->failProviderResponse($item, 'order_rotating_proxy');
            }

            $accessKey = is_string($item['keyxoay'] ?? null)
                ? trim($item['keyxoay'])
                : '';
            $hasAccessKey = $accessKey !== '';
            $hasReportedQuantity = array_key_exists('soluong', $item);
            $hasSummaryComment = is_string($item['comen'] ?? null)
                && trim($item['comen']) !== '';
            $isSummary = $hasReportedQuantity || $hasSummaryComment;

            if ($hasAccessKey === $isSummary) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            if ($hasAccessKey) {
                if ($status !== 100 || strlen($accessKey) > 255 || in_array($accessKey, $accessKeys, true)) {
                    throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
                }

                $accessKeys[] = $accessKey;
                $proxies[] = $item;

                continue;
            }

            if (! in_array($status, [100, 200], true)) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            if ($hasReportedQuantity) {
                $quantityValue = $item['soluong'];
                $hasIntegerQuantity = is_int($quantityValue)
                    || (is_string($quantityValue) && ctype_digit($quantityValue));

                if (! $hasIntegerQuantity) {
                    throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
                }

                $reportedQuantity = (int) $quantityValue;
            } else {
                $reportedQuantity = $expectedQuantity;
            }

            $summaryCount++;
        }

        if (
            count($proxies) !== $expectedQuantity
            || $summaryCount !== 1
            || $reportedQuantity !== $expectedQuantity
        ) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return [
            'status' => true,
            'message' => 'Mua hàng thành công',
            'proxy' => array_values($proxies),
        ];
    }

    /** Giữ nguyên URL tuyệt đối hoặc ghép đường dẫn tương đối với base URL. */
    private function endpoint(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * Gửi yêu cầu GET và trả về dữ liệu JSON đã được giải mã.
     *
     * @param  array<string, bool|float|int|string|null>  $query
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function curlGet(string $url, array $query = [], ?string $logOperation = null): array
    {
        $this->ensureValidHttpUrl($url);

        if ($logOperation !== null) {
            Log::info("proxy_vn.{$logOperation}.request", [
                'method' => 'GET',
                'endpoint' => $url,
                'payload' => $this->sanitizeProviderDataForLog($query),
            ]);
        }

        try {
            $response = Http::acceptJson()
                ->withUserAgent('DailyProxy/1.0')
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($url, $query);

        } catch (Throwable $exception) {
            if ($logOperation !== null) {
                Log::warning("proxy_vn.{$logOperation}.connection_failed", [
                    'endpoint' => $url,
                    'exception' => $exception::class,
                ]);
            }

            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        if ($logOperation !== null) {
            Log::info("proxy_vn.{$logOperation}.response", [
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'body_length' => strlen($response->body()),
                'body' => $this->sanitizeProviderResponseBodyForLog($response->body()),
            ]);
        }

        if ($response->failed()) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return $this->decodeJsonResponse($response->body());
    }

    private function resolveProviderProductCode(UserProxy $proxy, ProxyProduct $product): string
    {
        $providerProductCode = trim((string) $product->provider_product_code);

        if ($providerProductCode !== 'rand_nha_mang') {
            return $providerProductCode;
        }

        $currentCarrier = trim((string) data_get($proxy->response, 'loaiproxy'));

        return $currentCarrier;
    }

    /**
     * Quy đổi tổng số ngày sang đơn vị lớn nhất mà API proxy xoay hỗ trợ.
     *
     * @return array{period: 'day'|'week'|'month', units: int}
     */
    private function resolveRotatingDuration(int $durationDays): array
    {
        if ($durationDays < 1) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        if ($durationDays % 30 === 0) {
            return ['period' => 'month', 'units' => intdiv($durationDays, 30)];
        }

        if ($durationDays % 7 === 0) {
            return ['period' => 'week', 'units' => intdiv($durationDays, 7)];
        }

        return ['period' => 'day', 'units' => $durationDays];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeProviderDataForLog(array $data): array
    {
        $sensitiveKeys = ['key', 'api_key', 'access_token', 'idproxy', 'ip', 'proxy', 'user', 'username', 'password', 'keyxoay'];

        foreach ($data as $key => $value) {
            if (in_array(mb_strtolower((string) $key), $sensitiveKeys, true)) {
                $data[$key] = filled($value) ? '[REDACTED]' : null;
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeProviderDataForLog($value);
            }
        }

        return $data;
    }

    /** @return array<string, mixed>|list<array<string, mixed>>|string */
    private function sanitizeProviderResponseBodyForLog(string $responseBody): array|string
    {
        if (trim($responseBody) === '') {
            return '';
        }

        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            try {
                $decoded = $this->decodeConcatenatedJsonObjects($responseBody);
            } catch (RuntimeException) {
                return '[NON_JSON_RESPONSE]';
            }
        }

        return is_array($decoded)
            ? $this->sanitizeProviderDataForLog($decoded)
            : '[INVALID_JSON_RESPONSE]';
    }

    /**
     * Gửi yêu cầu POST dạng application/x-www-form-urlencoded và trả về dữ liệu JSON.
     *
     * @param  array<string, bool|float|int|string|null>  $payload
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function curlPost(string $url, array $payload = [], ?string $logOperation = null): array
    {
        $this->ensureValidHttpUrl($url);

        if ($logOperation !== null) {
            Log::info("proxy_vn.{$logOperation}.request", [
                'method' => 'POST',
                'endpoint' => $url,
                'payload' => $this->sanitizeProviderDataForLog($payload),
            ]);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->withUserAgent('DailyProxy/1.0')
                ->withOptions(['allow_redirects' => false])
                ->connectTimeout(5)
                ->timeout(20)
                ->post($url, $payload);
        } catch (Throwable $exception) {
            if ($logOperation !== null) {
                Log::warning("proxy_vn.{$logOperation}.connection_failed", [
                    'endpoint' => $url,
                    'exception' => $exception::class,
                ]);
            }

            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        if ($logOperation !== null) {
            Log::info("proxy_vn.{$logOperation}.response", [
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'body_length' => strlen($response->body()),
                'body' => $this->sanitizeProviderResponseBodyForLog($response->body()),
            ]);
        }

        if ($response->failed()) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return $this->decodeJsonResponse($response->body());
    }

    /**
     * Chỉ cho phép gửi request đến URL HTTP hoặc HTTPS hợp lệ.
     */
    private function ensureValidHttpUrl(string $url): void
    {
        $parts = parse_url($url);

        if (
            ! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || blank($parts['host'] ?? null)
        ) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }
    }

    /** Đảm bảo provider đã có API key trước khi tạo giao dịch bên ngoài. */
    private function ensureApiKeyIsConfigured(): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }
    }

    /** @param array<string, mixed> $response */
    private function failProviderResponse(array $response, string $operation): never
    {
        $status = (int) ($response['status'] ?? 0);
        $comment = trim((string) ($response['comen'] ?? $response['comment'] ?? $response['message'] ?? ''));
        $hasInsufficientBalanceMessage = Str::contains(Str::lower($comment), [
            'not enough money',
            'insufficient balance',
            'không đủ tiền',
        ]);

        if ($status === 102 || $hasInsufficientBalanceMessage) {
            SendMessage::sendProviderReport('Nhà cung cấp không đủ số dư', [
                'Provider ID' => $this->provider->id,
                'Nhà cung cấp' => $this->provider->name,
                'Mã provider' => $this->provider->code,
                'Thao tác' => $operation,
                'Mã phản hồi' => $status,
                'Thông báo' => 'Provider trả về trạng thái không đủ tiền.',
            ]);
        }

        throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
    }

    /**
     * Giải mã JSON và báo lỗi rõ ràng khi provider trả dữ liệu không hợp lệ.
     *
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function decodeJsonResponse(string $responseBody): array
    {
        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $decoded = $this->decodeConcatenatedJsonObjects($responseBody);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return $decoded;
    }

    /**
     * Giải mã định dạng nhiều JSON object nối tiếp mà API proxy xoay đang trả về.
     *
     * Bộ tách theo dõi chuỗi và ký tự escape để dấu ngoặc trong nội dung JSON không
     * bị hiểu nhầm là ranh giới giữa hai object.
     *
     * @return list<array<string, mixed>>
     */
    private function decodeConcatenatedJsonObjects(string $responseBody): array
    {
        $documents = [];
        $documentStart = null;
        $depth = 0;
        $insideString = false;
        $isEscaped = false;
        $length = strlen($responseBody);

        for ($index = 0; $index < $length; $index++) {
            $character = $responseBody[$index];

            if ($documentStart === null) {
                if (ctype_space($character)) {
                    continue;
                }

                if ($character !== '{') {
                    throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
                }

                $documentStart = $index;
                $depth = 1;

                continue;
            }

            if ($insideString) {
                if ($isEscaped) {
                    $isEscaped = false;
                } elseif ($character === '\\') {
                    $isEscaped = true;
                } elseif ($character === '"') {
                    $insideString = false;
                }

                continue;
            }

            if ($character === '"') {
                $insideString = true;

                continue;
            }

            if ($character === '{') {
                $depth++;
            } elseif ($character === '}') {
                $depth--;
            }

            if ($depth < 0) {
                throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
            }

            if ($depth === 0) {
                $document = substr($responseBody, $documentStart, $index - $documentStart + 1);

                try {
                    $decodedDocument = json_decode($document, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
                }

                if (! is_array($decodedDocument)) {
                    throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
                }

                $documents[] = $decodedDocument;
                $documentStart = null;
            }
        }

        if ($documentStart !== null || $insideString || $documents === []) {
            throw new RuntimeException(self::REQUEST_FAILED_MESSAGE);
        }

        return $documents;
    }
}
