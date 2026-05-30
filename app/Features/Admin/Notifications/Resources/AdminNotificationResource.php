<?php

namespace App\Features\Admin\Notifications\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function (): array {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'username' => $this->user?->username,
                    'full_name' => $this->user?->full_name,
                    'email' => $this->user?->email,
                    'phone' => $this->user?->phone,
                ];
            }),
            'title' => $this->title,
            'content' => $this->content,
            'redirect_url' => $this->redirect_url,
            'type' => $this->type,
            'reads_count' => (int) ($this->reads_count ?? 0),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
