<?php

namespace App\Features\Client\Notification\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope,
            'title' => $this->title,
            'content' => $this->content,
            'redirect_url' => $this->redirect_url,
            'type' => $this->type,
            'is_read' => ((int) ($this->is_read ?? 0)) > 0,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
