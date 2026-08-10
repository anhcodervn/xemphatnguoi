# ARCHITECTURE

## Proxy module

```text
Admin
    -> quản lý category
    -> quản lý provider và credential mã hóa
    -> quản lý product, giao thức và giá theo ngày

Client / reseller API
    -> ProxyCatalogService
    -> category + product đang hoạt động
```

Hiện tại hệ thống chỉ cung cấp catalog proxy; luồng đặt mua và thực thi provider sẽ được xây dựng lại theo từng bước.

## API hiện có

- SPA: `GET /api/client/proxy/products`
- Reseller: `GET /api/v1/proxy/products` với quyền `proxy-products.read`

## Provider security

- Credential được mã hóa trong model `ProxyProvider`.
- Resource công khai không trả provider hoặc dữ liệu kết nối.
- Resource admin chỉ hiển thị credential tại màn sửa được bảo vệ.
