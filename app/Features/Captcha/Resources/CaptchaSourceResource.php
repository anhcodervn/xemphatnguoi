<?php

namespace App\Features\Captcha\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CaptchaSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->role === 'admin';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'driver' => $this->driver,
            'api_base_url' => $this->api_base_url,
            'balance' => $this->balance,
            'credentials' => $isAdmin ? $this->credentials : null,
            'settings' => $this->settings,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'services_count' => $this->whenCounted('services'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
