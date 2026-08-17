<?php

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\LookupStatus;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\TrafficFineConfigurationException;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceRegistry;
use App\Features\TrafficFine\Services\Source\Xephatnguoi\XephatnguoiSource;
use App\Models\ApiKey;
use App\Models\TrafficFineResult;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.cache.store' => 'array',
        'traffic-fines.cache.ttl' => 86400,
        'traffic-fines.cache.error_ttl' => 60,
    ]);

    Cache::store('array')->flush();
});

/** @param list<array<string, mixed>> $violations */
function bindTrafficFineSource(
    array $violations = [],
    ?Throwable $failure = null,
    string $sourceName = 'fake_source',
): object {
    $source = new class($violations, $failure, $sourceName) implements TrafficFineSourceInterface
    {
        public int $calls = 0;

        public function __construct(
            private readonly array $violations,
            private readonly ?Throwable $failure,
            private readonly string $sourceName,
        ) {}

        public function name(): string
        {
            return $this->sourceName;
        }

        public function lookup(string $normalizedPlate, VehicleType $vehicleType): TrafficFineLookupResultDataDto
        {
            $this->calls++;

            if ($this->failure instanceof Throwable) {
                throw $this->failure;
            }

            return new TrafficFineLookupResultDataDto(
                plate: $normalizedPlate,
                displayPlate: app(LicensePlateNormalizer::class)->display($normalizedPlate),
                vehicleType: $vehicleType->value,
                status: $this->violations === [] ? LookupStatus::NoViolation->value : LookupStatus::Success->value,
                violationCount: count($this->violations),
                violations: $this->violations,
                checkedAt: now()->toImmutable(),
            );
        }
    };

    app()->instance(TrafficFineSourceInterface::class, $source);

    return $source;
}

/**
 * @param  list<array<string, mixed>>  $violations
 * @return array{status: string, plate: string, type: string, data: list<array<string, mixed>>, total: int, timestamp: string}
 */
function xephatnguoiSuccessPayload(string $plate = '30A12345', array $violations = []): array
{
    return [
        'status' => 'success',
        'plate' => $plate,
        'type' => '1',
        'data' => $violations,
        'total' => count($violations),
        'timestamp' => '2026-08-16 16:07:54',
    ];
}

