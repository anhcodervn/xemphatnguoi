# Discord webhook

Hệ thống dùng đúng 5 room Discord. Mỗi room tạo một webhook riêng và cấu hình URL trong `.env` của server. URL webhook không được lưu trong database hoặc nhập trên giao diện admin.

## Room cần tạo

| Room Discord | Biến `.env` | Báo cáo nhận được |
|---|---|---|
| `#xpn-ops` | `DISCORD_WEBHOOK_OPS` | Heartbeat production, queue thất bại, lỗi nguồn tra cứu, cảnh báo bảo mật và phục hồi hệ thống |
| `#xpn-activity` | `DISCORD_WEBHOOK_ACTIVITY` | Tài khoản đăng ký mới và sự kiện vòng đời tài khoản |
| `#xpn-sales` | `DISCORD_WEBHOOK_SALES` | Nạp tiền thành công, giao dịch ví và sự kiện doanh thu |
| `#xpn-support` | `DISCORD_WEBHOOK_SUPPORT` | Tin nhắn hỗ trợ mới và góp ý từ biểu mẫu liên hệ |
| `#xpn-staging` | `DISCORD_WEBHOOK_STAGING` | Toàn bộ báo cáo phát sinh ở local, testing và staging |

Production không gửi vào `#xpn-staging`. Khi `DISCORD_WEBHOOK_STAGING` được cấu hình, mọi báo cáo ngoài production tự động chuyển vào room này để không làm nhiễu các room production.

## Cấu hình production

```env
DISCORD_WEBHOOK_OPS=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_ACTIVITY=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_SALES=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_SUPPORT=https://discord.com/api/webhooks/...
DISCORD_WEBHOOK_STAGING=https://discord.com/api/webhooks/...

DISCORD_BOT_NAME="XemPhatNguoi Monitor"
DISCORD_BOT_AVATAR_URL=
DISCORD_SERVER_NAME="prod-app-01"
DISCORD_SERVER_IP=
DISCORD_SERVER_ROLE=app
DISCORD_SERVER_REGION=sgp-1
```

Sau khi thay đổi `.env`:

```bash
php artisan config:clear
php artisan monitor:discord-heartbeat --channel=ops --title="Heartbeat production"
```

Scheduler tự gửi heartbeat mỗi 10 phút vào `#xpn-ops` ở production và `#xpn-staging` ở môi trường khác.

## Quy tắc gửi báo cáo

- Queue chỉ báo khi job thất bại; job thành công không gửi Discord để tránh spam.
- Lỗi nguồn tra cứu được giới hạn tối đa một thông báo mỗi 5 phút cho từng nguồn.
- Nạp tiền thành công gửi vào `#xpn-sales`, không gửi nhầm sang activity.
- Góp ý và chat hỗ trợ cùng gửi vào `#xpn-support`.
- Mọi payload đều tắt Discord mentions để dữ liệu người dùng không thể ping `@everyone` hoặc role.
- Lỗi gửi webhook chỉ được report bằng tên channel và loại lỗi; URL/token webhook không được gắn vào exception mới.

## Biến tương thích cũ

Các biến dưới đây chỉ là alias tương thích cho deployment cũ. Cấu hình 5 biến canonical ở trên được ưu tiên:

```env
DISCORD_WEBHOOK_QUEUE=
DISCORD_WEBHOOK_INFO=
DISCORD_WEBHOOK_SECURITY=
DISCORD_WEBHOOK_ALERTS=
DISCORD_WEBHOOK_RECOVERED=
DISCORD_WEBHOOK_PROVIDER=
DISCORD_WEBHOOK_FEEDBACK=
```

Các alias `QUEUE`, `INFO`, `SECURITY`, `ALERTS`, `RECOVERED` và `PROVIDER` được gom vào `#xpn-ops`; `FEEDBACK` được gom vào `#xpn-support`.

## Xem trạng thái trong admin

Vào **Admin → Settings → Webhook Discord** để xem room nào đã hoặc chưa được cấu hình. Backend chỉ trả trạng thái boolean, tên room và tên biến môi trường; không trả URL webhook về trình duyệt.
