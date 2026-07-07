# PROJECT_BRIEF

## Product
- Product name: `GiaiCaptcha`
- Domain: SaaS cho dịch vụ `giải captcha qua API`
- Core value:
  - user nạp ví và sử dụng API key
  - tạo task captcha theo từng loại dịch vụ
  - hệ thống định tuyến task tới nguồn giải phù hợp
  - ghi log, trừ số dư, thống kê tỷ lệ thành công và tốc độ

## Confirmed Stack
- Backend: Laravel 12
- Frontend: Vue 3 + Vite + TypeScript
- Auth SPA: Sanctum + session/cookie + CSRF refresh
- UI: admin/client SPA layout có sẵn
- Tests: Pest

## Architecture Direction
- Giữ kiến trúc `feature-first` trong `app/Features`
- Tái sử dụng:
  - auth
  - admin/client layout
  - package
  - subscription
  - wallet
- Domain cũ liên quan API Bank và nền tảng cũ được loại khỏi route/UI chính, thay bằng domain captcha SaaS

## Main Business Domains
- `CaptchaService`
  - dịch vụ captcha công khai, icon, giá bán, tốc độ, tỷ lệ thành công
- `CaptchaSource`
  - nguồn solve bên thứ 3, driver, credentials, giá gốc, cấu hình routing
- `CaptchaTask`
  - task giải captcha, payload, status, provider response, kết quả
- `ApiKey`
  - khóa truy cập API của user
- `Wallet`
  - số dư, nạp tiền, chi phí task captcha

## Task And Queue Flow
1. Client gọi `POST /api/v1/create` để tạo task captcha.
2. Hệ thống validate service, API key, số dư và payload.
3. Task được route tới `CaptchaSource` phù hợp theo driver.
4. Nếu provider trả kết quả ngay thì task chuyển `solved`.
5. Nếu provider xử lý bất đồng bộ thì task giữ `pending` để poll.
6. Client gọi API check task để lấy kết quả và sử dụng.

## Security
- Có validation payload và provider credentials:
  - validate theo từng loại captcha
  - ẩn toàn bộ thông tin nguồn solve ở client
  - giảm thiểu lộ token/credential bên thứ 3
  - log response an toàn, không expose credentials

## Verification Commands
1. `php artisan route:list`
2. `php artisan test --compact`
3. `npm run build`
4. `npx eslint .`

## Current Refactor Status
- Đã chuyển trục nghiệp vụ chính sang captcha SaaS.
- Đã thay route và menu chính sang services, tasks, api docs, wallet.
- Vẫn còn một số file docs/test mẫu cần được giữ đồng bộ với thương hiệu mới.
