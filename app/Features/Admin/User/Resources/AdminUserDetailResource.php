<?php

namespace App\Features\Admin\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $wallet = $this->resource['wallet'];
        $currentSubscription = $this->resource['current_subscription'];

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status === 'banned' ? 'blocked' : $user->status,
            'avatar' => $user->avatar,
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'last_login_at' => $user->last_login_at?->toISOString(),
            'last_login_ip' => $user->last_login_ip,
            'wallet' => $wallet ? [
                'id' => $wallet->id,
                'balance' => (float) $wallet->balance,
                'hold_balance' => (float) $wallet->hold_balance,
                'total_spent' => (float) $wallet->total_spent,
            ] : null,
            'current_package' => $currentSubscription ? [
                'id' => $currentSubscription->package_id,
                'name' => $currentSubscription->package_name,
                'price' => (float) $currentSubscription->package_price,
                'starts_at' => $currentSubscription->starts_at?->toISOString(),
                'expires_at' => $currentSubscription->expires_at?->toISOString(),
                'status' => $currentSubscription->status?->value ?? $currentSubscription->status,
            ] : null,
            'stats' => $this->resource['stats'],
            'latest_login' => $this->resource['latest_login'],
        ];
    }
}
