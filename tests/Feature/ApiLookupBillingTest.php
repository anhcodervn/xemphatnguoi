<?php

use App\Features\TrafficFine\DTOs\TrafficFineLookupResultDataDto;
use App\Features\TrafficFine\Enums\LookupStatus;
use App\Features\TrafficFine\Enums\VehicleType;
use App\Features\TrafficFine\Exceptions\TrafficFineProviderException;
use App\Features\TrafficFine\Services\LicensePlateNormalizer;
use App\Features\TrafficFine\Services\Source\TrafficFineSourceInterface;
use App\Models\ApiKey;
use App\Models\ApiLog;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    config([
        'traffic-fines.cache.store' => 'array',
        'traffic-fines.cache.ttl' => 86400,
        'traffic-fines.cache.error_ttl' => 60,
        'traffic-fines.billing.api_request_price' => 20,
    ]);

    Cache::store('array')->flush();
});

function bindBillingTrafficFineSource(?Throwable $failure = null): object
{
    $source = new class($failure) implements TrafficFineSourceInterface
    {
        public int $calls = 0;

        public function __construct(private readonly ?Throwable $failure) {}

        public function name(): string
        {
            return 'billing_test_source';
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
                status: LookupStatus::NoViolation->value,
                violationCount: 0,
                violations: [],
                checkedAt: now()->toImmutable(),
            );
        }
    };

    app()->instance(TrafficFineSourceInterface::class, $source);

    return $source;
}

/**
 * @return array{user: User, api_key: ApiKey, headers: array{X-API-KEY: string, X-API-SECRET: string}}
 */
function billingApiAccount(float $balance = 100): array
{
    $user = User::factory()->create();
    $user->wallet()->update([
        'balance' => $balance,
        'total_spent' => 0,
    ]);

    $secret = 'sk_'.Str::random(40);
    $apiKey = ApiKey::query()->create([
        'user_id' => $user->id,
        'key_type' => ApiKey::TYPE_WALLET,
        'name' => 'Billing test key',
        'api_key' => 'ak_'.Str::lower(Str::random(28)),
        'api_secret_hash' => Hash::make($secret),
        'api_secret_encrypted' => $secret,
        'permissions' => ['traffic-fines.lookup'],
        'ip_whitelist' => ['*'],
        'status' => ApiKey::STATUS_ACTIVE,
    ]);

    return [
        'user' => $user,
        'api_key' => $apiKey,
        'headers' => [
            'X-API-KEY' => $apiKey->api_key,
            'X-API-SECRET' => $secret,
        ],
    ];
}

function billingLookupUrl(string $plate = '30A12345'): string
{
    return '/api/v1/lookup?'.http_build_query([
        'plate' => $plate,
        'vehicle_type' => 'car',
    ]);
}

