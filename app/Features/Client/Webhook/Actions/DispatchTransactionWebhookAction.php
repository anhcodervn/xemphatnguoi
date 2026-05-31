<?php

namespace App\Features\Client\Webhook\Actions;

use App\Jobs\DispatchWebhookJob;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Collection;

class DispatchTransactionWebhookAction
{
    public function handle(User $user, BankAccount $bankAccount, BankTransaction $bankTransaction): int
    {
        if ($bankTransaction->type !== 'credit') {
            return 0;
        }

        $normalizedDescription = mb_strtolower(trim((string) $bankTransaction->description));
        $matchingWebhooks = $this->matchingWebhooks($user, $bankAccount, $normalizedDescription);
        $payload = $this->payload($bankAccount, $bankTransaction);

        foreach ($matchingWebhooks as $webhook) {
            DispatchWebhookJob::dispatch(
                $webhook->id,
                $this->eventKeyword($webhook),
                $payload,
            );
        }

        return $matchingWebhooks->count();
    }

    /**
     * @return Collection<int, Webhook>
     */
    protected function matchingWebhooks(User $user, BankAccount $bankAccount, string $normalizedDescription): Collection
    {
        return Webhook::query()
            ->where('user_id', $user->id)
            ->where('bank_account_id', $bankAccount->id)
            ->where('status', 'active')
            ->get()
            ->filter(function (Webhook $webhook) use ($normalizedDescription): bool {
                $eventKeyword = $this->eventKeyword($webhook);

                if ($eventKeyword === '') {
                    return true;
                }

                if ($normalizedDescription === '') {
                    return false;
                }

                return str_contains($normalizedDescription, $eventKeyword);
            })
            ->values();
    }

    protected function eventKeyword(Webhook $webhook): string
    {
        return mb_strtolower(trim((string) ($webhook->event_keyword ?? '')));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(BankAccount $bankAccount, BankTransaction $bankTransaction): array
    {
        return [
            'source' => 'client-bank-manager.transaction',
            'bank_account_id' => $bankAccount->id,
            'transaction' => [
                'id' => $bankTransaction->id,
                'transaction_id' => $bankTransaction->transaction_id,
                'amount' => (string) $bankTransaction->amount,
                'description' => $bankTransaction->description,
                'transaction_time' => $bankTransaction->transaction_time?->toDateTimeString(),
                'type' => $bankTransaction->type,
                'raw_data' => $bankTransaction->raw_data,
                'created_at' => $bankTransaction->created_at?->toDateTimeString(),
                'updated_at' => $bankTransaction->updated_at?->toDateTimeString(),
            ],
        ];
    }
}
