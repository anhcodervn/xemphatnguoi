<?php

namespace App\Features\Client\Profile\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'full_name' => $this->full_name,
            'avatar' => $this->avatar,
            'status' => $this->status,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_login_ip' => $this->last_login_ip,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'security' => [
                'has_2fa' => false,
                'email_verified' => $this->email_verified_at !== null,
            ],
            'api_access' => $this->getAttribute('api_access'),
        ];
    }
}
