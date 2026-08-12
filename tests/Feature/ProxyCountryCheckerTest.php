<?php

use App\Events\ProxyCheckProgressed;
use App\Features\Client\Proxy\Controllers\ProxyController;
use App\Features\Client\Proxy\Services\ProxyCheckerService;
use App\Jobs\ProcessProxyCheckJob;
use App\Models\ProxyCheckBatch;
use App\Models\ProxyCheckItem;
use App\Models\User;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    config()->set('services.proxy.country_check_url', 'https://proxy-country.test/');
});

test('proxy country checker requires authentication', function (): void {
    $this->postJson('/api/client/proxy/country-check', [
        'proxies' => ['8.8.8.8:8080:user:secret'],
    ])->assertUnauthorized();
});

test('proxy country checker routes use the client proxy controller', function (): void {
    $storeRoute = Route::getRoutes()->match(Request::create('/api/client/proxy/country-check', 'POST'));
    $statusRoute = Route::getRoutes()->match(Request::create('/api/client/proxy/country-check/01KZN32WQYHMWX1CWGMH74PQ23', 'GET'));

    expect($storeRoute->getActionName())->toBe(ProxyController::class.'@checkCountry')
        ->and($statusRoute->getActionName())->toBe(ProxyController::class.'@checkCountryStatus');
});

test('proxy country checker creates an encrypted country batch and dispatches jobs', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/client/proxy/country-check', [
        'proxies' => [
            '8.8.8.8:8080:daily-user:first-secret',
            '1.1.1.1:3128:daily-user:second-secret',
        ],
    ]);

    $response
        ->assertAccepted()
        ->assertJsonPath('data.batch.check_type', ProxyCheckBatch::TYPE_COUNTRY)
        ->assertJsonPath('data.batch.total', 2)
        ->assertJsonPath('data.batch.results.0.country_code', null)
        ->assertDontSee('daily-user', false)
        ->assertDontSee('first-secret', false);

    $batch = ProxyCheckBatch::query()->with('items')->sole();
    $storedProxy = DB::table('proxy_check_items')->where('id', $batch->items->first()->id)->value('proxy');

    expect($batch->check_type)->toBe(ProxyCheckBatch::TYPE_COUNTRY)
        ->and($batch->items)->toHaveCount(2)
        ->and($storedProxy)->not->toBe('8.8.8.8:8080:daily-user:first-secret');

    Queue::assertPushed(ProcessProxyCheckJob::class, 2);
});

test('queued country check stores location data broadcasts progress and clears credentials', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/client/proxy/country-check', [
        'proxies' => ['8.8.8.8:8080:user:secret'],
    ])->assertAccepted();

    $item = ProxyCheckItem::query()->sole();
    Event::fake([ProxyCheckProgressed::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy-country.test/' => Http::response([
            'ip' => '203.0.113.15',
            'success' => true,
            'country' => 'United States',
            'country_code' => 'US',
            'region' => 'California',
            'city' => 'Los Angeles',
            'timezone' => ['id' => 'America/Los_Angeles'],
            'connection' => ['isp' => 'Example ISP'],
        ]),
    ]);

    (new ProcessProxyCheckJob($item->id))->handle(app(ProxyCheckerService::class));

    $item->refresh();
    $batch = $item->batch()->firstOrFail();

    expect($item->status)->toBe(ProxyCheckItem::STATUS_LIVE)
        ->and($item->proxy)->toBeNull()
        ->and($item->exit_ip)->toBe('203.0.113.15')
        ->and($item->country_code)->toBe('US')
        ->and($item->country_name)->toBe('United States')
        ->and($item->region_name)->toBe('California')
        ->and($item->city_name)->toBe('Los Angeles')
        ->and($item->timezone)->toBe('America/Los_Angeles')
        ->and($item->isp)->toBe('Example ISP')
        ->and($batch->status)->toBe(ProxyCheckBatch::STATUS_COMPLETED)
        ->and($batch->processed)->toBe(1)
        ->and($batch->live)->toBe(1);

    Event::assertDispatchedTimes(ProxyCheckProgressed::class, 2);
    Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://proxy-country.test/');
});

test('country check completes as failed when location response is invalid', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/client/proxy/country-check', [
        'proxies' => ['8.8.4.4:8080:user:secret'],
    ])->assertAccepted();

    $item = ProxyCheckItem::query()->sole();
    Event::fake([ProxyCheckProgressed::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy-country.test/' => Http::response([
            'success' => false,
            'message' => 'Lookup failed',
        ]),
    ]);

    (new ProcessProxyCheckJob($item->id))->handle(app(ProxyCheckerService::class));

    expect($item->fresh()->status)->toBe(ProxyCheckItem::STATUS_DIE)
        ->and($item->fresh()->proxy)->toBeNull()
        ->and($item->fresh()->country_code)->toBeNull()
        ->and($item->fresh()->message)->toBe('Không nhận được dữ liệu quốc gia hợp lệ.')
        ->and($item->batch()->firstOrFail()->die)->toBe(1);
});

test('country status endpoint only restores country batches belonging to the user', function (): void {
    Queue::fake();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $created = $this->actingAs($owner)->postJson('/api/client/proxy/country-check', [
        'proxies' => ['8.8.8.8:8080:user:secret'],
    ])->assertAccepted();
    $batchId = $created->json('data.batch.id');

    $this->actingAs($owner)
        ->getJson("/api/client/proxy/country-check/{$batchId}")
        ->assertOk()
        ->assertJsonPath('data.batch.check_type', ProxyCheckBatch::TYPE_COUNTRY)
        ->assertDontSee('secret', false);

    $this->actingAs($owner)
        ->getJson("/api/client/proxy/check/{$batchId}")
        ->assertNotFound();

    $this->actingAs($otherUser)
        ->getJson("/api/client/proxy/country-check/{$batchId}")
        ->assertNotFound();

    $liveBatch = ProxyCheckBatch::factory()->for($owner)->create();

    $this->actingAs($owner)
        ->getJson("/api/client/proxy/country-check/{$liveBatch->id}")
        ->assertNotFound();
});

test('country checker rejects malformed proxies before dispatching jobs', function (): void {
    Queue::fake();

    $this->actingAs(User::factory()->create())->postJson('/api/client/proxy/country-check', [
        'proxies' => ['127.0.0.1:8080:user:secret'],
    ])->assertUnprocessable();

    Queue::assertNothingPushed();
});
