<?php

use App\Jobs\DispatchWebhookJob;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('client can list bank webhooks scoped by current user and bank account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Sanctum::actingAs($user);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $otherBankAccount = BankAccount::query()->create([
        'bank_name' => 'vcb',
        'account_name' => 'Tai khoan VCB',
        'account_number' => '9988776655',
        'username' => 'vcb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $expectedWebhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook ACB',
        'url' => 'https://example.com/acb',
        'secret_key' => 'secret-key-1',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ]);

    Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $otherBankAccount->id,
        'name' => 'Webhook khac the',
        'url' => 'https://example.com/other-bank',
        'secret_key' => 'secret-key-2',
        'event_keyword' => 'other-event',
        'status' => 'inactive',
    ]);

    Webhook::query()->create([
        'user_id' => $otherUser->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook user khac',
        'url' => 'https://example.com/other-user',
        'secret_key' => 'secret-key-3',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ]);

    $this->getJson("/api/webhook/bank/{$bankAccount->id}")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $expectedWebhook->id)
        ->assertJsonPath('data.0.name', 'Webhook ACB')
        ->assertJsonPath('data.0.event_keyword', 'payment-success')
        ->assertJsonPath('data.0.secret_key_masked', 'sec******y-1')
        ->assertJsonMissingPath('data.0.secret_key');
});

test('client can create webhook for bank account', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $this->postJson("/api/webhook/bank/{$bankAccount->id}", [
        'name' => 'Webhook thanh toan',
        'url' => 'https://example.com/payment-hook',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Tạo webhook thành công.')
        ->assertJsonPath('data.bank_account_id', $bankAccount->id)
        ->assertJsonPath('data.name', 'Webhook thanh toan')
        ->assertJsonPath('data.event_keyword', 'payment-success')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonMissingPath('data.secret_key');

    $webhook = Webhook::query()->firstOrFail();

    expect($webhook->user_id)->toBe($user->id)
        ->and($webhook->bank_account_id)->toBe($bankAccount->id)
        ->and($webhook->event_keyword)->toBe('payment-success')
        ->and($webhook->secret_key)->not->toBe('');
});

test('client can update own webhook', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $webhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => BankAccount::query()->create([
            'bank_name' => 'acb',
            'account_name' => 'Tai khoan ACB',
            'account_number' => '001122334455',
            'username' => 'acb_user',
            'password' => 'secret-password',
            'status' => 'active',
        ])->id,
        'name' => 'Webhook cu',
        'url' => 'https://example.com/old',
        'secret_key' => 'secret-key-old',
        'event_keyword' => 'old-event',
        'status' => 'inactive',
    ]);

    $this->putJson("/api/webhook/{$webhook->id}", [
        'name' => 'Webhook moi',
        'url' => 'https://example.com/new',
        'event_keyword' => 'new-event',
        'status' => 'active',
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Cập nhật webhook thành công.')
        ->assertJsonPath('data.name', 'Webhook moi')
        ->assertJsonPath('data.url', 'https://example.com/new')
        ->assertJsonPath('data.event_keyword', 'new-event')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.secret_key_masked', 'sec******old')
        ->assertJsonMissingPath('data.secret_key');

    $webhook->refresh();

    expect($webhook->name)->toBe('Webhook moi')
        ->and($webhook->url)->toBe('https://example.com/new')
        ->and($webhook->event_keyword)->toBe('new-event')
        ->and($webhook->status)->toBe('active');
});

test('client can reveal own webhook secret on demand', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $webhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => BankAccount::query()->create([
            'bank_name' => 'acb',
            'account_name' => 'Tai khoan ACB',
            'account_number' => '001122334455',
            'username' => 'acb_user',
            'password' => 'secret-password',
            'status' => 'active',
        ])->id,
        'name' => 'Webhook reveal',
        'url' => 'https://example.com/reveal',
        'secret_key' => 'secret-key-reveal',
        'event_keyword' => 'reveal-event',
        'status' => 'active',
    ]);

    $this->getJson("/api/webhook/{$webhook->id}/secret")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.id', $webhook->id)
        ->assertJsonPath('data.secret_key', 'secret-key-reveal');
});

