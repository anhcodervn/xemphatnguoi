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
    config()->set('services.proxy.check_url', 'https://proxy-check.test/ip');
});

test('proxy checker requires authentication', function (): void {
    $this->postJson('/api/client/proxy/check', [
        'proxies' => ['8.8.8.8:8080:user:secret'],
    ])->assertUnauthorized();
});

test('proxy checker routes use the client proxy controller', function (): void {
    $storeRoute = Route::getRoutes()->match(Request::create('/api/client/proxy/check', 'POST'));
    $statusRoute = Route::getRoutes()->match(Request::create('/api/client/proxy/check/01KZN32WQYHMWX1CWGMH74PQ23', 'GET'));

    expect($storeRoute->getActionName())->toBe(ProxyController::class.'@check')
        ->and($statusRoute->getActionName())->toBe(ProxyController::class.'@checkStatus');
});

test('proxy checker creates an encrypted batch and dispatches one job per proxy', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/client/proxy/check', [
        'proxies' => [
            '8.8.8.8:8080:daily-user:first-secret',
            '1.1.1.1:3128:daily-user:second-secret',
        ],
    ]);

    $response
        ->assertAccepted()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.batch.status', ProxyCheckBatch::STATUS_PENDING)
        ->assertJsonPath('data.batch.total', 2)
        ->assertJsonPath('data.batch.processed', 0)
        ->assertJsonPath('data.batch.results.0.endpoint', '8.8.8.8:8080')
        ->assertJsonPath('data.batch.results.0.status', ProxyCheckItem::STATUS_PENDING)
        ->assertDontSee('daily-user', false)
        ->assertDontSee('first-secret', false)
        ->assertDontSee('second-secret', false);

    $batch = ProxyCheckBatch::query()->with('items')->sole();
    $storedProxy = DB::table('proxy_check_items')->where('id', $batch->items->first()->id)->value('proxy');

    expect($batch->user_id)->toBe($user->id)
        ->and($batch->items)->toHaveCount(2)
        ->and($storedProxy)->not->toBe('8.8.8.8:8080:daily-user:first-secret');

    Queue::assertPushed(ProcessProxyCheckJob::class, 2);
});

test('queued proxy check broadcasts progress and completes a live item', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/client/proxy/check', [
        'proxies' => ['8.8.8.8:8080:user:secret'],
    ])->assertAccepted();

    $item = ProxyCheckItem::query()->sole();
    Event::fake([ProxyCheckProgressed::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy-check.test/ip' => Http::response(['ip' => '203.0.113.15']),
    ]);

    (new ProcessProxyCheckJob($item->id))->handle(app(ProxyCheckerService::class));

    $item->refresh();
    $batch = $item->batch()->firstOrFail();

    expect($item->status)->toBe(ProxyCheckItem::STATUS_LIVE)
        ->and($item->proxy)->toBeNull()
        ->and($item->exit_ip)->toBe('203.0.113.15')
        ->and($batch->status)->toBe(ProxyCheckBatch::STATUS_COMPLETED)
        ->and($batch->processed)->toBe(1)
        ->and($batch->live)->toBe(1)
        ->and($batch->die)->toBe(0);

    Event::assertDispatchedTimes(ProxyCheckProgressed::class, 2);
    Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://proxy-check.test/ip');
});

test('queued proxy check marks a connection failure as die and removes credentials', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/client/proxy/check', [
        'proxies' => ['8.8.4.4:8080:user:secret'],
    ])->assertAccepted();

    $item = ProxyCheckItem::query()->sole();
    Event::fake([ProxyCheckProgressed::class]);
    Http::preventStrayRequests();
    Http::fake([
        'https://proxy-check.test/ip' => Http::failedConnection(),
    ]);

    (new ProcessProxyCheckJob($item->id))->handle(app(ProxyCheckerService::class));

    expect($item->fresh()->status)->toBe(ProxyCheckItem::STATUS_DIE)
        ->and($item->fresh()->proxy)->toBeNull()
        ->and($item->fresh()->message)->toBe('Không thể kết nối qua proxy.')
        ->and($item->batch()->firstOrFail()->die)->toBe(1);
});

test('user can restore only their own proxy check batch', function (): void {
    Queue::fake();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $created = $this->actingAs($owner)->postJson('/api/client/proxy/check', [
        'proxies' => ['8.8.8.8:8080:user:secret'],
    ])->assertAccepted();

    $batchId = $created->json('data.batch.id');

    $this->actingAs($owner)
        ->getJson("/api/client/proxy/check/{$batchId}")
        ->assertOk()
        ->assertJsonPath('data.batch.id', $batchId)
        ->assertDontSee('secret', false);

    $this->actingAs($otherUser)
        ->getJson("/api/client/proxy/check/{$batchId}")
        ->assertNotFound();
});

test('proxy checker rejects malformed and private proxy addresses before dispatching jobs', function (string $proxy): void {
    Queue::fake();

    $this->actingAs(User::factory()->create())->postJson('/api/client/proxy/check', [
        'proxies' => [$proxy],
    ])->assertUnprocessable();

    Queue::assertNothingPushed();
})->with([
    'missing credentials' => '8.8.8.8:8080',
    'private address' => '127.0.0.1:8080:user:secret',
    'invalid port' => '8.8.8.8:70000:user:secret',
]);

test('proxy checker limits each batch to twenty proxies', function (): void {
    Queue::fake();

    $this->actingAs(User::factory()->create())->postJson('/api/client/proxy/check', [
        'proxies' => array_fill(0, 21, '8.8.8.8:8080:user:secret'),
    ])->assertUnprocessable();

    Queue::assertNothingPushed();
});
