<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class DispatchWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $webhookId,
        public string $eventKeyword,
        public array $payload = [],
    ) {
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function handle(): void
    {
        $webhook = Webhook::query()->findOrFail($this->webhookId);

        $requestPayload = [
            'event_keyword' => $this->eventKeyword,
            'webhook_id' => $webhook->id,
            'bank_account_id' => $webhook->bank_account_id,
            'payload' => $this->payload,
        ];

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->withHeaders([
                    'X-Webhook-Secret' => $webhook->secret_key,
                ])
                ->post($webhook->url, $requestPayload);

            WebhookLog::query()->create([
                'webhook_id' => $webhook->id,
                'event_keyword' => $this->eventKeyword,
                'payload' => json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response' => $response->body(),
                'status_code' => $response->status(),
                'attempt' => $this->attempts(),
            ]);

            $response->throw();
        } catch (Throwable $exception) {
            if (! isset($response)) {
                WebhookLog::query()->create([
                    'webhook_id' => $webhook->id,
                    'event_keyword' => $this->eventKeyword,
                    'payload' => json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'response' => $exception->getMessage(),
                    'status_code' => null,
                    'attempt' => $this->attempts(),
                ]);
            }

            throw $exception;
        }
    }
}
