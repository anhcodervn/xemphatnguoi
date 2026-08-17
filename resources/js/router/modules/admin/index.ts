const placeholder = (title: string, description: string) => ({
    component: () => import('@/pages/admin/PlaceholderPage.vue'),
    props: { title, description },
});

export default {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    children: [
        { path: '', name: 'admin.dashboard', component: () => import('@/pages/admin/home/index.vue') },
        { path: 'lookup-logs', name: 'admin.lookup-logs', component: () => import('@/pages/admin/traffic-fines/LogsPage.vue') },
        { path: 'traffic-fine-results', name: 'admin.traffic-fine-results', component: () => import('@/pages/admin/traffic-fines/ResultsPage.vue') },
        { path: 'providers', name: 'admin.providers', component: () => import('@/pages/admin/traffic-fines/ProviderPage.vue') },
        { path: 'packages', redirect: { name: 'admin.api-billing' } },
        { path: 'subscriptions', redirect: { name: 'admin.api-billing' } },
        { path: 'api-billing', name: 'admin.api-billing', component: () => import('@/pages/admin/traffic-fines/BillingPage.vue') },
        {
            path: 'monitoring',
            name: 'admin.monitoring',
            ...placeholder('Monitoring', 'Quản lý lịch theo dõi biển số và scheduler sau khi phase monitoring được triển khai.'),
        },
        { path: 'ads', name: 'admin.ads', component: () => import('@/pages/admin/traffic-fines/AdSlotsPage.vue') },
        { path: 'support', name: 'admin.support.index', component: () => import('@/pages/admin/support/index.vue') },
        { path: 'api-usage', name: 'admin.api-usage', component: () => import('@/pages/admin/api-logs/index.vue') },
        { path: 'api-logs', redirect: { name: 'admin.api-usage' } },
        {
            path: 'users',
            children: [
                { path: '', name: 'admin.users.index', component: () => import('@/pages/admin/users/lists/index.vue') },
                { path: ':user_id(\\d+)', name: 'admin.users.show', component: () => import('@/pages/admin/users/info/index.vue') },
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
        { path: 'transactions', name: 'admin.transactions', component: () => import('@/pages/admin/users/wallet-transactions/index.vue') },
        {
            path: 'notifications',
            children: [
                { path: '', name: 'admin.notifications.index', component: () => import('@/pages/admin/notifications/list/index.vue') },
                { path: 'create', name: 'admin.notifications.create', component: () => import('@/pages/admin/notifications/form/index.vue') },
                {
                    path: ':notification_id(\\d+)/edit',
                    name: 'admin.notifications.edit',
                    component: () => import('@/pages/admin/notifications/form/index.vue'),
                },
                { path: 'history', name: 'admin.notifications.history', component: () => import('@/pages/admin/notifications/history/index.vue') },
            ],
        },
        { path: 'mail', name: 'admin.mail.index', component: () => import('@/pages/admin/mail/index.vue') },
        { path: 'queues', name: 'admin.queues.index', component: () => import('@/pages/admin/queues/index.vue') },
        { path: 'logs', redirect: { name: 'admin.queues.index' } },
        { path: 'feedbacks', name: 'admin.feedbacks.index', component: () => import('@/pages/admin/feedbacks/index.vue') },
        {
            path: 'seo',
            children: [
                { path: '', name: 'admin.seo.dashboard', component: () => import('@/pages/admin/seo/dashboard/index.vue') },
                { path: 'categories', name: 'admin.seo.categories', component: () => import('@/pages/admin/seo/categories/index.vue') },
                { path: 'posts', name: 'admin.seo.posts', component: () => import('@/pages/admin/seo/posts/index.vue') },
                { path: 'posts/create', name: 'admin.seo.posts.create', component: () => import('@/pages/admin/seo/posts/create/index.vue') },
                {
                    path: 'posts/:seo_post_id(\\d+)/edit',
                    name: 'admin.seo.posts.edit',
                    component: () => import('@/pages/admin/seo/posts/create/index.vue'),
                },
                { path: 'sitemaps', name: 'admin.seo.sitemaps', component: () => import('@/pages/admin/seo/sitemaps/index.vue') },
            ],
        },
        { path: 'blog', name: 'admin.blog', component: () => import('@/pages/admin/seo/posts/index.vue') },
        { path: 'blog/create', name: 'admin.blog.create', component: () => import('@/pages/admin/seo/posts/create/index.vue') },
        { path: 'blog/:seo_post_id(\\d+)/edit', name: 'admin.blog.edit', component: () => import('@/pages/admin/seo/posts/create/index.vue') },
        { path: 'recharge', redirect: { name: 'admin.recharge.config' } },
        { path: 'recharge/config', name: 'admin.recharge.config', component: () => import('@/pages/admin/settings/recharge/index.vue') },
        { path: 'recharge/history', name: 'admin.recharge.history', component: () => import('@/pages/admin/recharge/history/index.vue') },
        { path: 'setting', redirect: { name: 'admin.settings.general' } },
        { path: 'settings/general', name: 'admin.settings.general', component: () => import('@/pages/admin/settings/index.vue') },
        { path: 'settings/content', name: 'admin.settings.content', component: () => import('@/pages/admin/settings/content/index.vue') },
        { path: 'settings/recharge', redirect: { name: 'admin.recharge.config' } },
        { path: ':pathMatch(.*)*', name: 'admin.error.404', component: () => import('@/pages/errors/admin/NotFoundPage.vue') },
    ],
};
