<?php

namespace App\Features\Client\Webhook\Actions;

use App\Models\Webhook;

class UpdateWebhookAction
{
    /**
     * @param  array{name: string, url: string, event_keyword: string, status: string}  $payload
     */
    public function handle(Webhook $webhook, array $payload): Webhook
    {
        $webhook->forceFill([
            'name' => $payload['name'],
            'url' => $payload['url'],
            'events' => [$payload['event_keyword']],
            'status' => $payload['status'],
        ])->save();

        return $webhook->fresh() ?? $webhook;
    }
}