/** @return array{X-API-KEY: string, X-API-SECRET: string} */
function trafficFineV1Headers(User $user, array $permissions): array
{
    $user->wallet()->update(['balance' => 200]);
    $secret = 'sk_'.Str::random(40);
    $apiKeyValue = 'ak_'.Str::lower(Str::random(28));

    ApiKey::query()->create([
        'user_id' => $user->id,
        'key_type' => ApiKey::TYPE_WALLET,
        'name' => 'Traffic fine v1 test',
        'api_key' => $apiKeyValue,
        'api_secret_hash' => Hash::make($secret),
        'api_secret_encrypted' => $secret,
        'permissions' => $permissions,
        'ip_whitelist' => ['*'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    return [
        'X-API-KEY' => $apiKeyValue,
        'X-API-SECRET' => $secret,
    ];
}

it('resolves the configured source driver and supports the legacy provider alias', function (): void {
    config(['traffic-fines.default_source' => 'third_party']);

    $registry = app(TrafficFineSourceRegistry::class);

    expect($registry->activeName())->toBe('xephatnguoi')
        ->and($registry->resolve())->toBeInstanceOf(XephatnguoiSource::class);
});

it('rejects a configured driver that does not implement the source contract', function (): void {
    config([
        'traffic-fines.default_source' => 'invalid_source',
        'traffic-fines.sources.invalid_source.driver' => stdClass::class,
    ]);

    expect(fn () => app(TrafficFineSourceRegistry::class)->resolve())
        ->toThrow(TrafficFineConfigurationException::class, 'Nguồn tra cứu không được hỗ trợ.');
});

it('reports the active source configuration to admins without exposing credentials', function (): void {
    config([
        'traffic-fines.default_source' => 'xephatnguoi',
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

    $response = $this->getJson('/api/admin-api/traffic-fines/provider')
        ->assertOk()
        ->assertJsonPath('data.name', 'xephatnguoi')
        ->assertJsonPath('data.status', 'configured')
        ->assertJsonPath('data.url_configured', true)
        ->assertJsonPath('data.credential_configured', true);

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('private-test-token')
        ->not->toContain('api.xephatnguoi.com');
});

it('stores the normalized result returned by the active source', function (): void {
    $source = bindTrafficFineSource([[
        'plate_color' => 'Nền trắng',
        'time' => '2026-08-15 08:00:00',
        'location' => 'Hà Nội',
        'behavior' => 'Vượt quá tốc độ',
        'status' => 'Chưa xử lý',
        'agency' => 'Phòng CSGT',
        'resolution_agency' => 'Đội CSGT số 1',
        'resolution_address' => 'Số 1 Trần Phú, Hà Nội',
        'resolution_phone' => '0123456789',
    ]]);

    $this->postJson('/api/lookup', [
        'plate' => '30A-123.45',
        'vehicle_type' => 'car',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('cached', false)
        ->assertJsonMissingPath('source')
        ->assertJsonPath('data.plate', '30A12345')
        ->assertJsonPath('data.display_plate', '30A-123.45')
        ->assertJsonPath('data.violation_count', 1);

    expect($source->calls)->toBe(1);
    $this->assertDatabaseHas('traffic_fine_results', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'violation_count' => 1,
        'provider' => 'fake_source',
    ]);
    $this->assertDatabaseHas('traffic_fine_lookup_logs', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'source' => 'provider',
        'cache_hit' => false,
        'status' => 'success',
    ]);
    $this->assertDatabaseCount('lookup_histories', 0);
});

it('expires each stored lookup result exactly 24 hours after it is checked', function (): void {
    bindTrafficFineSource();

    $this->postJson('/api/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ])->assertOk();

    $result = TrafficFineResult::query()
        ->where('plate', '30A12345')
        ->where('vehicle_type', 'car')
        ->firstOrFail();

    expect($result->expires_at->getTimestamp() - $result->checked_at->getTimestamp())
        ->toBe(86400);
});

it('stores lookup history only for an authenticated user', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    bindTrafficFineSource();

    $this->postJson('/api/client/traffic-fines/lookup', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
    ])->assertOk();

    $this->assertDatabaseHas('lookup_histories', [
        'user_id' => $user->id,
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'violation_count' => 0,
    ]);
});

it('protects the v1 lookup with api credentials and the traffic fine permission', function (): void {
    bindTrafficFineSource();
    $payload = ['plate' => '30A12345', 'vehicle_type' => 'car'];

    $query = http_build_query($payload);

    $this->getJson("/api/v1/lookup?{$query}")
        ->assertUnauthorized()
        ->assertHeader('Cache-Control', 'no-store, private');

    $userWithoutPermission = User::factory()->create();
    $this->withHeaders(trafficFineV1Headers($userWithoutPermission, []))
        ->getJson("/api/v1/lookup?{$query}")
        ->assertForbidden();

    $user = User::factory()->create();
    $this->withHeaders(trafficFineV1Headers($user, ['traffic-fines.lookup']))
        ->getJson("/api/v1/lookup?{$query}")
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.plate', '30A12345');

    $this->assertDatabaseHas('lookup_histories', [
        'user_id' => $user->id,
        'plate' => '30A12345',
    ]);

    $this->postJson('/api/v1/lookup', $payload)->assertMethodNotAllowed();
});

it('uses the redis-compatible cache for repeated lookups', function (): void {
    $source = bindTrafficFineSource();
    $payload = ['plate' => '30A12345', 'vehicle_type' => 'car'];

    $this->postJson('/api/lookup', $payload)->assertOk()->assertJsonPath('cached', false);
    $this->postJson('/api/lookup', $payload)
        ->assertOk()
        ->assertJsonPath('cached', true)
        ->assertJsonMissingPath('source');

    expect($source->calls)->toBe(1);
});

it('isolates cached and stored results by active source', function (): void {
    $firstSource = bindTrafficFineSource(sourceName: 'source_a');
    $payload = ['plate' => '30A12345', 'vehicle_type' => 'car'];

    $this->postJson('/api/lookup', $payload)
        ->assertOk()
        ->assertJsonPath('cached', false);

    $secondSource = bindTrafficFineSource(sourceName: 'source_b');

    $this->postJson('/api/lookup', $payload)
        ->assertOk()
        ->assertJsonPath('cached', false);

    expect($firstSource->calls)->toBe(1)
        ->and($secondSource->calls)->toBe(1)
        ->and(Cache::store('array')->has('traffic_fine:source_a:car:30A12345'))->toBeTrue()
        ->and(Cache::store('array')->has('traffic_fine:source_b:car:30A12345'))->toBeTrue();

    $this->assertDatabaseHas('traffic_fine_results', [
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'provider' => 'source_b',
    ]);
});

it('restores redis from a fresh database result without calling the provider', function (): void {
    $source = bindTrafficFineSource(failure: new RuntimeException('Source must not be called.'));
    TrafficFineResult::factory()->create([
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'provider' => 'fake_source',
        'expires_at' => now()->addHour(),
    ]);

    $this->postJson('/api/lookup', ['plate' => '30A12345', 'vehicle_type' => 'car'])
        ->assertOk()
        ->assertJsonPath('cached', true)
        ->assertJsonMissingPath('source');

    expect($source->calls)->toBe(0)
        ->and(Cache::store('array')->has('traffic_fine:fake_source:car:30A12345'))->toBeTrue();
});

it('adds null defaults when restoring the previous normalized violation shape', function (): void {
    $source = bindTrafficFineSource(failure: new RuntimeException('Source must not be called.'));
    $checkedAt = now();

    TrafficFineResult::factory()->create([
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'provider' => 'fake_source',
        'status' => 'success',
        'violation_count' => 1,
        'response_json' => [
            'plate' => '30A12345',
            'display_plate' => '30A-123.45',
            'vehicle_type' => 'car',
            'status' => 'success',
            'violation_count' => 1,
            'violations' => [[
                'time' => '2026-08-15 08:00:00',
                'location' => 'Hà Nội',
                'behavior' => 'Vượt quá tốc độ',
                'status' => 'Chưa xử lý',
                'agency' => 'Phòng CSGT',
            ]],
            'checked_at' => $checkedAt->toISOString(),
        ],
        'checked_at' => $checkedAt,
        'expires_at' => $checkedAt->copy()->addHour(),
    ]);

    $this->postJson('/api/lookup', ['plate' => '30A12345', 'vehicle_type' => 'car'])
        ->assertOk()
        ->assertJsonPath('data.violations.0.plate_color', null)
        ->assertJsonPath('data.violations.0.resolution_agency', null)
        ->assertJsonPath('data.violations.0.resolution_address', null)
        ->assertJsonPath('data.violations.0.resolution_phone', null);

    expect($source->calls)->toBe(0);
});

it('does not extend a database result beyond its original expiry when restoring cache', function (): void {
    $source = bindTrafficFineSource();
    TrafficFineResult::factory()->create([
        'plate' => '30A12345',
        'vehicle_type' => 'car',
        'provider' => 'fake_source',
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson('/api/lookup', ['plate' => '30A12345', 'vehicle_type' => 'car'])
        ->assertOk()
        ->assertJsonPath('cached', true);

    $this->travel(6)->minutes();

    $this->postJson('/api/lookup', ['plate' => '30A12345', 'vehicle_type' => 'car'])
        ->assertOk()
        ->assertJsonPath('cached', false)
        ->assertJsonMissingPath('source');

    expect($source->calls)->toBe(1);
});

it('negative-caches provider failures', function (): void {
    $source = bindTrafficFineSource(failure: new TrafficFineProviderException('Upstream failure.'));
    $payload = ['plate' => '30A12345', 'vehicle_type' => 'car'];

    $this->postJson('/api/lookup', $payload)
        ->assertStatus(503)
        ->assertJsonPath('status', 'provider_error')
        ->assertJsonMissing(['message' => 'Upstream failure.']);
    $this->postJson('/api/lookup', $payload)
        ->assertStatus(503)
        ->assertJsonPath('status', 'provider_error');

    expect($source->calls)->toBe(1)
        ->and(Cache::store('array')->has('traffic_fine_error:fake_source:car:30A12345'))->toBeTrue();
});

it('returns a validation state without calling the provider', function (): void {
    $source = bindTrafficFineSource();

    $this->postJson('/api/lookup', ['plate' => 'invalid', 'vehicle_type' => 'car'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('status', 'invalid_plate');

    expect($source->calls)->toBe(0);
});

it('rejects vehicle types disabled in provider configuration', function (): void {
    config(['traffic-fines.vehicle_types.motorbike.enabled' => false]);
    $source = bindTrafficFineSource();

    $this->postJson('/api/lookup', ['plate' => '30A12345', 'vehicle_type' => 'motorbike'])
        ->assertUnprocessable()
        ->assertJsonPath('status', 'invalid_vehicle_type');

    expect($source->calls)->toBe(0);
});

it('rate limits the public lookup endpoint per ip', function (): void {
    config(['traffic-fines.rate_limit.per_minute' => 2]);
    bindTrafficFineSource();
    $payload = ['plate' => '30A12345', 'vehicle_type' => 'car'];

    $this->postJson('/api/lookup', $payload)->assertOk();
    $this->postJson('/api/lookup', $payload)->assertOk();
    $this->postJson('/api/lookup', $payload)
        ->assertTooManyRequests()
        ->assertJsonPath('status', 'rate_limited');
});

it('calls the private lookup endpoint with a backend-only bearer credential', function (string $vehicleType, int $providerType): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response(xephatnguoiSuccessPayload('30K12345')),
    ]);

    app(XephatnguoiSource::class)->lookup('30K12345', VehicleType::from($vehicleType));

    Http::assertSent(function (ClientRequest $request) use ($providerType): bool {
        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_SCHEME) === 'https'
            && parse_url($request->url(), PHP_URL_HOST) === 'api.xephatnguoi.com'
            && parse_url($request->url(), PHP_URL_PATH) === '/v1/search'
            && $request['plate'] === '30K12345'
            && $request['type'] === $providerType
            && $request->hasHeader('Authorization', 'Bearer private-test-token');
    });
})->with([
    'car' => ['car', 1],
    'motorbike' => ['motorbike', 2],
    'electric motorbike' => ['electric_motorbike', 3],
]);

it('refuses to send the bearer credential to an unapproved endpoint', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://untrusted.example/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();

    expect(fn () => app(XephatnguoiSource::class)->lookup('30K12345', VehicleType::Car))
        ->toThrow(TrafficFineConfigurationException::class, 'Nguồn tra cứu chưa được cấu hình hợp lệ.');

    Http::assertNothingSent();
});

it('does not follow redirects while carrying the bearer credential', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response('', 302, [
            'Location' => 'https://untrusted.example/capture',
        ]),
    ]);

    expect(fn () => app(XephatnguoiSource::class)->lookup('30K12345', VehicleType::Car))
        ->toThrow(TrafficFineProviderException::class, 'Không thể tra cứu dữ liệu vào lúc này.');

    Http::assertSentCount(1);
});

