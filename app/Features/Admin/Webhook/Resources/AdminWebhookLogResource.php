<?php

namespace App\Features\Admin\Webhook\Resources;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WebhookLog
 */
class AdminWebhookLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status_code !== null && $this->status_code >= 200 && $this->status_code < 300
            ? 'success'
            : 'failed';

        return [
            'id' => $this->id,
            'event' => $this->event_keyword,
            'payload_preview' => mb_substr((string) $this->payload, 0, 300),
            'http_status' => $this->status_code,
            'response_time' => null,
            'status' => $status,
            'retry_count' => $this->attempt,
            'error_message' => $status === 'failed' ? $this->response : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
