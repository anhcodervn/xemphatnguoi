import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import adminRouter from "./modules/admin";
import clientRouter from "./modules/client";
import { useUserStore } from "@/stores/user.store";

const routes: RouteRecordRaw[] = [adminRouter, clientRouter];

const routeTitles: Record<string, string> = {
    "admin.dashboard": "Tổng quan quản trị",
    "admin.users.index": "Quản lý người dùng",
    "admin.users.show": "Chi tiết người dùng",
    "admin.users.wallet-transaction": "Biến động ví người dùng",
    "admin.users.wallet-transaction.show": "Lịch sử ví người dùng",
    "admin.cron-jobs.index": "Quản lý Cron Jobs",
    "admin.system-logs.index": "Nhật ký hệ thống",
    "admin.packages.index": "Quản lý gói dịch vụ",
    "admin.packages.create": "Thêm gói dịch vụ",
    "admin.packages.edit": "Cập nhật gói dịch vụ",
    "admin.packages.orders": "Đơn hàng gói dịch vụ",
    "admin.notifications.index": "Thông báo hệ thống",
    "admin.notifications.create": "Tạo thông báo",
    "admin.notifications.edit": "Cập nhật thông báo",
    "admin.notifications.history": "Lịch sử thông báo",
    "admin.mail.index": "Gửi email",
    "admin.queues.index": "Hàng đợi hệ thống",
    "admin.feedbacks.index": "Liên hệ và góp ý",
    "admin.couponts.index": "Mã giảm giá",
    "admin.couponts.create": "Tạo mã giảm giá",
    "admin.couponts.edit": "Cập nhật mã giảm giá",
    "admin.couponts.history": "Lịch sử mã giảm giá",
    "admin.seo.dashboard": "Quản trị SEO",
    "admin.seo.categories": "Danh mục SEO",
    "admin.seo.posts": "Bài viết SEO",
    "admin.seo.posts.create": "Tạo bài viết SEO",
    "admin.seo.posts.edit": "Cập nhật bài viết SEO",
    "admin.seo.sitemaps": "Sitemap & index",
    "admin.settings.general": "Cấu hình chung",
    "admin.settings.content": "Cấu hình nội dung",
    "admin.settings.recharge": "Cấu hình nạp tiền",
    "admin.recharge.config": "Cấu hình nạp tiền",
    "admin.recharge.history": "Lịch sử nạp tiền",
    "admin.error.404": "Trang quản trị không tồn tại",
    "client.home": "Tổng quan",
    "client.cron-jobs": "Cron Jobs",
    "client.cron-jobs.create": "Tạo Cron Job",
    "client.cron-jobs.show": "Chi tiết Cron Job",
    "client.cron-jobs.edit": "Cập nhật Cron Job",
    "client.logs": "Nhật ký chạy",
    "client.alerts": "Kênh cảnh báo",
    "client.package": "Gói dịch vụ",
    "client.wallet": "Ví và nạp tiền",
    "client.profile": "Hồ sơ tài khoản",
    "client.contact": "Liên hệ và góp ý",
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
    const siteName = appElement?.dataset.siteName?.trim() || document.title || "Laravel";
    const routeName = typeof to.name === "string" ? to.name : "";
    const pageTitle = routeTitles[routeName];

    document.title = pageTitle ? `${pageTitle} | ${siteName}` : siteName;
});

router.onError((error, to) => {
    console.error(`Import component thất bại${to?.fullPath ? `: ${to.fullPath}` : ""}`, error);
});

export default router;
