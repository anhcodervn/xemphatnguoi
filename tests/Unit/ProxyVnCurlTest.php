<?php

use App\Features\Client\Proxy\Services\OrderService;
use App\Models\ProxyProduct;
use App\Models\ProxyProvider;
use App\Models\UserProxy;
use App\Service\Proxy\ProxyVn;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @return array{0: ProxyVn, 1: ProxyProvider}
 */
function proxyVnServiceWithEncryptedCredentials(): array
{
    $provider = new ProxyProvider([
        'name' => 'Proxy VN',
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'api_base_url' => 'https://fallback.example/api',
        'credentials' => [
            'base_url' => 'https://custom.example/apiv2',
            'api_key' => 'encrypted-api-key',
            'user' => 'custom-user',
            'password' => 'custom-password',
            'custom_secret' => 'custom-secret-value',
        ],
        'settings' => [
            'purchase_path' => '/custom-purchase.php',
        ],
    ]);

    return [new ProxyVn($provider), $provider];
}

/** @return array<string, string> */
function proxyVnRequestQuery(Request $request): array
{
    parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

    return $query;
}

test('constructor nhận credentials đã giải mã từ provider và gắn vào cấu hình nội bộ', function () {
    [$service, $provider] = proxyVnServiceWithEncryptedCredentials();
    $reflection = new ReflectionClass($service);
    $rawCredentials = (string) $provider->getAttributes()['credentials'];

    expect($reflection->getProperty('apiKey')->getValue($service))->toBe('encrypted-api-key')
        ->and($reflection->getProperty('baseUrl')->getValue($service))->toBe('https://custom.example/apiv2')
        ->and($reflection->getProperty('purchasePath')->getValue($service))->toBe('/custom-purchase.php')
        ->and($reflection->getProperty('username')->getValue($service))->toBe('custom-user')
        ->and($reflection->getProperty('password')->getValue($service))->toBe('custom-password')
        ->and($reflection->getProperty('credentials')->getValue($service))->toHaveKey('custom_secret', 'custom-secret-value')
        ->and($rawCredentials)->toContain('__encrypted')
        ->and($rawCredentials)->not->toContain('encrypted-api-key')
        ->and($rawCredentials)->not->toContain('custom-secret-value');
});

test('proxy vn định nghĩa các hàm curl get và post ở phạm vi private', function () {
    $reflection = new ReflectionClass(ProxyVn::class);

    expect($reflection->getMethod('curlGet')->isPrivate())->toBeTrue()
        ->and($reflection->getMethod('curlPost')->isPrivate())->toBeTrue();
});

test('endpoint giữ nguyên url tuyệt đối và chỉ ghép base url cho đường dẫn tương đối', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'endpoint');

    expect($method->invoke($service, 'https://proxy.vn/proxyxoay/apimuangay.php'))
        ->toBe('https://proxy.vn/proxyxoay/apimuangay.php')
        ->and($method->invoke($service, '/muaproxy.php'))
        ->toBe('https://custom.example/apiv2/muaproxy.php');
});

test('proxy vn giải mã json hợp lệ và từ chối json lỗi', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'decodeJsonResponse');

    expect($method->invoke($service, '{"status":100,"ip":"127.0.0.1"}'))
        ->toBe(['status' => 100, 'ip' => '127.0.0.1']);

    expect(fn () => $method->invoke($service, 'not-json'))
        ->toThrow(RuntimeException::class, 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.');
});

test('proxy vn cảnh báo webhook khi provider trả trạng thái không đủ tiền', function () {
    app()->detectEnvironment(fn (): string => 'local');
    $webhookUrl = 'https://discord.com/api/webhooks/provider-alert';
    config()->set('services.discord.channels.provider', $webhookUrl);
    Http::preventStrayRequests();
    Http::fake([$webhookUrl => Http::response([], 204)]);
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'failProviderResponse');

    try {
        expect(fn () => $method->invoke($service, [
            'status' => 102,
            'comen' => 'Not enough money',
        ], 'order_proxy'))->toThrow(RuntimeException::class);

        Http::assertSent(fn (Request $request): bool => $request->url() === $webhookUrl
            && str_contains((string) $request['content'], 'Nhà cung cấp không đủ số dư')
            && str_contains((string) $request['content'], 'Proxy VN')
            && str_contains((string) $request['content'], '102')
            && ! str_contains((string) $request['content'], 'encrypted-api-key'));
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }
});

