import {
    KeyRound,
    Landmark,
    LayoutDashboard,
    ListChecks,
    Mail,
    Package,
    Settings,
    Users,
    Webhook,
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
};

export const adminMenuGroups: AdminMenuGroup[] = [
    {
        key: 'dashboard',
        label: 'Dashboard',
        icon: LayoutDashboard,
        href: '/admin',
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
        label: 'Quản lý thông báo',
        icon: Users,
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
        key: 'packages',
        label: 'Quản lý gói thuê',
        icon: Package,
        children: [
            {
                label: 'Tạo gói mới',
                href: '/admin/packages/create',
            },
            {
                label: 'Danh sách gói',
                href: '/admin/packages',
            },
            {
                label: 'Gói đã cho thuê',
                href: '/admin/packages/orders',
            },
        ],
    },
    {
        key: 'recharge-methods',
        label: 'Quản lý nạp tiền',
        icon: Landmark,
        children: [
            {
                label: 'Tạo phương thức',
                href: '/admin/recharge-methods/create',
            },
            {
                label: 'Phương thức nạp',
                href: '/admin/recharge-methods',
            },
        ],
    },
    {
        key: 'api-management',
        label: 'Quản lý API',
        icon: KeyRound,
        children: [
            {
                label: 'Thêm bank',
                href: '/admin/banks/create',
            },
            {
                label: 'Quản lý bank',
                href: '/admin/banks',
            },
            {
                label: 'Quản lý API key',
                href: '/admin/api-keys',
            },
            {
                label: 'Quản lý API log',
                href: '/admin/api-logs',
            },
        ],
    },
    {
        key: 'coupons',
        label: 'Mã giảm giá',
        icon: Landmark,
        children: [
            {
                label: 'Tạo mã giảm giá',
                href: '/admin/couponts/create',
            },
            {
                label: 'Danh sách coupon',
                href: '/admin/couponts',
            },
            {
                label: 'Lịch sử coupon',
                href: '/admin/couponts/history',
            },
        ],
    },
    {
        key: 'settings',
        label: 'Cấu hình hệ thống',
        icon: Settings,
        href: '/admin/settings',
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
    {
        key: 'webhooks',
        label: 'Quản lý webhook',
        icon: Webhook,
        href: '/admin/webhooks',
    },
    {
        key: 'feedbacks',
        label: 'Liên hệ & góp ý',
        icon: Mail,
        href: '/admin/feedbacks',
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
