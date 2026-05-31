<?php

use App\Jobs\DispatchWebhookJob;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

test('client can dispatch matching webhooks for a credit transaction', function () {
    Queue::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $bankAccount = BankAccount::query()->create([
        'user_id' => $user->id,
        'bank_name' => 'acb',
        'account_name' => 'Tai khoan ACB',
        'account_number' => '001122334455',
        'username' => 'acb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $transaction = BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => 'TXN-10001',
        'amount' => 100000,
        'description' => 'Khach chuyen tien order-abc vao tai khoan',
        'transaction_time' => now(),
        'type' => 'credit',
        'raw_data' => ['reference' => 'REF-001'],
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

    $this->postJson("/api/webhook/bank/{$bankAccount->id}/transactions/{$transaction->id}/dispatch")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.dispatched_count', 2);

    Queue::assertPushed(DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($matchingWebhook): bool {
        return $job->webhookId === $matchingWebhook->id
            && $job->eventKeyword === 'order-abc'
            && ($job->payload['transaction']['transaction_id'] ?? null) === 'TXN-10001';
    });

    Queue::assertPushed(DispatchWebhookJob::class, function (DispatchWebhookJob $job) use ($wildcardWebhook): bool {
        return $job->webhookId === $wildcardWebhook->id
            && $job->eventKeyword === ''
            && ($job->payload['transaction']['transaction_id'] ?? null) === 'TXN-10001';
    });

    Queue::assertPushed(DispatchWebhookJob::class, 2);
});

test('client dispatch transaction endpoint ignores debit transactions', function () {
    Queue::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $bankAccount = BankAccount::query()->create([
        'user_id' => $user->id,
        'bank_name' => 'mb',
        'account_name' => 'Tai khoan MB',
        'account_number' => '99887766',
        'username' => 'mb_user',
        'password' => 'secret-password',
        'status' => 'active',
    ]);

    $transaction = BankTransaction::query()->create([
        'bank_account_id' => $bankAccount->id,
        'transaction_id' => 'TXN-DEBIT-1',
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

    $this->postJson("/api/webhook/bank/{$bankAccount->id}/transactions/{$transaction->id}/dispatch")
        ->assertSuccessful()
        ->assertJsonPath('status', true)
        ->assertJsonPath('data.dispatched_count', 0);

    Queue::assertNothingPushed();
});
