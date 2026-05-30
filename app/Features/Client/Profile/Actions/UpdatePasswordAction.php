<?php

namespace App\Features\Client\Profile\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function __construct(
        protected RecordUserLogAction $recordUserLogAction,
        protected LogoutOtherDevicesAction $logoutOtherDevicesAction,
    ) {
    }

    /**
     * @param array{current_password:string,password:string,password_confirmation:string,logout_other_devices?:bool} $payload
     */
    public function handle(User $user, array $payload, Request $request): void
    {
        $user->forceFill([
            'password' => Hash::make($payload['password']),
        ])->save();

        if ((bool) ($payload['logout_other_devices'] ?? false)) {
            $this->logoutOtherDevicesAction->revokeOtherAccess($user, $request);
        }

        $this->recordUserLogAction->handle($user, 'password_change', 'Đổi mật khẩu tài khoản', $request);
    }
}