it('charges the configured price atomically for a successful GET API lookup', function (): void {
    $source = bindBillingTrafficFineSource();
    $account = billingApiAccount();

    $this->withHeaders($account['headers'])
        ->getJson(billingLookupUrl())
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('data.plate', '30A12345');

    expect($source->calls)->toBe(1)
        ->and((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(80.0)
        ->and((float) $account['user']->wallet()->firstOrFail()->total_spent)->toBe(20.0);

    $transaction = WalletTransaction::query()
        ->where('reference_type', 'traffic_fine_api_request')
        ->sole();
    $log = ApiLog::query()->sole();

    expect((float) $transaction->amount)->toBe(20.0)
        ->and($transaction->reference_id)->toBe($account['api_key']->id)
        ->and($log->wallet_transaction_id)->toBe($transaction->id)
        ->and((float) $log->unit_price)->toBe(20.0)
        ->and((float) $log->charged_amount)->toBe(20.0)
        ->and($log->billing_status)->toBe('charged');
});

it('charges every successful request while the provider result remains cached', function (): void {
    $source = bindBillingTrafficFineSource();
    $account = billingApiAccount();

    $this->withHeaders($account['headers'])->getJson(billingLookupUrl())->assertOk()->assertJsonPath('cached', false);
    $this->withHeaders($account['headers'])->getJson(billingLookupUrl())->assertOk()->assertJsonPath('cached', true);

    expect($source->calls)->toBe(1)
        ->and((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(60.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_request')->count())->toBe(2)
        ->and(ApiLog::query()->where('billing_status', 'charged')->count())->toBe(2);
});

it('rejects insufficient balance before provider lookup without charging', function (): void {
    $source = bindBillingTrafficFineSource();
    $account = billingApiAccount(balance: 19);

    $this->withHeaders($account['headers'])
        ->getJson(billingLookupUrl())
        ->assertStatus(402)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('code', 'insufficient_balance');

    expect($source->calls)->toBe(0)
        ->and((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(19.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_request')->exists())->toBeFalse();

    $this->assertDatabaseHas('api_logs', [
        'billing_status' => 'insufficient_balance',
        'unit_price' => 20,
        'charged_amount' => 0,
        'status_code' => 402,
    ]);
});

it('does not charge validation or provider failures', function (): void {
    $account = billingApiAccount();
    $source = bindBillingTrafficFineSource();

    $this->withHeaders($account['headers'])
        ->getJson('/api/v1/lookup?plate=invalid&vehicle_type=car')
        ->assertUnprocessable();

    expect($source->calls)->toBe(0);

    bindBillingTrafficFineSource(new TrafficFineProviderException('private upstream failure'));
    $this->withHeaders($account['headers'])
        ->getJson(billingLookupUrl('30A99999'))
        ->assertServiceUnavailable()
        ->assertJsonMissing(['message' => 'private upstream failure']);

    expect((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(100.0)
        ->and(WalletTransaction::query()->where('reference_type', 'traffic_fine_api_request')->exists())->toBeFalse()
        ->and(ApiLog::query()->where('billing_status', 'charged')->exists())->toBeFalse();

    $storedLogs = json_encode(ApiLog::query()->get()->toArray(), JSON_THROW_ON_ERROR);
    expect($storedLogs)
        ->not->toContain($account['headers']['X-API-SECRET'])
        ->not->toContain('private upstream failure');
});

it('lets an admin change the price for new requests while preserving old log prices', function (): void {
    bindBillingTrafficFineSource();
    $account = billingApiAccount(balance: 100);

    $this->withHeaders($account['headers'])->getJson(billingLookupUrl())->assertOk();

    $admin = User::factory()->create(['role' => 'admin']);
    Sanctum::actingAs($admin);
    $this->putJson('/api/admin-api/traffic-fines/billing', ['api_request_price' => 35])
        ->assertOk()
        ->assertJsonPath('data.api_request_price', 35);

    $this->withHeaders($account['headers'])->getJson(billingLookupUrl('30A99999'))->assertOk();

    $prices = ApiLog::query()->orderBy('id')->pluck('unit_price')->map(fn (string $price): float => (float) $price)->all();

    expect($prices)->toBe([20.0, 35.0])
        ->and((float) $account['user']->wallet()->firstOrFail()->balance)->toBe(45.0);
});

it('shows a user only allowlisted fields from their own API request logs', function (): void {
    bindBillingTrafficFineSource();
    $first = billingApiAccount();
    $second = billingApiAccount();

    $this->withHeaders($first['headers'])->getJson(billingLookupUrl('30A12345'))->assertOk();
    $this->withHeaders($second['headers'])->getJson(billingLookupUrl('30A99999'))->assertOk();

    Sanctum::actingAs($first['user']);
    $response = $this->getJson('/api/client/traffic-fines/api-usage')
        ->assertOk()
        ->assertJsonCount(1, 'data.logs.data')
        ->assertJsonPath('data.logs.data.0.plate', '30A12345')
        ->assertJsonMissingPath('data.logs.data.0.request_data')
        ->assertJsonMissingPath('data.logs.data.0.response_data')
        ->assertJsonMissingPath('data.logs.data.0.api_key_id');

    expect(json_encode($response->json(), JSON_THROW_ON_ERROR))
        ->not->toContain((string) $second['api_key']->api_key)
        ->not->toContain('30A99999');
});
