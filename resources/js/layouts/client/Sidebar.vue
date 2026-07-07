<script setup lang="ts">
import { useSystemSetting } from '@/composables/useSystemSetting';
import {
    CircleUserRound,
    Code2,
    Gift,
    History,
    LayoutDashboard,
    Layers3,
    MessageSquareMore,
    Package,
    Wallet,
    X,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

defineProps<{
    isOpen: boolean;
}>();

defineEmits<{
    close: [];
}>();

type NavItem = {
    label: string;
    icon: LucideIcon;
    href: string;
};

const route = useRoute();
const { settings, fetchSettings } = useSystemSetting();

const items: NavItem[] = [
    { label: 'Tổng quan', icon: LayoutDashboard, href: '/' },
    { label: 'Hồ sơ tài khoản', icon: CircleUserRound, href: '/profile' },
    { label: 'Ví và nạp tiền', icon: Wallet, href: '/wallet' },
    { label: 'Dịch vụ captcha', icon: Layers3, href: '/services' },
    { label: 'Gói captcha', icon: Package, href: '/packages' },
    { label: 'Lịch sử giải captcha', icon: History, href: '/captcha-history' },
    { label: 'Tài liệu API', icon: Code2, href: '/api-docs' },
    { label: 'Liên hệ và góp ý', icon: MessageSquareMore, href: '/contact' },
];

const isActive = (href: string): boolean => {
    if (href === '/') {
        return route.path === href;
    }

    return route.path === href || route.path.startsWith(`${href}/`);
};

const siteLogo = computed(() => settings.value.light_logo || false);
const siteName = computed(() => settings.value.site_name || 'GiaiCaptcha.vn');
const siteDescription = computed(() => settings.value.site_description || 'Captcha Solving API');
const supportLine = computed(() => settings.value.support_email || settings.value.hotline || '');

onMounted(async () => {
    await fetchSettings();
});
</script>

<template>
    <Teleport to="body">
        <button v-if="isOpen" type="button" class="fixed inset-0 z-40 bg-slate-950/30 lg:hidden" @click="$emit('close')" />
    </Teleport>

    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-200 bg-white text-slate-900 transition duration-200 lg:relative lg:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5">
                <RouterLink v-if="!siteLogo" to="/" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white">
                        G
                    </div>
                    <div>
                        <p class="text-base font-semibold tracking-tight text-slate-950">{{ siteName }}</p>
                        <p class="line-clamp-1 text-sm text-slate-500">{{ siteDescription }}</p>
                    </div>
                </RouterLink>

                <RouterLink v-else to="/" class="flex items-center gap-3">
                    <img :src="siteLogo || ''" alt="logo website" class="max-h-11 object-contain" />
                </RouterLink>

                <button type="button" class="rounded-xl border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 lg:hidden" @click="$emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <nav class="flex-1 px-3 py-5">
                <ul class="space-y-1.5">
                    <li v-for="item in items" :key="item.label">
                        <RouterLink
                            :to="item.href"
                            class="group flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium transition"
                            :class="
                                isActive(item.href)
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                            "
                            @click="$emit('close')"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg transition"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-white text-blue-700'
                                        : 'bg-slate-100 text-slate-500 group-hover:bg-white group-hover:text-slate-700'
                                "
                            >
                                <component :is="item.icon" class="h-4.5 w-4.5" />
                            </div>
                            <span class="flex-1">{{ item.label }}</span>
                        </RouterLink>
                    </li>
                </ul>
            </nav>

            <!-- <div class="px-4 pb-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600">
                            <Gift class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Giới thiệu bạn bè</p>
                            <p class="mt-1 text-sm text-slate-500">Nhận ngay 10% chiết khấu</p>
                        </div>
                    </div>
                </div>
            </div> -->

            <div class="border-t border-slate-200 px-5 py-4 text-sm text-slate-500">
                <p>&copy; {{ new Date().getFullYear() }} {{ siteName }}.</p>
                <p v-if="supportLine" class="mt-1 text-xs text-slate-400">{{ supportLine }}</p>
            </div>
        </div>
    </aside>
</template>
