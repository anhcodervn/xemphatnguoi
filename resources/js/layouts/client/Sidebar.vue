<script setup lang="ts">
import { useSystemSetting } from '@/composables/useSystemSetting';
import { useSupportStore } from '@/stores/support.store';
import {
    ChevronDown,
    CircleUserRound,
    Code2,
    Globe2,
    History,
    Layers3,
    LayoutDashboard,
    MessageCircleMore,
    MessageSquareMore,
    ShieldCheck,
    Wallet,
    X,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
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
    href?: string;
    children?: NavChild[];
    badge?: 'support';
};

type NavChild = {
    label: string;
    icon: LucideIcon;
    href: string;
};

const route = useRoute();
const { settings, fetchSettings } = useSystemSetting();
const supportStore = useSupportStore();

const items: NavItem[] = [
    { label: 'Tổng quan', icon: LayoutDashboard, href: '/' },
    { label: 'Hồ sơ tài khoản', icon: CircleUserRound, href: '/profile' },
    { label: 'Ví và nạp tiền', icon: Wallet, href: '/wallet' },
    { label: 'Mua proxy', icon: Layers3, href: '/services' },
    { label: 'Quản lý proxy', icon: History, href: '/proxy-orders' },
    {
        label: 'Công cụ',
        icon: ShieldCheck,
        children: [
            { label: 'Check Live Proxy', icon: ShieldCheck, href: '/proxy-check' },
            { label: 'Check quốc gia', icon: Globe2, href: '/proxy-country-check' },
        ],
    },
    { label: 'Tài liệu API', icon: Code2, href: '/api-docs' },
    { label: 'Hỗ trợ trực tiếp', icon: MessageCircleMore, href: '/support', badge: 'support' },
    { label: 'Liên hệ và góp ý', icon: MessageSquareMore, href: '/contact' },
];

const isActive = (href: string): boolean => {
    if (href.includes('?')) {
        return route.fullPath === href;
    }

    if (href === '/') {
        return route.path === href && !route.fullPath.includes('?');
    }

    return (route.path === href || route.path.startsWith(`${href}/`)) && !route.fullPath.includes('?');
};

const hasActiveChild = (item: NavItem): boolean => item.children?.some((child) => isActive(child.href)) ?? false;
const openMenus = ref<Record<string, boolean>>({
    'Công cụ': hasActiveChild(items.find((item) => item.label === 'Công cụ')!),
});

const toggleMenu = (label: string): void => {
    openMenus.value[label] = !openMenus.value[label];
};

watch(
    () => route.fullPath,
    () => {
        items.forEach((item) => {
            if (hasActiveChild(item)) {
                openMenus.value[item.label] = true;
            }
        });
    },
);

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
        class="fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-800 bg-[linear-gradient(180deg,_#0b1220_0%,_#111c32_56%,_#0b1220_100%)] text-slate-100 shadow-2xl shadow-slate-950/20 transition duration-200 lg:translate-x-0 lg:shadow-none"
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

            <nav class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-5">
                <ul class="space-y-1.5">
                    <li v-for="item in items" :key="item.label">
                        <RouterLink
                            v-if="item.href"
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
                            <span
                                v-if="item.badge === 'support' && supportStore.userUnread > 0"
                                class="inline-flex min-h-6 min-w-6 items-center justify-center rounded-full bg-red-600 px-1.5 text-[11px] font-black tabular-nums text-white"
                            >
                                {{ supportStore.userUnread > 99 ? '99+' : supportStore.userUnread }}
                            </span>
                        </RouterLink>

                        <template v-else>
                            <button
                                type="button"
                                class="group flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium transition"
                                :class="hasActiveChild(item) ? 'bg-white/[0.09] text-white' : 'text-slate-400 hover:bg-white/[0.07] hover:text-white'"
                                :aria-expanded="openMenus[item.label] ?? false"
                                @click="toggleMenu(item.label)"
                            >
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-lg transition"
                                    :class="
                                        hasActiveChild(item)
                                            ? 'bg-cyan-400/15 text-cyan-300 ring-1 ring-cyan-300/20'
                                            : 'bg-white/[0.06] text-slate-400 group-hover:bg-white/10 group-hover:text-cyan-300'
                                    "
                                >
                                    <component :is="item.icon" class="h-4.5 w-4.5" />
                                </div>
                                <span class="flex-1 text-left">{{ item.label }}</span>
                                <ChevronDown
                                    class="h-4 w-4 transition-transform duration-200"
                                    :class="openMenus[item.label] ? 'rotate-180 text-cyan-300' : ''"
                                />
                            </button>

                            <ul v-show="openMenus[item.label]" class="mt-1 grid gap-1 pl-5">
                                <li v-for="child in item.children" :key="child.label">
                                    <RouterLink
                                        :to="child.href"
                                        class="group flex items-center gap-3 rounded-xl py-2.5 pl-5 pr-3 text-sm font-medium transition"
                                        :class="
                                            isActive(child.href)
                                                ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-lg shadow-blue-950/30'
                                                : 'text-slate-400 hover:bg-white/[0.07] hover:text-white'
                                        "
                                        @click="$emit('close')"
                                    >
                                        <component
                                            :is="child.icon"
                                            class="h-4 w-4 shrink-0"
                                            :class="isActive(child.href) ? 'text-white' : 'text-slate-500 group-hover:text-cyan-300'"
                                        />
                                        <span>{{ child.label }}</span>
                                    </RouterLink>
                                </li>
                            </ul>
                        </template>
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