it('retries temporary server failures without exposing the credential', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
        'traffic-fines.sources.xephatnguoi.retry_sleep_ms' => 0,
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::sequence()
            ->push(['message' => 'temporary failure'], 500)
            ->push(xephatnguoiSuccessPayload('30K12345')),
    ]);

    $result = app(XephatnguoiSource::class)->lookup('30K12345', VehicleType::Car);

    expect($result->status)->toBe('no_violation');
    Http::assertSentCount(2);
});

it('normalizes upstream data without exposing provider details or credentials', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response([
            'status' => 'success',
            'plate' => '30K12345',
            'type' => '1',
            'provider' => 'sensitive-provider-name',
            'debug_url' => 'https://api.xephatnguoi.com/v1/search',
            'data' => [[
                'bien_so' => '30K12345',
                'mau_bien' => 'Nền trắng',
                'thoi_gian' => '2026-08-15 08:00:00',
                'dia_diem' => 'Hà Nội',
                'hanh_vi' => 'Vượt quá tốc độ',
                'trang_thai' => 'Chưa xử lý',
                'don_vi_phat_hien' => 'Phòng CSGT',
                'nơi_giai_quyet' => 'Đội CSGT số 1',
                'dia_chi_giai_quyet' => 'Số 1 Trần Phú, Hà Nội',
                'so_dien_thoai' => '0123456789',
                'debug_token' => 'private-test-token',
            ]],
            'total' => 1,
            'timestamp' => '2026-08-16 16:07:54',
        ]),
    ]);

    $response = $this->postJson('/api/lookup', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])->assertOk()
        ->assertJsonPath('data.violation_count', 1)
        ->assertJsonPath('data.processed_count', 0)
        ->assertJsonPath('data.unprocessed_count', 1)
        ->assertJsonPath('data.unknown_status_count', 0)
        ->assertJsonPath('data.violations.0.location', 'Hà Nội')
        ->assertJsonMissingPath('source');

    $publicPayload = json_encode($response->json(), JSON_THROW_ON_ERROR);
    $storedPayload = json_encode(
        TrafficFineResult::query()->where('plate', '30K12345')->firstOrFail()->response_json,
        JSON_THROW_ON_ERROR,
    );
    $cachedPayload = json_encode(
        Cache::store('array')->get('traffic_fine:xephatnguoi:car:30K12345'),
        JSON_THROW_ON_ERROR,
    );

    expect($publicPayload)
        ->not->toContain('private-test-token')
        ->not->toContain('api.xephatnguoi.com')
        ->not->toContain('sensitive-provider-name')
        ->and($storedPayload)
        ->not->toContain('private-test-token')
        ->not->toContain('api.xephatnguoi.com')
        ->not->toContain('sensitive-provider-name')
        ->and($cachedPayload)
        ->not->toContain('private-test-token')
        ->not->toContain('api.xephatnguoi.com')
        ->not->toContain('sensitive-provider-name');
});

