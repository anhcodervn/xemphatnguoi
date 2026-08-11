import {
    BarChart3,
    BellRing,
    BookMarked,
    Database,
    LayoutDashboard,
    ListChecks,
    Mail,
    MessagesSquare,
    Settings,
    Users,
    WalletCards,
    type LucideIcon,
} from 'lucide-vue-next';

export type AdminMenuChild = {
    label: string;
    href: string;
};

export type AdminMenuGroup = {
    key: string;
    label: string;
    icon: LucideIcon;
    href?: string;
    children?: AdminMenuChild[];
    badge?: 'support';
};

export const adminMenuGroups: AdminMenuGroup[] = [
    {
        key: 'dashboard',
        label: 'Dashboard',
        icon: LayoutDashboard,
        href: '/admin',
    },
    {
        key: 'analytics',
        label: 'Báo cáo vận hành',
        icon: BarChart3,
        href: '/admin/analytics',
    },
    {
        key: 'support',
        label: 'Tin nhắn hỗ trợ',
        icon: MessagesSquare,
        href: '/admin/support',
        badge: 'support',
    },
    {
        key: 'proxy',
        label: 'Kho proxy',
        icon: Database,
        children: [
            {
                label: 'Chuyên mục proxy',
                href: '/admin/proxy-taxonomy',
            },
            {
                label: 'Nhà cung cấp',
                href: '/admin/proxy-providers',
            },
            {
                label: 'Sản phẩm proxy',
                href: '/admin/proxy-products',
            },
            {
                label: 'API log',
                href: '/admin/api-logs',
            },
        ],
    },
    {
        key: 'recharge',
        label: 'Quản lý nạp tiền',
        icon: WalletCards,
        children: [
            {
                label: 'Cấu hình nạp tiền',
                href: '/admin/recharge/config',
            },
            {
                label: 'Lịch sử nạp tiền',
                href: '/admin/recharge/history',
            },
        ],
    },
    {
        key: 'users',
        label: 'Quản lý người dùng',
        icon: Users,
        children: [
            {
                label: 'Danh sách thành viên',
                href: '/admin/users',
            },
            {
                label: 'Lịch sử dòng tiền',
                href: '/admin/users/wallet-transactions',
            },
        ],
    },
    {
        key: 'notifications',
        label: 'Thông báo hệ thống',
        icon: BellRing,
        children: [
            {
                label: 'Tạo thông báo mới',
                href: '/admin/notifications/create',
            },
            {
                label: 'Danh sách thông báo',
                href: '/admin/notifications',
            },
        ],
    },
    {
        key: 'seo-management',
        label: 'Quản trị SEO',
        icon: BookMarked,
        children: [
            {
                label: 'Tổng quan SEO',
                href: '/admin/seo',
            },
            {
                label: 'Danh mục SEO',
                href: '/admin/seo/categories',
            },
            {
                label: 'Bài viết SEO',
                href: '/admin/seo/posts',
            },
            {
                label: 'Tạo bài viết',
                href: '/admin/seo/posts/create',
            },
            {
                label: 'Sitemap & index',
                href: '/admin/seo/sitemaps',
            },
        ],
    },
    {
        key: 'settings',
        label: 'Cấu hình hệ thống',
        icon: Settings,
        children: [
            {
                label: 'Cấu hình chung',
                href: '/admin/settings/general',
            },
            {
                label: 'Cấu hình nội dung',
                href: '/admin/settings/content',
            },
        ],
    },
    {
        key: 'mail',
        label: 'Gửi email',
        icon: Mail,
        href: '/admin/mail',
    },
    {
        key: 'queues',
        label: 'Quản lý queue',
        icon: ListChecks,
        href: '/admin/queues',
    },
];

export const resolveAdminPageTitle = (path: string): string => {
    for (const group of adminMenuGroups) {
        if (group.href && (path === group.href || path.startsWith(`${group.href}/`))) {
            return group.label;
        }

        const matchedChild = group.children
            ?.slice()
            .sort((left, right) => right.href.length - left.href.length)
            .find((child) => path === child.href || path.startsWith(`${child.href}/`));

        if (matchedChild) {
            return matchedChild.label;
        }
    }

    return 'Admin';
};
