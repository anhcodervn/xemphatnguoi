import { useUserStore } from '@/stores/user.store';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import adminRouter from './modules/admin';
import clientRouter from './modules/client';

const routes: RouteRecordRaw[] = [adminRouter, clientRouter];

const routeTitles: Record<string, string> = {
    'admin.dashboard': 'Tổng quan quản trị',
    'admin.lookup-logs': 'Lookup Logs',
    'admin.traffic-fine-results': 'Traffic Fine Results',
    'admin.providers': 'Providers',
    'admin.api-billing': 'Cấu hình giá API',
    'admin.transactions': 'Transactions',
    'admin.monitoring': 'Monitoring',
    'admin.api-usage': 'API Usage',
    'admin.blog': 'Blog',
    'admin.blog.create': 'Tạo bài viết',
    'admin.blog.edit': 'Cập nhật bài viết',
    'admin.ads': 'Ads',
    'admin.support.index': 'Tin nhắn hỗ trợ',
    'admin.users.index': 'Quản lý người dùng',
    'admin.users.show': 'Chi tiết người dùng',
    'admin.users.wallet-transaction': 'Biến động ví người dùng',
    'admin.users.wallet-transaction.show': 'Lịch sử ví người dùng',
    'admin.notifications.index': 'Thông báo hệ thống',
    'admin.notifications.create': 'Tạo thông báo',
    'admin.notifications.edit': 'Cập nhật thông báo',
    'admin.notifications.history': 'Lịch sử thông báo',
    'admin.mail.index': 'Gửi email',
    'admin.queues.index': 'Hàng đợi hệ thống',
    'admin.feedbacks.index': 'Liên hệ và góp ý',
    'admin.seo.dashboard': 'Quản trị SEO',
    'admin.seo.categories': 'Danh mục SEO',
    'admin.seo.posts': 'Bài viết SEO',
    'admin.seo.posts.create': 'Tạo bài viết SEO',
    'admin.seo.posts.edit': 'Cập nhật bài viết SEO',
    'admin.seo.sitemaps': 'Sitemap và index',
    'admin.settings.general': 'Cấu hình chung',
    'admin.settings.content': 'Cấu hình nội dung',
    'admin.settings.recharge': 'Cấu hình nạp tiền',
    'admin.recharge.config': 'Cấu hình nạp tiền',
    'admin.recharge.history': 'Lịch sử nạp tiền',
    'admin.error.404': 'Trang quản trị không tồn tại',
    'client.home': 'Tổng quan',
    'client.lookup-history': 'Lịch sử tra cứu',
    'client.vehicles': 'Xe của tôi',
    'client.monitoring': 'Theo dõi biển số',
    'client.api-usage': 'Lượt dùng API',
    'client.wallet': 'Ví và nạp tiền',
    'client.transactions': 'Giao dịch',
    'client.api-docs': 'Tài liệu API',
    'client.profile': 'Hồ sơ tài khoản',
    'client.contact': 'Liên hệ và góp ý',
    'client.support': 'Hỗ trợ trực tiếp',
    'client.error.404': 'Trang khách hàng không tồn tại',
};

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const routeName = typeof to.name === 'string' ? to.name : '';

    if (!routeName.startsWith('admin.')) {
        return true;
    }

    const userStore = useUserStore();
    const user = await userStore.bootstrap({ silent: true });

    if (!user) {
        return {
            path: '/login',
            query: {
                redirect: to.fullPath,
            },
        };
    }

    if (user.role !== 'admin') {
        return {
            path: '/dashboard',
        };
    }

    return true;
});

router.afterEach((to) => {
    const appElement = document.getElementById('app');
    const siteName = appElement?.dataset.siteName?.trim() || document.title || 'Laravel';
    const routeName = typeof to.name === 'string' ? to.name : '';
    const pageTitle = routeTitles[routeName];

    document.title = pageTitle ? `${pageTitle} | ${siteName}` : siteName;
});

router.onError((error, to) => {
    console.error(`Import component thất bại${to?.fullPath ? `: ${to.fullPath}` : ''}`, error);
});

export default router;