test('client can delete own webhook', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $webhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => BankAccount::query()->create([
            'bank_name' => 'acb',
            'account_name' => 'Tai khoan ACB',
            'account_number' => '001122334455',
            'username' => 'acb_user',
            'password' => 'secret-password',
            'status' => 'active',
        ])->id,
        'name' => 'Webhook xoa',
        'url' => 'https://example.com/delete',
        'secret_key' => 'secret-key-delete',
        'event_keyword' => 'delete-event',
        'status' => 'active',
    ]);

    $this->deleteJson("/api/webhook/{$webhook->id}")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Xóa webhook thành công.');

    $this->assertModelMissing($webhook);
});

test('dispatch endpoint queues only matching active webhooks for current bank account', function () {
    Queue::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $bankAccount = BankAccount::query()->create([
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $otherBankAccount = BankAccount::query()->create([
        'bank_name' => 'vcb',
        'account_name' => 'Tai khoan VCB',
        'account_number' => '9988776655',
        'username' => 'vcb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $matchingWebhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook hop le',
        'url' => 'https://example.com/matching',
        'secret_key' => 'secret-key-matching',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ]);

    Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook sai event',
        'url' => 'https://example.com/wrong-event',
        'secret_key' => 'secret-key-wrong-event',
        'event_keyword' => 'order-failed',
        'status' => 'active',
    ]);

    Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $otherBankAccount->id,
        'name' => 'Webhook sai the',
        'url' => 'https://example.com/wrong-bank',
        'secret_key' => 'secret-key-wrong-bank',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ]);

    Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook tat',
        'url' => 'https://example.com/inactive',
        'secret_key' => 'secret-key-inactive',
        'event_keyword' => 'payment-success',
        'status' => 'inactive',
    ]);

    $this->postJson("/api/webhook/bank/{$bankAccount->id}/dispatch", [
        'event_keyword' => 'payment-success',
        'payload' => [
            'amount' => 10000,
        ],
    ])
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('message', 'Đã đưa 1 webhook vào hàng chờ.')
        ->assertJsonPath('data.dispatched_count', 1);

    Queue::assertPushed(DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($matchingWebhook): bool {
        return $job->webhookId === $matchingWebhook->id
            && $job->eventKeyword === 'payment-success'
            && $job->payload['amount'] === 10000;
    });

    Queue::assertPushed(DispatchWebhookJob::class, 1);
});

test('client can view webhook logs', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $webhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => BankAccount::query()->create([
            'bank_name' => 'acb',
            'account_name' => 'Tai khoan ACB',
            'account_number' => '001122334455',
            'username' => 'acb_user',
            'password' => 'secret-password',
            'status' => 'active',
        ])->id,
        'name' => 'Webhook log',
        'url' => 'https://example.com/logs',
        'secret_key' => 'secret-key-logs',
        'event_keyword' => 'payment-success',
        'status' => 'active',
    ]);

    $log = WebhookLog::query()->create([
        'webhook_id' => $webhook->id,
        'event_keyword' => 'payment-success',
        'payload' => '{"amount":10000}',
        'response' => '{"ok":true}',
        'status_code' => 200,
        'attempt' => 2,
    ]);

    $this->getJson("/api/webhook/{$webhook->id}/logs")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $log->id)
        ->assertJsonPath('data.0.event_keyword', 'payment-success')
        ->assertJsonPath('data.0.status_code', 200)
        ->assertJsonPath('data.0.attempt', 2);
});

test('dispatch webhook job stores execution log for successful response', function () {
    Http::fake([
        'https://example.com/hook' => Http::response(['received' => true], 200),
    ]);

    $webhook = Webhook::query()->create([
        'user_id' => User::factory()->create()->id,
        'bank_account_id' => BankAccount::query()->create([
            'bank_name' => 'acb',
            'account_name' => 'Tai khoan ACB',
            'account_number' => '001122334455',
            'username' => 'acb_user',
            'password' => 'secret-password',
            'status' => 'active',
        ])->id,
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
        ->and($log->attempt)->toBe(1)
        ->and($log->payload)->toContain('"amount":10000')
        ->and($log->response)->toContain('received');

    Http::assertSent(function ($request) use ($webhook): bool {
        return $request->url() === $webhook->url
            && $request->hasHeader('X-Webhook-Secret', $webhook->secret_key)
            && $request['bank_id'] === $webhook->bank_account_id
            && $request['sign'] === md5($webhook->secret_key.(string) $webhook->bank_account_id)
            && $request['event_keyword'] === 'payment-success'
            && $request['webhook_id'] === $webhook->id;
    });
});
