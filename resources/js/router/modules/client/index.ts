export default {
    path: '/dashboard',
    component: () => import('@/layouts/ClientLayout.vue'),
    children: [
        {
            path: '',
            name: 'client.home',
            component: () => import('@/pages/client/home/index.vue'),
        },
        {
            path: 'history',
            name: 'client.lookup-history',
            component: () => import('@/pages/client/lookup-history/index.vue'),
        },
        {
            path: 'vehicles',
            name: 'client.vehicles',
            component: () => import('@/pages/client/vehicles/index.vue'),
        },
        {
            path: 'monitoring',
            name: 'client.monitoring',
            component: () => import('@/pages/client/monitoring/index.vue'),
        },
        { path: 'packages', redirect: { name: 'client.api-docs' } },
        {
            path: 'api',
            name: 'client.api-docs',
            component: () => import('@/pages/client/api-docs/index.vue'),
        },
        {
            path: 'api-usage',
            name: 'client.api-usage',
            component: () => import('@/pages/client/api-usage/index.vue'),
        },
        {
            path: 'wallet',
            name: 'client.wallet',
            component: () => import('@/pages/client/wallet/index.vue'),
        },
        {
            path: 'transactions',
            name: 'client.transactions',
            component: () => import('@/pages/client/transactions/index.vue'),
        },
        {
            path: 'account',
            name: 'client.profile',
            component: () => import('@/pages/client/profile/index.vue'),
        },
        {
            path: 'contact',
            name: 'client.contact',
            component: () => import('@/pages/client/contact/index.vue'),
        },
        {
            path: 'support',
            name: 'client.support',
            component: () => import('@/pages/client/support/index.vue'),
        },
        {
            path: ':pathMatch(.*)*',
            name: 'client.error.404',
            component: () => import('@/pages/errors/client/NotFoundPage.vue'),
        },
    ],
};