test('proxy vn giải mã response gồm nhiều json object nối tiếp', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'decodeJsonResponse');
    $response = <<<'JSON'
{"status":100,"keyxoay":"jfLFRqLnPgnDaRWzmdxnga"}{"status":100,"keyxoay":"EVZFDvVqohncMpDUEzrqjt"}{"status":100,"soluong":2,"comen":"successful transaction 2 key xoay"}
JSON;

    $decoded = $method->invoke($service, $response);

    expect($decoded)->toHaveCount(3)
        ->and(array_column($decoded, 'status'))->toBe([100, 100, 100])
        ->and(array_column(array_slice($decoded, 0, 2), 'keyxoay'))
        ->toBe(['jfLFRqLnPgnDaRWzmdxnga', 'EVZFDvVqohncMpDUEzrqjt']);
});

test('chuẩn hóa response mua proxy xoay thành contract của order service', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeRotatingOrderResponse');
    $response = [
        ['status' => 100, 'keyxoay' => 'jfLFRqLnPgnDaRWzmdxnga'],
        ['status' => 100, 'keyxoay' => 'EVZFDvVqohncMpDUEzrqjt'],
        ['status' => 100, 'soluong' => 2, 'comen' => 'successful transaction 2 key xoay'],
    ];

    $result = $method->invoke($service, $response, 2);

    expect($result['status'])->toBeTrue()
        ->and($result['message'])->toBe('Mua hàng thành công')
        ->and($result['proxy'])->toBe([
            ['status' => 100, 'keyxoay' => 'jfLFRqLnPgnDaRWzmdxnga'],
            ['status' => 100, 'keyxoay' => 'EVZFDvVqohncMpDUEzrqjt'],
        ]);
});

test('chấp nhận status tổng kết 100 hoặc 200 của proxy xoay', function (int $summaryStatus) {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeRotatingOrderResponse');

    $result = $method->invoke($service, [
        ['status' => 100, 'keyxoay' => 'rwywzSOvFNZOWDVJJBrQRb'],
        ['status' => $summaryStatus, 'soluong' => 1, 'comen' => 'successful transaction 1 key xoay'],
    ], 1);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'])->toBe([
            ['status' => 100, 'keyxoay' => 'rwywzSOvFNZOWDVJJBrQRb'],
        ]);
})->with([
    'summary status 100' => 100,
    'summary status 200' => 200,
]);

test('từ chối status lỗi dù response proxy xoay có trường số lượng', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeRotatingOrderResponse');

    expect(fn () => $method->invoke($service, [
        ['status' => 100, 'keyxoay' => 'rwywzSOvFNZOWDVJJBrQRb'],
        ['status' => 201, 'soluong' => 1, 'comen' => 'provider rejected the order'],
    ], 1))->toThrow(RuntimeException::class, 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.');
});

test('chấp nhận phần tử tổng kết proxy xoay không có trường số lượng', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeRotatingOrderResponse');

    $result = $method->invoke($service, [
        ['status' => 100, 'keyxoay' => 'rwywzSOvFNZOWDVJJBrQRb'],
        ['status' => 100, 'comen' => 'successful transaction 1 key xoay'],
    ], 1);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'])->toBe([
            ['status' => 100, 'keyxoay' => 'rwywzSOvFNZOWDVJJBrQRb'],
        ]);
});

