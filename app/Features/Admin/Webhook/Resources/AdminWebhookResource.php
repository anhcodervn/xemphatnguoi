<?php

namespace App\Features\Admin\Webhook\Resources;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Webhook
 */
class AdminWebhookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'url' => $this->url,
            'event_keyword' => $this->event_keyword,
            'status' => $this->status,
            'last_called_at' => $this->logs_max_created_at,
            'success_count' => $this->success_logs_count ?? 0,
            'failed_count' => $this->failed_logs_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
