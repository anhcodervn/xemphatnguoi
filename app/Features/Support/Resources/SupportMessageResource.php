<?php

namespace App\Features\Support\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'conversation_id' => (int) $this->support_conversation_id,
            'sender_id' => (int) $this->sender_id,
            'sender_role' => (string) $this->sender_role,
            'message' => (string) $this->message,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