test('order service lưu proxy xoay bằng access key và không tạo thông tin xác thực giả', function () {
    $service = (new ReflectionClass(OrderService::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod($service, 'normalizeRotatingProxyData');
    $product = new ProxyProduct([
        'country_code' => 'VN',
        'settings' => ['proxy_type' => 'rotating'],
    ]);

    $result = $method->invoke($service, [
        'status' => 100,
        'keyxoay' => 'jfLFRqLnPgnDaRWzmdxnga',
    ], $product, [
        'product_code' => 'rotating-product',
        'quantity' => 1,
        'duration_days' => 7,
        'protocol' => 'http',
        'idempotency_key' => 'd652b7dc-9717-48e2-ae57-7f6493a981e7',
    ]);

    expect($result['provider_proxy_id'])->toBe('jfLFRqLnPgnDaRWzmdxnga')
        ->and($result['provider_code'])->toBeNull()
        ->and($result['host'])->toBeNull()
        ->and($result['port'])->toBeNull()
        ->and($result['username'])->toBeNull()
        ->and($result['password'])->toBeNull();
});

test('chuẩn hóa giống nhau khi mua một hoặc nhiều proxy', function (int $quantity) {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeOrderResponse');
    $response = [];

    for ($index = 1; $index <= $quantity; $index++) {
        $response[] = [
            'status' => 100,
            'loaiproxy' => 'FPT',
            'idproxy' => 12000 + $index,
            'ip' => "113.22.201.{$index}",
            'port' => 32000 + $index,
            'user' => 'proxy-user',
            'password' => 'proxy-password',
            'type' => 'HTTPS',
            'proxy' => "113.22.201.{$index}:".(32000 + $index).':proxy-user:proxy-password',
            'time' => 1786336881,
        ];
    }

    $response[] = [
        'status' => 200,
        'comen' => "You have successfully purchased {$quantity}",
    ];

    $result = $method->invoke($service, $response, $quantity);

    expect($result)
        ->toHaveKeys(['status', 'message', 'proxy'])
        ->and($result['status'])->toBeTrue()
        ->and($result['message'])->toBe('Mua hàng thành công')
        ->and($result['proxy'])->toHaveCount($quantity)
        ->and(array_column($result['proxy'], 'status'))->each->toBe(100);
})->with([
    'một proxy' => 1,
    'nhiều proxy' => 2,
]);

test('mua proxy tĩnh gọi provider thật qua transport có thể fake', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://custom.example/apiv2/custom-purchase.php*' => Http::response([
            ['status' => 100, 'idproxy' => 6903, 'ip' => '14.247.49.139', 'port' => 38811],
            ['status' => 200, 'comen' => 'You have successfully purchased 1'],
        ]),
    ]);
    [$service] = proxyVnServiceWithEncryptedCredentials();

    $result = $service->order([
        'loaiproxy' => 'VNPT',
        'soluong' => 1,
        'ngay' => 1,
        'type' => 'HTTP',
    ]);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'])->toHaveCount(1)
        ->and($result['proxy'][0]['idproxy'])->toBe(6903);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request['key'] === 'encrypted-api-key'
        && $request['loaiproxy'] === 'VNPT'
        && $request['soluong'] === 1);
});

test('đổi proxy tĩnh gọi đúng api và chuẩn hóa response proxy vn', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://custom.example/apiv2/doiproxy.php*' => Http::response([
            'status' => 100,
            'loaiproxy' => 'Viettel',
            'idproxy' => 2772,
            'ip' => '27.73.88.211',
            'port' => 35270,
            'user' => 'mdtrong',
            'password' => 'pass',
            'type' => 'HTTPS',
            'proxy' => '27.73.88.211:35270:mdtrong:pass',
        ]),
    ]);
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $product = new ProxyProduct(['provider_product_code' => 'Viettel']);
    $proxy = new UserProxy([
        'provider_proxy_id' => '2772',
        'protocol' => 'https',
    ]);

    $result = $service->changeProxy($proxy, $product);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'])->toHaveCount(1)
        ->and($result['proxy'][0]['idproxy'])->toBe(2772)
        ->and($result['proxy'][0]['ip'])->toBe('27.73.88.211');

    Http::assertSent(function (Request $request): bool {
        $query = proxyVnRequestQuery($request);

        return $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://custom.example/apiv2/doiproxy.php?')
            && ($query['key'] ?? null) === 'encrypted-api-key'
            && ($query['loaiproxy'] ?? null) === 'Viettel'
            && ($query['loaiproxynhan'] ?? null) === 'Viettel'
            && ($query['idproxy'] ?? null) === '2772'
            && ($query['type'] ?? null) === 'HTTPS';
    });
});

