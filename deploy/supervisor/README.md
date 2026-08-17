# Supervisor cho XemPhatNguoi.vn

Toàn bộ tiến trình nền của project nằm trong một file `xemphatnguoi.conf`:

- 2 queue worker xử lý theo thứ tự `default`, `mails`, `user-logs`.
- 1 Laravel scheduler chạy heartbeat Discord mỗi 10 phút và dọn API log hằng ngày.
- 1 Laravel Reverb server lắng nghe nội bộ tại `127.0.0.1:8080`.

## Thông số máy chủ đang dùng

```text
PHP:        /usr/bin/php8.2
User:       xemphatnguoivn
Project:    /home/xemphatnguoivn/xemphatnguoi.vn
Queue:      Theo QUEUE_CONNECTION trong .env
Reverb:     127.0.0.1:8080
```

Nếu user hoặc đường dẫn hosting thực tế khác, sửa đồng thời `directory`, `command`,
`user`, `stdout_logfile` và `environment` trong file cấu hình trước khi cài.

Khuyến nghị production dùng Redis cho queue:

```dotenv
QUEUE_CONNECTION=redis
REDIS_QUEUE_RETRY_AFTER=90
```

`--timeout=60` của worker thấp hơn `retry_after=90`, tránh một job bị xử lý trùng
khi worker chưa kịp dừng.

## Cài đặt

```bash
sudo cp deploy/supervisor/xemphatnguoi.conf /etc/supervisor/conf.d/xemphatnguoi.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## Lệnh vận hành

```bash
sudo supervisorctl restart 'xemphatnguoi:*'
sudo supervisorctl status 'xemphatnguoi:*'
sudo supervisorctl tail -f xemphatnguoi-worker_00
sudo supervisorctl tail -f xemphatnguoi-worker_01
sudo supervisorctl tail -f xemphatnguoi-scheduler
sudo supervisorctl tail -f xemphatnguoi-reverb
```

Sau mỗi lần deploy code mới:

```bash
/usr/bin/php8.2 artisan queue:restart
/usr/bin/php8.2 artisan reverb:restart
```

Chỉ dùng một trong hai cách chạy scheduler. File này đã dùng `schedule:work`, vì
vậy không cấu hình thêm cron `schedule:run` để tránh chạy lịch hai lần.

Nếu chạy nhiều application server, chỉ bật `xemphatnguoi-scheduler` trên một máy.
Reverb hiện bind localhost và cần được reverse proxy từ domain WebSocket public.
