<?php

namespace App\Features\Client\Webhook\Actions;

use App\Exceptions\ApiException;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Support\Str;

class StoreWebhookAction
{
    /**
     * @param  array{name: string, url: string, event_keyword?: string|null, status: string}  $payload
     */
    public function handle(User $user, BankAccount $bankAccount, array $payload): Webhook
    {
        $eventKeyword = trim((string) ($payload['event_keyword'] ?? ''));

        $alreadyExists = Webhook::query()
            ->where('user_id', $user->id)
            ->where('bank_account_id', $bankAccount->id)
            ->where('url', $payload['url'])
            ->where(function ($query) use ($eventKeyword): void {
                if ($eventKeyword === '') {
                    $query->whereNull('event_keyword')->orWhere('event_keyword', '');

                    return;
                }

                $query->where('event_keyword', $eventKeyword);
            })
            ->exists();

        if ($alreadyExists) {
            throw new ApiException('URL webhook này đã được cấu hình với event tương ứng.', 422);
        }

        return Webhook::query()->create([
            'user_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'name' => $payload['name'],
            'url' => $payload['url'],
            'secret_key' => Str::random(40),
            'event_keyword' => $eventKeyword !== '' ? $eventKeyword : null,
            'status' => $payload['status'],
        ]);
    }
}
