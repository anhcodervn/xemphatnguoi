import {
    BookOpen,
    Cable,
    ChartNoAxesCombined,
    CircleDollarSign,
    Database,
    Eye,
    FileClock,
    LayoutDashboard,
    Logs,
    ReceiptText,
    Settings,
    UserRound,
    Users,
    WalletCards,
    type LucideIcon,
} from 'lucide-vue-next';

export type AdminMenuChild = { label: string; href: string };
export type AdminMenuGroup = { key: string; label: string; icon: LucideIcon; href?: string; children?: AdminMenuChild[]; badge?: 'support' };

export const adminMenuGroups: AdminMenuGroup[] = [
    { key: 'dashboard', label: 'Dashboard', icon: LayoutDashboard, href: '/admin' },
    { key: 'users', label: 'Users', icon: Users, href: '/admin/users' },
    { key: 'lookup-logs', label: 'Lookup Logs', icon: FileClock, href: '/admin/lookup-logs' },
    { key: 'traffic-results', label: 'Traffic Fine Results', icon: Database, href: '/admin/traffic-fine-results' },
    { key: 'providers', label: 'Providers', icon: Cable, href: '/admin/providers' },
    { key: 'api-billing', label: 'API Billing', icon: CircleDollarSign, href: '/admin/api-billing' },
    { key: 'transactions', label: 'Transactions', icon: WalletCards, href: '/admin/transactions' },
    {
        key: 'recharge',
        label: 'Quản lý nạp tiền',
        icon: WalletCards,
        children: [
            { label: 'Cấu hình nạp tiền', href: '/admin/recharge/config' },
            { label: 'Lịch sử nạp tiền', href: '/admin/recharge/history' },
        ],
    },
    { key: 'monitoring', label: 'Monitoring', icon: Eye, href: '/admin/monitoring' },
    { key: 'api-usage', label: 'API Usage', icon: ChartNoAxesCombined, href: '/admin/api-usage' },
    { key: 'blog', label: 'Blog', icon: BookOpen, href: '/admin/blog' },
    { key: 'seo', label: 'SEO', icon: ReceiptText, href: '/admin/seo' },
    { key: 'ads', label: 'Ads', icon: ChartNoAxesCombined, href: '/admin/ads' },
    { key: 'settings', label: 'Settings', icon: Settings, href: '/admin/settings/general' },
    { key: 'logs', label: 'Logs', icon: Logs, href: '/admin/logs' },
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
