<?php

namespace App\Features\Admin\User\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentSubscription = collect($this->userSubscriptions ?? [])->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status === 'banned' ? 'blocked' : $this->status,
            'wallet_balance' => $this->wallet ? (float) $this->wallet->balance : null,
            'current_package' => $currentSubscription ? [
                'id' => $currentSubscription->package_id,
                'name' => $currentSubscription->package_name,
                'expires_at' => $currentSubscription->expires_at?->toISOString(),
                'status' => $currentSubscription->status?->value ?? $currentSubscription->status,
            ] : null,
            'created_at' => $this->created_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
        ];
    }
}
