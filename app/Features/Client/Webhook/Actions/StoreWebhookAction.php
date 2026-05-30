<?php

namespace App\Features\Client\Webhook\Actions;

use App\Models\BankAccount;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Str;

class StoreWebhookAction
{
    /**
     * @param  array{name: string, url: string, event_keyword: string, status: string}  $payload
     */
    public function handle(User $user, BankAccount $bankAccount, array $payload): Webhook
    {
        return Webhook::query()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'name' => $payload['name'],
            'url' => $payload['url'],
            'secret_key' => Str::random(40),
            'events' => [$payload['event_keyword']],
            'status' => $payload['status'],
        ]);
    }
}
