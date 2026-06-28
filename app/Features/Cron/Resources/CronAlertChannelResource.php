<?php

namespace App\Features\Cron\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CronAlertChannelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'cron_job_id' => $this->cron_job_id,
            'name' => $this->name,
            'type' => $this->type?->value ?? $this->type,
            'target_url' => $this->target_url,
            'telegram_chat_id' => $this->telegram_chat_id,
            'email' => $this->email,
            'events' => $this->events ?? [],
            'is_enabled' => (bool) $this->is_enabled,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
