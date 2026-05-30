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
            path: 'package',
            name: 'client.package',
            component: () => import('@/pages/client/package/index.vue'),
        },
        {
            path: 'bank-manager',
            name: 'client.bank-manager',
            component: () => import('@/pages/client/bank-manager/index.vue'),
        },
        {
            path: 'bank-manager/:bank_id',
            name: 'client.bank-manager.detail',
            component: () => import('@/pages/client/bank-manager/manager/index.vue'),
        },
        {
            path: 'bank-manager/bank',
            name: 'client.bank-manager.bank.create',
            component: () => import('@/pages/client/bank-manager/bank/index.vue'),
        },
        {
            path: 'bank-manager/bank/:bank_id',
            name: 'client.bank-manager.bank.edit',
            component: () => import('@/pages/client/bank-manager/bank/index.vue'),
        },
        {
            path: 'recharge',
            name: 'client.recharge',
            component: () => import('@/pages/client/recharge/index.vue'),
        },
        {
            path: 'recharge/payment/:recharge_id',
            name: 'client.recharge.payment',
            component: () => import('@/pages/client/recharge/payment/index.vue'),
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
            path: 'api-docs',
            name: 'client.api-docs',
            component: () => import('@/pages/client/api-docs/index.vue'),
        },
    ],
};
