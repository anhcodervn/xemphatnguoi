<?php

use App\Features\Client\Webhook\Actions\DispatchTransactionWebhookAction;
use App\Jobs\DispatchWebhookJob;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('database.connections.mysql.database', 'naptientudong');
    config()->set('database.connections.mysql.host', '192.168.1.60');
    config()->set('database.connections.mysql.port', '3306');
    config()->set('database.connections.mysql.username', 'admin');
    config()->set('database.connections.mysql.password', 'tuthan1801@');
    config()->set('database.default', 'mysql');
    DB::purge('mysql');
    DB::reconnect('mysql');
});

test('dispatch transaction webhook action queues matching active webhooks for a credit transaction', function () {
    Queue::fake();

    $user = User::factory()->create();
    $suffix = (string) str()->ulid();

    $bankAccount = BankAccount::query()->create([
        'user_id' => $user->id,
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => "UT-001122334455-{$suffix}",
        'username' => "acb_user_unit_{$suffix}",
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $transaction = BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => "UNIT-TXN-10001-{$suffix}",
        'amount' => 100000,
        'description' => 'Khach chuyen tien order-abc vao tai khoan',
        'transaction_time' => now(),
        'type' => 'credit',
        'raw_data' => ['reference' => 'REF-UNIT-001'],
    ]);

    $matchingWebhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook match keyword',
        'url' => 'https://example.com/match',
        'secret_key' => 'secret-key-match',
        'event_keyword' => 'order-abc',
        'status' => 'active',
    ]);

    $wildcardWebhook = Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook all credits',
        'url' => 'https://example.com/all',
        'secret_key' => 'secret-key-all',
        'event_keyword' => null,
        'status' => 'active',
    ]);

    Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook sai keyword',
        'url' => 'https://example.com/wrong',
        'secret_key' => 'secret-key-wrong',
        'event_keyword' => 'order-xyz',
        'status' => 'active',
    ]);

    $dispatchedCount = app(DispatchTransactionWebhookAction::class)->handle($user, $bankAccount, $transaction);

    expect($dispatchedCount)->toBe(2);

    Queue::assertPushed(DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($matchingWebhook, $suffix): bool {
        return $job->webhookId === $matchingWebhook->id
            && $job->eventKeyword === 'order-abc'
            && ($job->payload['transaction']['transaction_id'] ?? null) === "UNIT-TXN-10001-{$suffix}";
    });

    Queue::assertPushed(DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($wildcardWebhook, $suffix): bool {
        return $job->webhookId === $wildcardWebhook->id
            && $job->eventKeyword === ''
            && ($job->payload['transaction']['transaction_id'] ?? null) === "UNIT-TXN-10001-{$suffix}";
    });

    Queue::assertPushed(DispatchWebhookJob::class, 2);
});

test('dispatch transaction webhook action ignores debit transactions', function () {
    Queue::fake();

    $user = User::factory()->create();
    $suffix = (string) str()->ulid();

    $bankAccount = BankAccount::query()->create([
        'user_id' => $user->id,
        'bank_name' => 'mb',
        'account_name' => 'Tai khoan MB',
        'account_number' => "UT-99887766-{$suffix}",
        'username' => "mb_user_unit_{$suffix}",
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $transaction = BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => "UNIT-TXN-DEBIT-1-{$suffix}",
        'amount' => 50000,
        'description' => 'Chuyen tien ra',
        'transaction_time' => now(),
        'type' => 'debit',
    ]);

    Webhook::query()->create([
        'user_id' => $user->id,
        'bank_account_id' => $bankAccount->id,
        'name' => 'Webhook all credits',
        'url' => 'https://example.com/all',
        'secret_key' => 'secret-key-all',
        'event_keyword' => null,
        'status' => 'active',
    ]);

    $dispatchedCount = app(DispatchTransactionWebhookAction::class)->handle($user, $bankAccount, $transaction);

    expect($dispatchedCount)->toBe(0);
    Queue::assertNothingPushed();
});
