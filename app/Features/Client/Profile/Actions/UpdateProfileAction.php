<?php

namespace App\Features\Client\Profile\Actions;

use App\Models\User;
use Illuminate\Http\Request;

class UpdateProfileAction
{
    public function __construct(
        protected RecordUserLogAction $recordUserLogAction,
    ) {
    }

    /**
     * @param array{full_name:string,email:string,phone:?string,avatar:?string,username:string} $payload
     */
    public function handle(User $user, array $payload, Request $request): User
    {
        $emailChanged = $user->email !== $payload['email'];

        $user->fill([
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'avatar' => $payload['avatar'] ?? null,
            'username' => $payload['username'],
        ]);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->recordUserLogAction->handle($user, 'profile_update', 'Cập nhật thông tin tài khoản', $request);

        return $user->fresh() ?? $user;
    }
}