it('normalizes the actual provider response contract', function (): void {
    $this->freezeTime();
    $expectedCheckedAt = now()->toImmutable()->toISOString();

    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response([
            'status' => 'success',
            'plate' => '29A33334',
            'type' => '1',
            'data' => [[
                'bien_so' => '29A33334',
                'mau_bien' => 'Nền mầu trắng, chữ và số màu đen',
                'trang_thai' => 'Chưa xử phạt',
                'thoi_gian' => '2026-02-13T05:34:00.000Z',
                'dia_diem' => 'Km 141+900, Quốc Lộ 1, Tỉnh Bắc Ninh',
                'hanh_vi' => 'Điều khiển xe chạy quá tốc độ quy định từ 10 km/h đến 20 km/h',
                'don_vi_phat_hien' => 'Phòng Cảnh sát giao thông - Công an Tỉnh Bắc Ninh',
                'nơi_giai_quyet' => 'Đội CSGT ĐB số 2 - Phòng Cảnh sát giao thông - Công an Thành phố Hà Nội',
                'dia_chi_giai_quyet' => 'Số 8A Xuân La, Phường Tây Hồ, TP Hà Nội',
                'so_dien_thoai' => '',
            ]],
            'total' => 1,
            'timestamp' => '2026-08-16 16:07:54',
        ]),
    ]);

    $this->postJson('/api/lookup', [
        'plate' => '29A33334',
        'vehicle_type' => 'car',
    ])->assertOk()
        ->assertJsonPath('data.violation_count', 1)
        ->assertJsonPath('data.violations.0.time', '2026-02-13T05:34:00.000Z')
        ->assertJsonPath('data.violations.0.location', 'Km 141+900, Quốc Lộ 1, Tỉnh Bắc Ninh')
        ->assertJsonPath('data.violations.0.behavior', 'Điều khiển xe chạy quá tốc độ quy định từ 10 km/h đến 20 km/h')
        ->assertJsonPath('data.violations.0.status', 'Chưa xử phạt')
        ->assertJsonPath('data.violations.0.resolution_status', 'unprocessed')
        ->assertJsonPath('data.violations.0.agency', 'Phòng Cảnh sát giao thông - Công an Tỉnh Bắc Ninh')
        ->assertJsonPath('data.violations.0.plate_color', 'Nền mầu trắng, chữ và số màu đen')
        ->assertJsonPath('data.violations.0.resolution_agency', 'Đội CSGT ĐB số 2 - Phòng Cảnh sát giao thông - Công an Thành phố Hà Nội')
        ->assertJsonPath('data.violations.0.resolution_address', 'Số 8A Xuân La, Phường Tây Hồ, TP Hà Nội')
        ->assertJsonPath('data.violations.0.resolution_phone', null)
        ->assertJsonPath('data.checked_at', $expectedCheckedAt)
        ->assertJsonMissingPath('data.violations.0.bien_so')
        ->assertJsonMissingPath('data.violations.0.mau_bien')
        ->assertJsonMissingPath('data.violations.0.nơi_giai_quyet')
        ->assertJsonMissingPath('data.total');
});

