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
            path: 'cron-jobs',
            name: 'client.cron-jobs',
            component: () => import('@/pages/client/cron-jobs/index.vue'),
        },
        {
            path: 'cron-jobs/create',
            name: 'client.cron-jobs.create',
            component: () => import('@/pages/client/cron-jobs/form/index.vue'),
        },
        {
            path: 'cron-jobs/:cron_job_id(\\d+)',
            name: 'client.cron-jobs.show',
            component: () => import('@/pages/client/cron-jobs/detail/index.vue'),
        },
        {
            path: 'cron-jobs/:cron_job_id(\\d+)/edit',
            name: 'client.cron-jobs.edit',
            component: () => import('@/pages/client/cron-jobs/form/index.vue'),
        },
        {
            path: 'logs',
            name: 'client.logs',
            component: () => import('@/pages/client/logs/index.vue'),
        },
        {
            path: 'alerts',
            name: 'client.alerts',
            component: () => import('@/pages/client/alerts/index.vue'),
        },
        {
            path: 'package',
            name: 'client.package',
            component: () => import('@/pages/client/package/index.vue'),
        },
        {
            path: 'wallet',
            name: 'client.wallet',
            component: () => import('@/pages/client/wallet/index.vue'),
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