test('gia hạn proxy xoay chọn đúng api và đơn vị thời gian provider', function (int $durationDays, string $endpoint, int $durationUnits) {
    Http::preventStrayRequests();
    Http::fake([
        "https://proxy.vn/proxyxoay/{$endpoint}*" => Http::response([
            'status' => 100,
            'comen' => 'Renewal successful',
        ]),
    ]);
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $product = new ProxyProduct([
        'provider_product_code' => null,
        'settings' => ['proxy_type' => 'rotating'],
    ]);
    $proxy = new UserProxy([
        'provider_proxy_id' => 'rotating-access-key',
        'protocol' => 'http',
    ]);

    $result = $service->renewProxy($proxy, $product, $durationDays);

    expect($result['status'])->toBeTrue()
        ->and($result['expires_at'])->toBeNull();

    Http::assertSent(function (Request $request) use ($endpoint, $durationUnits): bool {
        $query = proxyVnRequestQuery($request);

        return $request->method() === 'GET'
            && str_starts_with($request->url(), "https://proxy.vn/proxyxoay/{$endpoint}?")
            && ($query['key'] ?? null) === 'encrypted-api-key'
            && ($query['keyxoay'] ?? null) === 'rotating-access-key'
            && ($query['thoigian'] ?? null) === (string) $durationUnits;
    });
})->with([
    '1 ngày' => [1, 'apigiahanngay.php', 1],
    '15 ngày' => [15, 'apigiahanngay.php', 15],
    '1 tuần' => [7, 'apigiahantuan.php', 1],
    '2 tuần' => [14, 'apigiahantuan.php', 2],
    '1 tháng' => [30, 'apigiahanthang.php', 1],
    '2 tháng' => [60, 'apigiahanthang.php', 2],
    'ưu tiên tháng khi chia hết cả tháng và tuần' => [210, 'apigiahanthang.php', 7],
]);

test('gia hạn proxy tĩnh gọi đúng api provider', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://custom.example/apiv2/giahanproxy.php*' => Http::response([
            'status' => 100,
            'loaiproxy' => 'Viettel',
            'idproxy' => 2772,
            'ip' => '27.73.88.211',
            'port' => 35270,
            'user' => 'mdtrong',
            'password' => 'pass',
            'type' => 'HTTPS',
            'proxy' => '27.73.88.211:35270:234mdtrong:pass',
        ]),
    ]);
    Log::spy();
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $product = new ProxyProduct(['provider_product_code' => 'rand_nha_mang']);
    $proxy = new UserProxy([
        'provider_proxy_id' => '2772',
        'protocol' => 'https',
        'response' => ['status' => 100, 'loaiproxy' => '4Gvinaphone'],
    ]);

    $result = $service->renewProxy($proxy, $product, 30);

    expect($result['status'])->toBeTrue()
        ->and($result['expires_at'])->toBeNull();

    Http::assertSent(function (Request $request): bool {
        $query = proxyVnRequestQuery($request);

        return $request->method() === 'GET'
            && str_starts_with($request->url(), 'https://custom.example/apiv2/giahanproxy.php?')
            && ($query['key'] ?? null) === 'encrypted-api-key'
            && ($query['loaiproxy'] ?? null) === '4Gvinaphone'
            && ($query['idproxy'] ?? null) === '2772'
            && ($query['ngay'] ?? null) === '30';
    });

    Log::shouldHaveReceived('info')->with('proxy_vn.renew_static_proxy.response', Mockery::on(
        fn (array $context): bool => $context['http_status'] === 200
            && data_get($context, 'body.status') === 100
            && data_get($context, 'body.idproxy') === '[REDACTED]'
            && data_get($context, 'body.ip') === '[REDACTED]'
            && data_get($context, 'body.user') === '[REDACTED]'
            && data_get($context, 'body.password') === '[REDACTED]'
            && data_get($context, 'body.proxy') === '[REDACTED]',
    ));
});

