# ARCHITECTURE

## Stack
- Laravel 12
- Vue 3 + Vite + TypeScript
- TailwindCSS
- Axios + Sanctum session auth
- Pest

## Folder Model
- `app/Features`
  - `Admin/...`
  - `Client/...`
  - `Captcha/...`
- `app/Models`
  - Eloquent models cho wallet, user, captcha services, captcha tasks, api keys
- `app/Console/Commands`
  - command queue maintenance, pruning, syncing stats
- `app/Jobs`
  - queue jobs cho xử lý task captcha bất đồng bộ
- `resources/js/pages`
  - page theo admin/client
- `resources/js/services`
  - wrapper API typed
- `resources/js/router/modules`
  - router admin/client

## Feature-First Conventions
Mỗi feature ưu tiên giữ cấu trúc:
- `Controllers`
- `Requests`
- `Services`
- `Actions`
- `Resources`
- `routes.php`

## Captcha Backend Modules

### `app/Features/Captcha`
- `Actions/ApiCaptchaAction`
  - điều phối create task API, validate ví và service
- `Services/CaptchaTaskService`
  - tạo task, route provider, xử lý sync/async response
- `Resources/*`
  - JSON response shape cho services, tasks, API docs

### Client Features
- `Client/Captcha`
  - danh sách dịch vụ, lịch sử task captcha, tài liệu API

### Admin Features
- `Admin/Captcha`
  - quản lý dịch vụ captcha, nguồn solve, giá gốc và giá bán
- `Admin/User`
  - xem thông tin user, ví, API key, lịch sử task captcha

## Core Data Flow
1. Vue page gọi service trong `resources/js/services`.
2. Service gọi API Laravel session-authenticated.
3. Controller dùng `FormRequest` validate input.
4. Service/Action xử lý nghiệp vụ.
5. Model ghi dữ liệu và `Resource` trả JSON thống nhất.

## Task Processing Flow
1. Client gọi `POST /api/v1/create`.
2. Controller validate payload theo loại captcha.
3. `CaptchaTaskService` chọn service và source phù hợp.
4. Provider service trong `App/Service/Captcha` gọi API bên thứ 3.
5. Nếu response có `captcha` ngay thì lưu task `solved`.
6. Nếu response trả mã task provider thì lưu `pending` để poll tiếp.
7. Queue có thể dùng cho refresh/truy vấn các task pending.
8. Stats dịch vụ công khai có thể cập nhật từ 100 task gần nhất.

## Pricing And Routing
- Mỗi `CaptchaService` có giá bán /1 lần giải.
- Mỗi `CaptchaSource` có giá gốc, driver, credentials và mã dịch vụ nhà cung cấp.
- Admin có thể bật/tắt source, điều chỉnh ưu tiên và định tuyến theo nhu cầu.
- Toàn bộ dữ liệu nguồn solve được ẩn ở client/public API.

## Queue / Supervisor Notes
- Production nên dùng Redis queue + Supervisor hoặc Horizon.
- Worker nên tách hàng đợi cho provider callback/polling nếu có.
- Có thể thêm retry/backoff cho call provider bên thứ 3.

## Security Boundary
- Credentials provider nên lưu an toàn và không trả về client.
- Payload create task cần validate theo từng loại captcha.
- Log response phải ẩn token/secret và dữ liệu nhạy cảm.
- API key user cần có quota/rate limit phù hợp.