it('rejects an invalid provider response contract', function (array $providerPayload): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response($providerPayload),
    ]);

    $this->postJson('/api/lookup', [
        'plate' => '29A33334',
        'vehicle_type' => 'car',
    ])->assertServiceUnavailable()
        ->assertJsonPath('status', 'provider_error');

    $this->assertDatabaseMissing('traffic_fine_results', [
        'plate' => '29A33334',
        'vehicle_type' => 'car',
    ]);
})->with([
    'missing success status' => [[
        'plate' => '29A33334',
        'data' => [],
        'total' => 0,
    ]],
    'data is not a list' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => ['unexpected' => 'shape'],
        'total' => 0,
    ]],
    'total is missing' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => [],
    ]],
    'total is negative' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => [],
        'total' => -1,
    ]],
    'total is a numeric string' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => [],
        'total' => '0',
    ]],
    'total is less than returned items' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => [['bien_so' => '29A33334']],
        'total' => 0,
    ]],
    'violation item is not an object' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => ['invalid item'],
        'total' => 1,
    ]],
    'root plate does not match' => [[
        'status' => 'success',
        'plate' => '30A12345',
        'data' => [],
        'total' => 0,
    ]],
    'violation plate does not match' => [[
        'status' => 'success',
        'plate' => '29A33334',
        'data' => [['bien_so' => '30A12345']],
        'total' => 1,
    ]],
]);

