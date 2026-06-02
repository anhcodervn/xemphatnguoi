import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import adminRouter from "./modules/admin";
import clientRouter from "./modules/client";
import { useUserStore } from "@/stores/user.store";

const routes: RouteRecordRaw[] = [adminRouter, clientRouter];

const routeTitles: Record<string, string> = {
    "admin.dashboard": "Tổng quan quản trị",
    "admin.users.index": "Quản lý người dùng",
    "admin.users.show": "Chi tiết người dùng",
    "admin.users.wallet-transaction": "Biến động số dư người dùng",
    "admin.users.wallet-transaction.show": "Lịch sử ví người dùng",
    "admin.packages.index": "Quản lý gói dịch vụ",
    "admin.packages.create": "Thêm gói dịch vụ",
    "admin.packages.edit": "Cập nhật gói dịch vụ",
    "admin.packages.orders": "Đơn hàng gói dịch vụ",
    "admin.couponts.index": "Quản lý mã giảm giá",
    "admin.couponts.create": "Tạo mã giảm giá",
    "admin.couponts.edit": "Cập nhật mã giảm giá",
    "admin.couponts.history": "Lịch sử mã giảm giá",
    "admin.notifications.index": "Thông báo hệ thống",
    "admin.notifications.create": "Tạo thông báo",
    "admin.notifications.edit": "Cập nhật thông báo",
    "admin.notifications.history": "Lịch sử thông báo",
    "admin.mail.index": "Gửi email",
    "admin.queues.index": "Hàng đợi hệ thống",
    "admin.webhooks.index": "Webhook hệ thống",
    "admin.feedbacks.index": "Liên hệ và góp ý",
    "admin.recharge-methods.index": "Quản lý nạp tiền",
    "admin.recharge-methods.create": "Thêm tài khoản nhận tiền",
    "admin.recharge-methods.edit": "Cập nhật tài khoản nhận tiền",
    "admin.banks.index": "Quản lý ngân hàng",
    "admin.banks.create": "Thêm ngân hàng",
    "admin.banks.edit": "Cập nhật ngân hàng",
    "admin.api-keys.index": "Quản lý API key",
    "admin.api-logs.index": "Quản lý API log",
    "admin.seo.dashboard": "Quản trị SEO",
    "admin.seo.categories": "Danh mục SEO",
    "admin.seo.posts": "Bài viết SEO",
    "admin.seo.posts.create": "Tạo bài viết SEO",
    "admin.seo.posts.edit": "Cập nhật bài viết SEO",
    "admin.seo.sitemaps": "Sitemap & index",
    "admin.settings.general": "Cấu hình chung",
    "admin.settings.content": "Cấu hình điều khoản",
    "admin.error.404": "Trang quản trị không tồn tại",
    "client.home": "Tổng quan",
    "client.package": "Gói dịch vụ",
    "client.bank-manager": "Quản lý thẻ",
    "client.bank-manager.detail": "Chi tiết thẻ",
    "client.bank-manager.bank.create": "Thêm thẻ ngân hàng",
    "client.bank-manager.bank.edit": "Cập nhật thẻ ngân hàng",
    "client.recharge": "Nạp tiền",
    "client.recharge.payment": "Thanh toán nạp tiền",
    "client.profile": "Hồ sơ tài khoản",
    "client.contact": "Liên hệ và góp ý",
    "client.api-docs": "Tài liệu API",
    "client.error.404": "Trang khách hàng không tồn tại",
};

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const routeName = typeof to.name === "string" ? to.name : "";

    if (!routeName.startsWith("admin.")) {
        return true;
    }

    const userStore = useUserStore();
    const user = await userStore.bootstrap({ silent: true });

    if (!user) {
        return {
            path: "/login",
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (user.role !== "admin") {
        return {
            path: "/",
        };
    }

    return true;
});

router.afterEach((to) => {
    const appElement = document.getElementById("app");
    const siteName =
        appElement?.dataset.siteName?.trim() || document.title || "Laravel";
    const routeName = typeof to.name === "string" ? to.name : "";
    const pageTitle = routeTitles[routeName];

    document.title = pageTitle ? `${pageTitle} | ${siteName}` : siteName;
});

router.onError((error, to) => {
    console.error(
        `Import component thất bại${to?.fullPath ? `: ${to.fullPath}` : ""}`,
        error,
    );
});

export default router;
