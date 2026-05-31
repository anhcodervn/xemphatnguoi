<?php

namespace App\Features\Client\Webhook\Actions;

use App\Exceptions\ApiException;
use App\Models\Webhook;

class UpdateWebhookAction
{
    /**
     * @param  array{name: string, url: string, event_keyword?: string|null, status: string}  $payload
     */
    public function handle(Webhook $webhook, array $payload): Webhook
    {
        $eventKeyword = trim((string) ($payload['event_keyword'] ?? ''));

        $alreadyExists = Webhook::query()
            ->where('user_id', $webhook->user_id)
            ->where('bank_account_id', $webhook->bank_account_id)
            ->where('url', $payload['url'])
            ->whereKeyNot($webhook->id)
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

        $webhook->forceFill([
            'name' => $payload['name'],
            'url' => $payload['url'],
            'event_keyword' => $eventKeyword !== '' ? $eventKeyword : null,
            'status' => $payload['status'],
        ])->save();

        return $webhook->fresh() ?? $webhook;
    }
}
