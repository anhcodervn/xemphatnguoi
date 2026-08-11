<?php

namespace App\Features\Support\Resources;

use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestMessage = $this->whenLoaded('latestMessage');
        $user = $this->whenLoaded('user');

        return [
            'id' => (int) $this->id,
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'username' => (string) $user->username,
                'email' => $request->user()?->role === SupportMessage::ROLE_ADMIN ? (string) $user->email : null,
                'avatar' => $user->avatar,
            ] : null,
            'status' => (string) $this->status,
            'last_message' => $latestMessage ? SupportMessageResource::make($latestMessage)->resolve($request) : null,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'unread_count' => (int) ($this->unread_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