test('từ chối response đổi proxy thiếu dữ liệu kết nối', function () {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeChangeResponse');

    expect(fn () => $method->invoke($service, [
        'status' => 100,
        'idproxy' => 2772,
        'ip' => '',
        'port' => 0,
    ]))->toThrow(RuntimeException::class);
});

test('mua proxy xoay chọn đúng api và đơn vị thời gian provider', function (int $durationDays, string $endpoint, int $durationUnits) {
    Http::preventStrayRequests();
    Http::fake([
        "https://proxy.vn/proxyxoay/{$endpoint}*" => Http::response(
            '{"status":100,"keyxoay":"jfLFRqLnPgnDaRWzmdxnga"}{"status":200,"soluong":1,"comen":"successful transaction 1 key xoay"}',
        ),
    ]);
    [$service] = proxyVnServiceWithEncryptedCredentials();

    $result = $service->orderRotating([
        'soluong' => 1,
        'ngay' => $durationDays,
    ]);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'])->toBe([
            ['status' => 100, 'keyxoay' => 'jfLFRqLnPgnDaRWzmdxnga'],
        ]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && str_starts_with($request->url(), "https://proxy.vn/proxyxoay/{$endpoint}?")
        && $request['thoigian'] === $durationUnits
        && $request['soluong'] === 1);
})->with([
    '1 ngày' => [1, 'apimuangay.php', 1],
    '15 ngày' => [15, 'apimuangay.php', 15],
    '1 tuần' => [7, 'apimuatuan.php', 1],
    '2 tuần' => [14, 'apimuatuan.php', 2],
    '1 tháng' => [30, 'apimuathang.php', 1],
    '2 tháng' => [60, 'apimuathang.php', 2],
    'chia hết cả tháng và tuần thì ưu tiên tháng' => [210, 'apimuathang.php', 7],
]);

test('ghi log payload và response mua proxy xoay nhưng che toàn bộ key bí mật', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy.vn/proxyxoay/apimuangay.php*' => Http::response(
            '{"status":102,"soluong":1,"comen":"provider rejected the order"}',
        ),
    ]);
    [$service] = proxyVnServiceWithEncryptedCredentials();
    Log::spy();

    expect(fn () => $service->orderRotating([
        'soluong' => 1,
        'ngay' => 1,
    ]))->toThrow(RuntimeException::class);

    Log::shouldHaveReceived('info')->with(
        'proxy_vn.order_rotating.request',
        Mockery::on(fn (array $context): bool => $context['method'] === 'GET'
            && data_get($context, 'payload.key') === '[REDACTED]'
            && data_get($context, 'payload.soluong') === 1
            && data_get($context, 'payload.thoigian') === 1
            && ! str_contains((string) json_encode($context), 'encrypted-api-key')),
    )->once();

    Log::shouldHaveReceived('info')->with(
        'proxy_vn.order_rotating.response',
        Mockery::on(fn (array $context): bool => $context['http_status'] === 200
            && data_get($context, 'body.status') === 102
            && data_get($context, 'body.soluong') === 1
            && data_get($context, 'body.comen') === 'provider rejected the order'),
    )->once();
});

test('demo proxy xoay trả đúng số lượng và không gửi request provider', function (int $quantity) {
    Http::preventStrayRequests();
    $provider = new ProxyProvider([
        'name' => 'Proxy VN demo',
        'driver' => ProxyProvider::DRIVER_PROXY_VN,
        'credentials' => ['key' => 'demo-provider-key'],
        'settings' => ['use_demo_response' => true],
    ]);
    $service = new ProxyVn($provider);

    $result = $service->orderRotating([
        'soluong' => $quantity,
        'ngay' => 1,
    ]);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'])->toHaveCount($quantity)
        ->and(array_column($result['proxy'], 'keyxoay'))->each->toBeString();

    Http::assertNothingSent();
})->with([
    'một key' => 1,
    'hai key' => 2,
]);

