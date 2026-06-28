export default {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    children: [
        {
            path: '',
            name: 'admin.dashboard',
            component: () => import('@/pages/admin/home/index.vue'),
        },
        {
            path: 'users',
            children: [
                {
                    path: '',
                    name: 'admin.users.index',
                    component: () => import('@/pages/admin/users/lists/index.vue'),
                },
                {
                    path: ':user_id(\\d+)',
                    name: 'admin.users.show',
                    component: () => import('@/pages/admin/users/info/index.vue'),
                },
                {
                    path: 'wallet-transactions',
                    name: 'admin.users.wallet-transaction',
                    component: () => import('@/pages/admin/users/wallet-transactions/index.vue'),
                },
                {
                    path: 'wallet-transactions/:user_id(\\d+)',
                    name: 'admin.users.wallet-transaction.show',
                    component: () => import('@/pages/admin/users/wallet-transactions/index.vue'),
                },
            ],
        },
        {
            path: 'cron-jobs',
            name: 'admin.cron-jobs.index',
            component: () => import('@/pages/admin/cron-jobs/index.vue'),
        },
        {
            path: 'system-logs',
            name: 'admin.system-logs.index',
            component: () => import('@/pages/admin/system-logs/index.vue'),
        },
        {
            path: 'packages',
            children: [
                {
                    path: '',
                    name: 'admin.packages.index',
                    component: () => import('@/pages/admin/package/managers/list/index.vue'),
                },
                {
                    path: 'create',
                    name: 'admin.packages.create',
                    component: () => import('@/pages/admin/package/managers/create/index.vue'),
                },
                {
                    path: ':package_id(\\d+)/edit',
                    name: 'admin.packages.edit',
                    component: () => import('@/pages/admin/package/managers/create/index.vue'),
                },
                {
                    path: 'orders',
                    name: 'admin.packages.orders',
                    component: () => import('@/pages/admin/package/orders/index.vue'),
                },
            ],
        },
        {
            path: 'notifications',
            children: [
                {
                    path: '',
                    name: 'admin.notifications.index',
                    component: () => import('@/pages/admin/notifications/list/index.vue'),
                },
                {
                    path: 'create',
                    name: 'admin.notifications.create',
                    component: () => import('@/pages/admin/notifications/form/index.vue'),
                },
                {
                    path: ':notification_id(\\d+)/edit',
                    name: 'admin.notifications.edit',
                    component: () => import('@/pages/admin/notifications/form/index.vue'),
                },
                {
                    path: 'history',
                    name: 'admin.notifications.history',
                    component: () => import('@/pages/admin/notifications/history/index.vue'),
                },
            ],
        },
        {
            path: 'mail',
            name: 'admin.mail.index',
            component: () => import('@/pages/admin/mail/index.vue'),
        },
        {
            path: 'queues',
            name: 'admin.queues.index',
            component: () => import('@/pages/admin/queues/index.vue'),
        },
        {
            path: 'feedbacks',
            name: 'admin.feedbacks.index',
            component: () => import('@/pages/admin/feedbacks/index.vue'),
        },
        {
            path: 'couponts',
            children: [
                {
                    path: '',
                    name: 'admin.couponts.index',
                    component: () => import('@/pages/admin/couponts/list/index.vue'),
                },
                {
                    path: 'create',
                    name: 'admin.couponts.create',
                    component: () => import('@/pages/admin/couponts/create/index.vue'),
                },
                {
                    path: ':coupont_id(\\d+)/edit',
                    name: 'admin.couponts.edit',
                    component: () => import('@/pages/admin/couponts/create/index.vue'),
                },
                {
                    path: 'history',
                    name: 'admin.couponts.history',
                    component: () => import('@/pages/admin/couponts/history/index.vue'),
                },
            ],
        },
        {
            path: 'seo',
            children: [
                {
                    path: '',
                    name: 'admin.seo.dashboard',
                    component: () => import('@/pages/admin/seo/dashboard/index.vue'),
                },
                {
                    path: 'categories',
                    name: 'admin.seo.categories',
                    component: () => import('@/pages/admin/seo/categories/index.vue'),
                },
                {
                    path: 'posts',
                    name: 'admin.seo.posts',
                    component: () => import('@/pages/admin/seo/posts/index.vue'),
                },
                {
                    path: 'posts/create',
                    name: 'admin.seo.posts.create',
                    component: () => import('@/pages/admin/seo/posts/create/index.vue'),
                },
                {
                    path: 'posts/:seo_post_id(\\d+)/edit',
                    name: 'admin.seo.posts.edit',
                    component: () => import('@/pages/admin/seo/posts/create/index.vue'),
                },
                {
                    path: 'sitemaps',
                    name: 'admin.seo.sitemaps',
                    component: () => import('@/pages/admin/seo/sitemaps/index.vue'),
                },
            ],
        },
        {
            path: 'recharge',
            redirect: { name: 'admin.recharge.config' },
        },
        {
            path: 'recharge/config',
            name: 'admin.recharge.config',
            component: () => import('@/pages/admin/settings/recharge/index.vue'),
        },
        {
            path: 'recharge/history',
            name: 'admin.recharge.history',
            component: () => import('@/pages/admin/recharge/history/index.vue'),
        },
        {
            path: 'setting',
            redirect: { name: 'admin.settings.general' },
        },
        {
            path: 'settings/general',
            name: 'admin.settings.general',
            component: () => import('@/pages/admin/settings/index.vue'),
        },
        {
            path: 'settings/content',
            name: 'admin.settings.content',
            component: () => import('@/pages/admin/settings/content/index.vue'),
        },
        {
            path: 'settings/recharge',
            redirect: { name: 'admin.recharge.config' },
        },
        {
            path: ':pathMatch(.*)*',
            name: 'admin.error.404',
            component: () => import('@/pages/errors/admin/NotFoundPage.vue'),
        },
    ],
};
