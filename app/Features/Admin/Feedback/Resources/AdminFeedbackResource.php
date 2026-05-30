<?php

namespace App\Features\Admin\Feedback\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminFeedbackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function (): ?array {
                if (! $this->user) {
                    return null;
                }

                return [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                ];
            }),
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'content' => $this->content,
            'status' => $this->status,
            'handled_at' => optional($this->handled_at)?->toDateTimeString(),
            'handled_by' => $this->handled_by,
            'handler' => $this->whenLoaded('handler', function (): ?array {
                if (! $this->handler) {
                    return null;
                }

                return [
                    'id' => $this->handler->id,
                    'username' => $this->handler->username,
                    'full_name' => $this->handler->full_name,
                ];
            }),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'updated_at' => optional($this->updated_at)?->toDateTimeString(),
        ];
    }
}