it('rejects an unsuccessful string status from the provider', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response([
            'status' => 'error',
            'data' => [],
        ]),
    ]);

    expect(fn () => app(XephatnguoiSource::class)->lookup('29A33334', VehicleType::Car))
        ->toThrow(TrafficFineProviderException::class, 'Hệ thống tra cứu không thể hoàn tất yêu cầu.');
});

it('returns a sanitized error when the private endpoint rejects the request', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response([
            'message' => 'Bearer private-test-token is invalid at api.xephatnguoi.com',
        ], 401),
    ]);

    $response = $this->postJson('/api/lookup', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])->assertServiceUnavailable()
        ->assertJsonPath('status', 'provider_error');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain('private-test-token')
        ->not->toContain('api.xephatnguoi.com');

    Http::assertSentCount(1);
});

it('rejects an unknown upstream schema instead of treating it as no violation', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.xephatnguoi.com/v1/search*' => Http::response([
            'status' => 'success',
            'plate' => '30K12345',
            'data' => ['unexpected' => 'shape'],
            'total' => 0,
        ]),
    ]);

    $this->postJson('/api/lookup', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ])->assertServiceUnavailable()
        ->assertJsonPath('status', 'provider_error');

    $this->assertDatabaseMissing('traffic_fine_results', [
        'plate' => '30K12345',
        'vehicle_type' => 'car',
    ]);
});

it('maps provider connection timeouts to a safe provider exception', function (): void {
    config([
        'traffic-fines.sources.xephatnguoi.url' => 'https://api.xephatnguoi.com/v1/search',
        'traffic-fines.sources.xephatnguoi.token' => 'private-test-token',
    ]);
    Http::fake(['https://api.xephatnguoi.com/v1/search*' => Http::failedConnection()]);

    expect(fn () => app(XephatnguoiSource::class)->lookup('30A12345', VehicleType::Car))
        ->toThrow(TrafficFineProviderException::class, 'Kết nối tra cứu đã hết thời gian chờ.');
});
