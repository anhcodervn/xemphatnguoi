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
        <button v-if="isOpen" type="button" class="fixed inset-0 z-40 bg-slate-950/25 lg:hidden" @click="$emit('close')" />
    </Teleport>

    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 border-r border-sky-100 bg-white text-slate-900 transition duration-200 lg:relative lg:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-sky-100 px-5 py-6">
                <RouterLink v-if="!siteLogo" to="/" class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 via-cyan-500 to-teal-400 text-white shadow-[0_16px_30px_rgba(37,99,235,0.22)]">
                        <span class="text-xl font-black">G</span>
                    </div>
                    <div>
                        <p class="text-[1.05rem] font-bold tracking-[-0.03em] text-slate-950">{{ siteName }}</p>
                        <p class="line-clamp-1 text-sm text-slate-500">{{ siteDescription }}</p>
                    </div>
                </RouterLink>

                <RouterLink v-else to="/" class="flex items-center gap-3">
                    <img :src="siteLogo || ''" alt="logo website" class="max-h-12 object-contain" />
                </RouterLink>

                <button type="button" class="rounded-xl border border-sky-100 p-2 text-slate-500 transition hover:bg-sky-50 lg:hidden" @click="$emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <nav class="flex-1 px-3 py-6">
                <ul class="space-y-2.5">
                    <li v-for="item in items" :key="item.label">
                        <RouterLink
                            :to="item.href"
                            class="group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200"
                            :class="
                                isActive(item.href)
                                    ? 'bg-gradient-to-r from-blue-500 via-cyan-400 to-sky-300 text-white shadow-[0_14px_28px_rgba(56,189,248,0.28)]'
                                    : 'text-slate-600 hover:bg-sky-50 hover:text-blue-700'
                            "
                            @click="$emit('close')"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl transition"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-white/20 text-white'
                                        : 'bg-sky-50 text-blue-600 group-hover:bg-white group-hover:text-blue-700'
                                "
                            >
                                <component :is="item.icon" class="h-4.5 w-4.5" />
                            </div>
                            <span class="flex-1">{{ item.label }}</span>
                        </RouterLink>
                    </li>
                </ul>
            </nav>

            <div class="px-4 pb-5">
                <div class="rounded-[22px] border border-sky-100 bg-[linear-gradient(180deg,#f5fbff_0%,#ffffff_100%)] p-4 shadow-[0_14px_30px_rgba(15,23,42,0.06)]">
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-100 to-violet-100 text-blue-600">
                            <Gift class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-950">Giới thiệu bạn bè</p>
                            <p class="mt-1 text-sm text-slate-500">Nhận ngay 10% chiết khấu</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Mời thêm người dùng mới và tối ưu chi phí solve captcha cho cả hai bên.</p>
                </div>
            </div>

            <div class="border-t border-sky-100 px-5 py-4 text-sm text-slate-500">
                <p>&copy; {{ new Date().getFullYear() }} {{ siteName }}.</p>
                <p v-if="supportLine" class="mt-1 text-xs text-slate-400">{{ supportLine }}</p>
            </div>
        </div>
    </aside>
</template>
