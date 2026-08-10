<?php

use App\Utils\SendMessage;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    app()->detectEnvironment(fn (): string => 'local');
});

afterEach(function () {
    app()->detectEnvironment(fn (): string => 'testing');
});

it('silently skips a Discord channel without a configured webhook', function () {
    Http::preventStrayRequests();
    config()->set('services.discord.channels.queue');

    SendMessage::sendQueueReport('Queue finished');

    Http::assertNothingSent();
});

it('sends to Discord when the channel webhook is configured', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://discord.com/api/webhooks/test' => Http::response([], 204),
    ]);
    config()->set('services.discord.channels.queue', 'https://discord.com/api/webhooks/test');

    SendMessage::sendQueueReport('Queue finished', ['Job' => 'ExampleJob']);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://discord.com/api/webhooks/test'
        && str_contains((string) $request['content'], 'Queue finished')
        && str_contains((string) $request['content'], 'ExampleJob'));
});

it('still rejects an unsupported Discord channel type', function () {
    expect(fn () => SendMessage::sendDiscord('Test', 'unsupported'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported Discord channel type [unsupported].');
});

it('reports a configured Discord webhook failure without breaking the caller', function () {
    Exceptions::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://discord.com/api/webhooks/failing' => Http::response(['message' => 'Unavailable'], 500),
    ]);
    config()->set('services.discord.channels.queue', 'https://discord.com/api/webhooks/failing');

    SendMessage::sendQueueReport('Queue finished');

    Exceptions::assertReported(RequestException::class);
});

it('sends domain reports to their configured webhook channels', function (string $method, string $channel, string $prefix) {
    $url = "https://discord.com/api/webhooks/{$channel}";
    Http::preventStrayRequests();
    Http::fake([$url => Http::response([], 204)]);
    config()->set("services.discord.channels.{$channel}", $url);

    SendMessage::{$method}('Báo cáo thử nghiệm', ['Mã' => 123]);

    Http::assertSent(fn ($request): bool => $request->url() === $url
        && str_contains((string) $request['content'], "[{$prefix}]")
        && str_contains((string) $request['content'], 'Báo cáo thử nghiệm')
        && str_contains((string) $request['content'], '123'));
})->with([
    'activity' => ['sendActivityReport', 'activity', 'ACTIVITY'],
    'sales' => ['sendSalesReport', 'sales', 'SALES'],
    'provider' => ['sendProviderReport', 'provider', 'PROVIDER'],
    'feedback' => ['sendFeedbackReport', 'feedback', 'FEEDBACK'],
]);
