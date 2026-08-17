<?php

use App\Models\User;
use App\Utils\SendMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

test('admin sees the five canonical Discord rooms without webhook secrets', function (): void {
    Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
    config([
        'services.discord.channels.ops' => 'https://discord.com/api/webhooks/1/ops-secret',
        'services.discord.channels.activity' => '',
        'services.discord.channels.sales' => 'https://discord.com/api/webhooks/2/sales-secret',
        'services.discord.channels.support' => '',
        'services.discord.channels.staging' => '',
    ]);

    $response = $this->getJson('/api/admin-api/settings/monitoring')->assertOk();

    expect(collect($response->json('data.settings.rooms'))->pluck('name')->all())->toBe([
        '#xpn-ops',
        '#xpn-activity',
        '#xpn-sales',
        '#xpn-support',
        '#xpn-staging',
    ]);

    $response
        ->assertJsonPath('data.settings.rooms.0.configured', true)
        ->assertJsonPath('data.settings.rooms.1.configured', false)
        ->assertJsonPath('data.settings.rooms.2.configured', true)
        ->assertJsonMissing(['url' => 'https://discord.com/api/webhooks/1/ops-secret']);
});

test('domain reports route to their canonical production rooms', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Http::preventStrayRequests();
    Http::fake([
        'https://discord.test/ops' => Http::response([], 204),
        'https://discord.test/activity' => Http::response([], 204),
        'https://discord.test/sales' => Http::response([], 204),
        'https://discord.test/support' => Http::response([], 204),
    ]);
    config([
        'services.discord.channels.ops' => 'https://discord.test/ops',
        'services.discord.channels.activity' => 'https://discord.test/activity',
        'services.discord.channels.sales' => 'https://discord.test/sales',
        'services.discord.channels.support' => 'https://discord.test/support',
        'services.discord.channels.staging' => '',
    ]);

    SendMessage::sendQueueReport('Queue failed');
    SendMessage::sendProviderReport('Provider failed');
    SendMessage::sendActivityReport('New user');
    SendMessage::sendSalesReport('Recharge success');
    SendMessage::sendFeedbackReport('New feedback');

    Http::assertSentCount(5);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.test/ops' && $request['allowed_mentions'] === ['parse' => []]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.test/activity');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.test/sales');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.test/support');
});

test('non production reports are isolated in the staging room', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    Http::preventStrayRequests();
    Http::fake(['https://discord.test/staging' => Http::response([], 204)]);
    config([
        'services.discord.channels.ops' => 'https://discord.test/ops',
        'services.discord.channels.staging' => 'https://discord.test/staging',
    ]);

    SendMessage::sendProviderReport('Provider failed locally');

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.test/staging');
});

test('runtime report call sites use the expected canonical rooms', function (): void {
    $walletService = file_get_contents(base_path('app/Features/Client/Wallet/Services/WalletDepositService.php'));
    $lookupService = file_get_contents(base_path('app/Features/TrafficFine/Services/TrafficFineLookupService.php'));
    $provider = file_get_contents(base_path('app/Providers/AppServiceProvider.php'));

    expect($walletService)
        ->toContain('SendMessage::sendSalesReport')
        ->not->toContain("SendMessage::sendActivityReport('Người dùng nạp tiền thành công'")
        ->and($lookupService)->toContain('SendMessage::sendProviderReport')
        ->and($provider)
        ->not->toContain('sendQueueProcessedNotification')
        ->toContain('sendQueueFailedNotification');
});

test('admin UI explains the canonical Discord room mapping', function (): void {
    $source = file_get_contents(base_path('resources/js/pages/admin/settings/index.vue'));

    expect($source)
        ->toContain('#xpn-ops')
        ->toContain('#xpn-activity')
        ->toContain('#xpn-sales')
        ->toContain('#xpn-support')
        ->toContain('#xpn-staging')
        ->not->toContain('discord_webhooks');
});
