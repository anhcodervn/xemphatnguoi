import { BookOpen, Database, LayoutDashboard, Settings, UserRound, Users, type LucideIcon } from 'lucide-vue-next';

export type AdminMenuChild = { label: string; href: string };
export type AdminMenuGroup = { key: string; label: string; icon: LucideIcon; href?: string; children?: AdminMenuChild[] };

export const adminMenuGroups: AdminMenuGroup[] = [
    { key: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/admin' },
    {
        key: 'lookup-data',
        label: 'Tra cứu & dữ liệu',
        icon: Database,
        children: [
            { label: 'Báo cáo chi tiết', href: '/admin/reports' },
            { label: 'Nhật ký tra cứu', href: '/admin/lookup-logs' },
            { label: 'Cache biển số', href: '/admin/traffic-fine-results' },
            { label: 'Nguồn dữ liệu', href: '/admin/providers' },
            { label: 'Theo dõi biển số', href: '/admin/monitoring' },
        ],
    },
    {
        key: 'users-finance',
        label: 'Người dùng & tài chính',
        icon: Users,
        children: [
            { label: 'Người dùng', href: '/admin/users' },
            { label: 'Thanh toán API', href: '/admin/api-billing' },
            { label: 'Giao dịch ví', href: '/admin/transactions' },
            { label: 'Cấu hình nạp tiền', href: '/admin/recharge/config' },
            { label: 'Lịch sử nạp tiền', href: '/admin/recharge/history' },
        ],
    },
    {
        key: 'content-growth',
        label: 'Nội dung',
        icon: BookOpen,
        children: [
            { label: 'Bài viết', href: '/admin/articles' },
            { label: 'Danh mục', href: '/admin/categories' },
            { label: 'Tags', href: '/admin/tags' },
            { label: 'SEO', href: '/admin/seo' },
            { label: 'Quảng cáo', href: '/admin/ads' },
        ],
    },
    {
        key: 'system-operations',
        label: 'Hệ thống & vận hành',
        icon: Settings,
        children: [
            { label: 'Lượt dùng API', href: '/admin/api-usage' },
            { label: 'Cài đặt hệ thống', href: '/admin/settings/general' },
            { label: 'Queue & nhật ký', href: '/admin/logs' },
        ],
    },
    { key: 'profile', label: 'Tài khoản admin', icon: UserRound, href: '/dashboard/account' },
];

export const resolveAdminPageTitle = (path: string): string => {
    const menuItems = adminMenuGroups.flatMap((group) => [
        ...(group.href ? [{ label: group.label, href: group.href }] : []),
        ...(group.children ?? []),
    ]);
    const matchedItem = menuItems
        .sort((left, right) => right.href.length - left.href.length)
        .find((item) => path === item.href || path.startsWith(`${item.href}/`));

    return matchedItem?.label ?? 'Admin';
};
