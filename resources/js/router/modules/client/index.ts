export default {
    path: '',
    component: () => import('@/layouts/ClientLayout.vue'),
    children: [
        {
            path: '',
            name: 'client.home',
            component: () => import('@/pages/client/home/index.vue'),
        },
        {
            path: 'wallet',
            name: 'client.wallet',
            component: () => import('@/pages/client/wallet/index.vue'),
        },
        {
            path: 'services',
            name: 'client.services',
            component: () => import('@/pages/client/services/index.vue'),
        },
        {
            path: 'packages',
            name: 'client.packages',
            component: () => import('@/pages/client/packages/index.vue'),
        },
        {
            path: 'captcha-history',
            name: 'client.captcha-history',
            component: () => import('@/pages/client/captcha-history/index.vue'),
        },
        {
            path: 'api-docs',
            name: 'client.api-docs',
            component: () => import('@/pages/client/api-docs/index.vue'),
        },
        {
            path: 'profile',
            name: 'client.profile',
            component: () => import('@/pages/client/profile/index.vue'),
        },
        {
            path: 'contact',
            name: 'client.contact',
            component: () => import('@/pages/client/contact/index.vue'),
        },
        {
            path: ':pathMatch(.*)*',
            name: 'client.error.404',
            component: () => import('@/pages/errors/client/NotFoundPage.vue'),
        },
    ],
};
