<script setup lang="ts">
import { useSystemSetting } from '@/composables/useSystemSetting';
import { CircleUserRound, Code2, History, Layers3, LayoutDashboard, MessageSquareMore, ShieldCheck, Wallet, X, type LucideIcon } from 'lucide-vue-next';
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
    { label: 'Mua proxy', icon: Layers3, href: '/services' },
    { label: 'Quản lý proxy', icon: History, href: '/proxy-orders' },
    { label: 'Check Proxy', icon: ShieldCheck, href: '/proxy-check' },
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
const siteName = computed(() => settings.value.site_name || 'DailyProxy.vn');
const siteInitial = computed(() => siteName.value.trim().charAt(0).toUpperCase() || 'D');
const siteDescription = computed(() => settings.value.site_description || 'Kho proxy trung gian');
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
        class="fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-800 bg-[linear-gradient(180deg,_#0b1220_0%,_#111c32_56%,_#0b1220_100%)] text-slate-100 shadow-2xl shadow-slate-950/20 transition duration-200 lg:relative lg:translate-x-0 lg:shadow-none"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <RouterLink v-if="!siteLogo" to="/" class="flex items-center gap-3">
                    <div
                        class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 text-lg font-bold text-white shadow-lg shadow-cyan-950/30"
                    >
                        {{ siteInitial }}
                    </div>
                    <div>
                        <p class="text-base font-semibold tracking-tight text-white">{{ siteName }}</p>
                        <p class="line-clamp-1 text-sm text-slate-400">{{ siteDescription }}</p>
                    </div>
                </RouterLink>

                <RouterLink v-else to="/" class="flex items-center gap-3">
                    <img :src="siteLogo || ''" alt="logo website" class="max-h-11 object-contain" />
                </RouterLink>

                <button
                    type="button"
                    class="rounded-xl border border-white/10 p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    @click="$emit('close')"
                >
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
                                    ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-lg shadow-blue-950/30'
                                    : 'text-slate-400 hover:bg-white/[0.07] hover:text-white'
                            "
                            @click="$emit('close')"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg transition"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-white/15 text-white ring-1 ring-white/20'
                                        : 'bg-white/[0.06] text-slate-400 group-hover:bg-white/10 group-hover:text-cyan-300'
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

            <div class="border-t border-white/10 px-5 py-4 text-sm text-slate-400">
                <p>&copy; {{ new Date().getFullYear() }} {{ siteName }}.</p>
                <p v-if="supportLine" class="mt-1 text-xs text-slate-500">{{ supportLine }}</p>
            </div>
        </div>
    </aside>
</template>
