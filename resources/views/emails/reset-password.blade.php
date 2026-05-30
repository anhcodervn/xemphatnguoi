<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;">
                    <tr>
                        <td style="padding:20px 20px 8px;">
                            <h1 style="margin:0;font-size:20px;line-height:1.4;color:#0f172a;">Yêu cầu đặt lại mật khẩu</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 20px 10px;">
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#334155;">Xin chào {{ $name }}, bạn vừa yêu cầu đặt lại mật khẩu.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 20px 10px;">
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#334155;">Liên kết sẽ hết hạn sau {{ $expireMinutes }} phút.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 20px 20px;">
                            <a href="{{ $resetUrl }}" style="display:inline-block;padding:10px 14px;background:#4f46e5;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;">
                                Đặt lại mật khẩu
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
