<?php

namespace App\Features\Admin\User\Actions;

use App\Exceptions\ApiException;
use App\Models\User;

class UpdateAdminUserStatusAction
{
    /**
     * @param  array{status:string}  $payload
     */
    public function handle(User $user, array $payload, User $actor): User
    {
        if ($actor->id === $user->id && $payload['status'] === 'blocked') {
            throw new ApiException('Không thể tự khóa tài khoản admin hiện tại.', 422);
        }

        $user->forceFill([
            'status' => $payload['status'] === 'blocked' ? 'banned' : 'active',
        ])->save();

        return $user->refresh();
    }
}
