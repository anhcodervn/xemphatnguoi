<?php

namespace App\Features\Client\Webhook\Actions;

use App\Jobs\DispatchWebhookJob;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Webhook;

class DispatchWebhookAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(User $user, BankAccount $bankAccount, string $eventKeyword, array $payload = []): int
    {
        $webhooks = Webhook::query()
            ->where('user_id', $user->id)
            ->where('bank_account_id', $bankAccount->id)
            ->where('status', 'active')
            ->where(function ($query) use ($eventKeyword): void {
                $query
                    ->where('event_keyword', $eventKeyword)
                    ->orWhere(function ($builder): void {
                        $builder->whereNull('event_keyword')->orWhere('event_keyword', '');
                    });
            })
            ->get();

        foreach ($webhooks as $webhook) {
            DispatchWebhookJob::dispatch($webhook->id, $eventKeyword, $payload);
        }

        return $webhooks->count();
    }
}
