<template>
    <div class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <template v-for="(item, index) in resolvedItems" :key="`${item.label}-${index}`">
                <template v-if="index > 0">
                    <span class="mr-2 text-slate-300">/</span>
                </template>

                <router-link
                    v-if="item.to && !item.active"
                    :to="item.to"
                    class="transition-colors hover:text-blue-600"
                >
                    {{ item.label }}
                </router-link>

                <span v-else class="font-medium text-slate-900">
                    {{ item.label }}
                </span>
            </template>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    {{ resolvedTitle }}
                </h1>
                <p v-if="description" class="text-sm text-slate-500">
                    {{ description }}
                </p>
            </div>

            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

interface BreadcrumbItem {
    label: string;
    to?: string;
    active?: boolean;
}

const route = useRoute();

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items?: BreadcrumbItem[];
        homeLabel?: string;
    }>(),
    {
        title: '',
        description: '',
        items: () => [],
        homeLabel: 'Dashboard',
    },
);

const routeLabelMap: Record<string, string> = {
    'admin.dashboard': 'Dashboard',
    'admin.users.index': 'Người dùng',
    'admin.users.show': 'Chi tiết người dùng',
    'admin.users.wallet-transaction': 'Lịch sử dòng tiền',
    'admin.users.wallet-transaction.show': 'Lịch sử dòng tiền',
    'admin.packages.index': 'Gói thuê',
    'admin.packages.create': 'Tạo gói',
    'admin.packages.edit': 'Cập nhật gói',
    'admin.packages.orders': 'Gói đã bán',
    'admin.couponts.index': 'Mã giảm giá',
    'admin.couponts.create': 'Tạo mã giảm giá',
    'admin.couponts.edit': 'Cập nhật mã giảm giá',
    'admin.couponts.history': 'Lịch sử coupon',
    'admin.notifications.index': 'Thông báo',
    'admin.notifications.create': 'Tạo thông báo',
    'admin.notifications.edit': 'Cập nhật thông báo',
    'admin.notifications.history': 'Lịch sử thông báo',
    'admin.mail.index': 'Gửi mail',
    'admin.queues.index': 'Quản lý queue',
    'admin.webhooks.index': 'Quản lý webhook',
    'admin.feedbacks.index': 'Liên hệ và góp ý',
    'admin.recharge-methods.index': 'Phương thức nạp',
    'admin.recharge-methods.create': 'Tạo phương thức nạp',
    'admin.recharge-methods.edit': 'Cập nhật phương thức nạp',
    'admin.banks.index': 'Quản lý bank',
    'admin.banks.create': 'Thêm bank',
    'admin.banks.edit': 'Cập nhật bank',
    'admin.api-keys.index': 'Quản lý API key',
    'admin.api-logs.index': 'Quản lý API log',
    'admin.settings.system': 'Cấu hình hệ thống',
    'client.home': 'Trang chủ',
    'client.profile': 'Hồ sơ tài khoản',
    'client.recharge': 'Nạp tiền',
    'client.package': 'Nâng cấp gói',
    'client.contact': 'Liên hệ và góp ý',
    'client.api-docs': 'Tài liệu API',
};

const homeRoute = computed<string>(() => {
    const name = route.name?.toString() ?? '';

    if (name.startsWith('admin.')) {
        return '/admin';
    }

    return '/';
});

const autoItems = computed<BreadcrumbItem[]>(() => {
    const matchedRoutes = route.matched.filter((item) => item.name);

    const items = matchedRoutes.map((item, index) => {
        const name = item.name?.toString() || '';
        const label =
            routeLabelMap[name] ||
            item.meta?.title?.toString() ||
            name
                .split('.')
                .pop()
                ?.replace(/-/g, ' ')
                .replace(/\b\w/g, (char) => char.toUpperCase()) ||
            'Trang';

        return {
            label,
            to: item.path.includes(':') ? undefined : item.path,
            active: index === matchedRoutes.length - 1,
        };
    });

    return [
        {
            label: props.homeLabel,
            to: homeRoute.value,
            active: items.length === 0,
        },
        ...items,
    ];
});

const resolvedItems = computed(() => {
    if (props.items.length) {
        return props.items.map((item, index) => ({
            ...item,
            active: item.active ?? index === props.items.length - 1,
        }));
    }

    return autoItems.value;
});

const resolvedTitle = computed(() => {
    if (props.title) {
        return props.title;
    }

    return resolvedItems.value[resolvedItems.value.length - 1]?.label || props.homeLabel;
});

const description = computed(() => props.description);
</script>