test('từ chối response thiếu proxy hoặc có mã lỗi', function (array $response, int $quantity) {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, 'normalizeOrderResponse');

    expect(fn () => $method->invoke($service, $response, $quantity))
        ->toThrow(RuntimeException::class, 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.');
})->with([
    'thiếu số lượng' => [[
        ['status' => 100, 'idproxy' => 1, 'ip' => '127.0.0.1', 'port' => 8080],
        ['status' => 200],
    ], 2],
    'provider báo lỗi' => [[['status' => 102]], 1],
]);

test('curl get và post chỉ cho phép giao thức http hoặc https', function (string $methodName, array $payload) {
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $method = new ReflectionMethod($service, $methodName);

    expect(fn () => $method->invoke($service, 'file:///tmp/proxy-vn.json', $payload))
        ->toThrow(RuntimeException::class, 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.');
})->with([
    'GET' => ['curlGet', ['key' => 'secret']],
    'POST' => ['curlPost', ['key' => 'secret']],
]);

test('đổi sản phẩm random bằng nhà mạng thực và che dữ liệu nhạy cảm trong log', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://custom.example/apiv2/doiproxy.php*' => Http::response([
            'status' => 100,
            'loaiproxy' => 'VNPT',
            'idproxy' => 2772,
            'ip' => '27.73.88.211',
            'port' => 35270,
            'user' => 'new-user',
            'password' => 'new-password',
            'type' => 'HTTPS',
            'proxy' => '27.73.88.211:35270:new-user:new-password',
        ]),
    ]);
    Log::spy();
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $product = new ProxyProduct(['provider_product_code' => 'rand_nha_mang']);
    $proxy = new UserProxy([
        'provider_proxy_id' => 'old-provider-proxy-id',
        'protocol' => 'https',
        'response' => ['status' => 100, 'loaiproxy' => 'VNPT'],
    ]);

    $result = $service->changeProxy($proxy, $product);

    expect($result['status'])->toBeTrue()
        ->and($result['proxy'][0]['loaiproxy'])->toBe('VNPT');

    Http::assertSent(function (Request $request): bool {
        $query = proxyVnRequestQuery($request);

        return ($query['loaiproxy'] ?? null) === 'VNPT'
            && ($query['loaiproxynhan'] ?? null) === 'VNPT'
            && ($query['idproxy'] ?? null) === 'old-provider-proxy-id';
    });

    Log::shouldHaveReceived('info')->with('proxy_vn.change_proxy.request', Mockery::on(
        fn (array $context): bool => data_get($context, 'payload.loaiproxy') === 'VNPT'
            && data_get($context, 'payload.key') === '[REDACTED]'
            && data_get($context, 'payload.idproxy') === '[REDACTED]'
            && data_get($context, 'payload.user') === '[REDACTED]'
            && data_get($context, 'payload.password') === '[REDACTED]',
    ));
    Log::shouldHaveReceived('info')->with('proxy_vn.change_proxy.response', Mockery::on(
        fn (array $context): bool => $context['http_status'] === 200
            && data_get($context, 'body.loaiproxy') === 'VNPT'
            && data_get($context, 'body.idproxy') === '[REDACTED]'
            && data_get($context, 'body.ip') === '[REDACTED]'
            && data_get($context, 'body.user') === '[REDACTED]'
            && data_get($context, 'body.password') === '[REDACTED]'
            && data_get($context, 'body.proxy') === '[REDACTED]',
    ));
});

test('ghi lại response rỗng trước khi báo lỗi giải mã provider', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://custom.example/apiv2/doiproxy.php*' => Http::response('', 200),
    ]);
    Log::spy();
    [$service] = proxyVnServiceWithEncryptedCredentials();
    $product = new ProxyProduct(['provider_product_code' => 'VNPT']);
    $proxy = new UserProxy([
        'provider_proxy_id' => 'old-provider-proxy-id',
        'protocol' => 'http',
    ]);

    expect(fn () => $service->changeProxy($proxy, $product))->toThrow(RuntimeException::class);

    Log::shouldHaveReceived('info')->with('proxy_vn.change_proxy.response', Mockery::on(
        fn (array $context): bool => $context['http_status'] === 200
            && $context['body_length'] === 0
            && $context['body'] === '',
    ));
});
