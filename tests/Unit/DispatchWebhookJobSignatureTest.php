<?php

use App\Jobs\DispatchWebhookJob;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('webhook_logs');
    Schema::dropIfExists('webhooks');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function ($table): void {
        $table->id();
        $table->string('username')->unique();
        $table->string('email')->nullable()->unique();
        $table->string('password');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('webhooks', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('bank_account_id')->nullable();
        $table->string('name');
        $table->string('url');
        $table->string('secret_key');
        $table->string('event_keyword')->nullable();
        $table->string('status')->default('active');
        $table->timestamps();
    });

    Schema::create('wallets', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('type')->default('main');
        $table->decimal('balance', 16, 2)->default(0);
        $table->decimal('hold_balance', 16, 2)->default(0);
        $table->decimal('total_recharge', 16, 2)->default(0);
        $table->decimal('total_spent', 16, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('webhook_logs', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('webhook_id');
        $table->string('event_keyword')->nullable();
        $table->longText('payload')->nullable();
        $table->longText('response')->nullable();
        $table->unsignedSmallInteger('status_code')->nullable();
        $table->unsignedInteger('attempt')->default(1);
        $table->timestamps();
    });
});

test('dispatch webhook job sends sign as md5 of secret and bank id', function () {
    Http::fake([
        'https://example.com/hook' => Http::response(['received' => true], 200),
    ]);

    $user = User::query()->create([
        'username' => 'webhook-user',
        'email' => 'webhook@example.com',
        'password' => 'password',
    ]);

    $webhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => 34,
        'name' => 'Webhook queue',
        'url' => 'https://example.com/hook',
        'secret_key' => 'secret-key-job',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ]);

    $job = new DispatchWebhookJob($webhook->id, 'payment-success', [
        'amount' => 10000,
        'reference' => 'REF-001',
    ]);

    $job->handle();

    $log = WebhookLog::query()->firstOrFail();

    expect($log->webhook_id)->toBe($webhook->id)
        ->and($log->event_keyword)->toBe('payment-success')
        ->and($log->status_code)->toBe(200)
        ->and($log->payload)->toContain('"sign":"'.md5('secret-key-job34').'"');

    Http::assertSent(function ($request) use ($webhook): bool {
        return $request->url() === $webhook->url
            && $request->hasHeader('X-Webhook-Secret', $webhook->secret_key)
            && $request['bank_id'] === 34
            && $request['sign'] === md5('secret-key-job34')
            && $request['event_keyword'] === 'payment-success'
            && $request['webhook_id'] === $webhook->id;
    });
});
