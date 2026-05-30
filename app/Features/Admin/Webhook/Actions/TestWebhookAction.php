<?php

namespace App\Features\Admin\Webhook\Actions;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Throwable;

class TestWebhookAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Webhook $webhook): array
    {
        $payload = [
            'event_keyword' => 'admin.test',
            'webhook_id' => $webhook->id,
            'bank_account_id' => $webhook->bank_account_id,
            'payload' => [
                'message' => 'Admin webhook test',
                'tested_at' => now()->toISOString(),
            ],
        ];

        $startedAt = microtime(true);
        $statusCode = null;
        $responseBody = null;
        $success = false;

        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->withHeaders([
                    'X-Webhook-Secret' => $webhook->secret_key,
                ])
                ->post($webhook->url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            $success = $response->successful();
        } catch (Throwable $exception) {
            $responseBody = $exception->getMessage();
        }

        $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

        WebhookLog::query()->create([
            'webhook_id' => $webhook->id,
            'event_keyword' => 'admin.test',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response' => $responseBody,
            'status_code' => $statusCode,
            'attempt' => 1,
        ]);

        return [
            'webhook_id' => $webhook->id,
            'success' => $success,
            'http_status' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'response' => $responseBody,
        ];
    }
}
