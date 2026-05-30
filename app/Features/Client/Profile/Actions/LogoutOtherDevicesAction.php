<?php

namespace App\Features\Client\Profile\Actions;

use App\Models\User;
use Illuminate\Http\Request;

class LogoutOtherDevicesAction
{
    public function __construct(
        protected RecordUserLogAction $recordUserLogAction,
    ) {
    }

    /**
     * @param array{current_password:string} $payload
     */
    public function handle(User $user, array $payload, Request $request): void
    {
        unset($payload);

        $this->revokeOtherAccess($user, $request);

        $this->recordUserLogAction->handle($user, 'logout_other_devices', 'Đăng xuất các thiết bị khác', $request);
    }

    public function revokeOtherAccess(User $user, Request $request): void
    {
        $currentAccessToken = $user->currentAccessToken();

        $user->tokens()
            ->when($currentAccessToken !== null, function ($query) use ($currentAccessToken) {
                $query->whereKeyNot($currentAccessToken->getKey());
            })
            ->delete();

        $user->userSessions()
            ->where(function ($query) use ($request): void {
                if ($request->ip() !== null) {
                    $query->where('ip', '!=', $request->ip());
                } else {
                    $query->whereNotNull('ip');
                }

                if ($request->userAgent() !== null) {
                    $query->orWhere('user_agent', '!=', $request->userAgent());
                } else {
                    $query->orWhereNotNull('user_agent');
                }
            })
            ->delete();
    }
}
