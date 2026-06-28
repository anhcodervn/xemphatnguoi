<?php

namespace App\Features\Client\Profile\Actions;

use App\Models\User;
use App\Support\MailQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordAction
{
    public function __construct(
        protected RecordUserLogAction $recordUserLogAction,
        protected LogoutOtherDevicesAction $logoutOtherDevicesAction,
        protected MailQueue $mailQueue,
    ) {}

    /**
     * @param  array{current_password:string,password:string,password_confirmation:string,logout_other_devices?:bool}  $payload
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

        if (is_string($user->email) && trim($user->email) !== '') {
            $this->mailQueue->dispatch(
                to: $user->email,
                subjectText: 'Đổi mật khẩu thành công',
                title: 'Mật khẩu tài khoản đã được thay đổi',
                messageLines: [
                    'Mật khẩu tài khoản của bạn vừa được cập nhật thành công.',
                    sprintf('Thời gian: %s', now()->format('d/m/Y H:i:s')),
                    sprintf('IP thực hiện: %s', (string) ($request->ip() ?: 'Không xác định')),
                    (bool) ($payload['logout_other_devices'] ?? false)
                        ? 'Các thiết bị khác đã được đăng xuất theo yêu cầu của bạn.'
                        : 'Nếu đây không phải bạn, hãy đổi lại mật khẩu và liên hệ hỗ trợ ngay.',
                ],
                ctaText: 'Kiểm tra tài khoản',
                ctaUrl: url('/profile'),
            );
        }
    }
}
