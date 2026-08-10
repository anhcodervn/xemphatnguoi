# PROJECT_BRIEF

## Product

- Product name: `DailyProxy.vn`.
- Website trung gian quản lý và niêm yết sản phẩm proxy từ các nhà cung cấp.
- Credential kết nối nhà cung cấp chỉ nằm ở backend.

## Stack

- Laravel 12, Vue 3, Vite, TypeScript, Tailwind CSS 3.
- Sanctum cho SPA; API key/secret cho reseller API.
- Pest cho backend tests.

## Proxy catalog hiện tại

- `ProxyCategory`: nhóm sản phẩm proxy.
- `ProxyProvider`: tên, code, phương thức thủ công/tự động và dữ liệu kết nối được mã hóa.
- `ProxyProduct`: sản phẩm thuộc category, gắn provider, giao thức hỗ trợ và giá bán theo proxy/ngày.
- Trang Services và API `GET /api/v1/proxy/products` chỉ đọc catalog.

## Security boundary

- Credential provider được mã hóa và không trả ra resource công khai.
- Client chỉ thấy thương hiệu DailyProxy và dữ liệu catalog công khai.
- Không log credential, token hoặc raw response nhạy cảm từ provider.
