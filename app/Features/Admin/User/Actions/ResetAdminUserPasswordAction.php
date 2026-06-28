<?php

namespace App\Features\Admin\User\Actions;

use App\Features\Client\Profile\Actions\LogoutOtherDevicesAction;
use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Models\User;
use App\Support\MailQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetAdminUserPasswordAction
{
    public function __construct(
        private readonly LogoutOtherDevicesAction $logoutOtherDevicesAction,
        private readonly RecordUserLogAction $recordUserLogAction,
        private readonly MailQueue $mailQueue,
    ) {}

    /**
     * @param  array{password:string,password_confirmation:string}  $payload
     */
    public function handle(User $user, array $payload, User $actor, Request $request): void
    {
        $user->forceFill([
            'password' => Hash::make($payload['password']),
        ])->save();

        $this->logoutOtherDevicesAction->revokeOtherAccess($user, $request);

        $actorLabel = $actor->email ?: $actor->username ?: sprintf('admin #%d', $actor->id);

        $this->recordUserLogAction->handle(
            $user,
            'admin_reset_password',
            sprintf('Mật khẩu tài khoản được admin %s cập nhật', $actorLabel),
            $request,
        );

        if (is_string($user->email) && trim($user->email) !== '') {
            $this->mailQueue->dispatch(
                to: $user->email,
                subjectText: 'Mật khẩu tài khoản đã được cập nhật',
                title: 'Mật khẩu tài khoản của bạn vừa được thay đổi',
                messageLines: [
                    'Quản trị viên vừa cập nhật mật khẩu cho tài khoản của bạn.',
                    sprintf('Thời gian: %s', now()->format('d/m/Y H:i:s')),
                    sprintf('Thực hiện bởi: %s', $actorLabel),
                    'Các phiên đăng nhập khác đã được đăng xuất để bảo mật tài khoản.',
                    'Nếu đây không phải yêu cầu của bạn, hãy liên hệ hỗ trợ ngay.',
                ],
                ctaText: 'Đăng nhập lại',
                ctaUrl: url('/login'),
            );
        }
    }
}
