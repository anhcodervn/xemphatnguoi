<script setup lang="ts">
import { useSystemSetting } from '@/composables/useSystemSetting';
import {
    BarChart3,
    CarFront,
    CircleUserRound,
    Code2,
    CreditCard,
    Eye,
    History,
    LayoutDashboard,
    Search,
    Wallet,
    X,
    type LucideIcon,
} from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';

defineProps<{ isOpen: boolean }>();
defineEmits<{ close: [] }>();

type NavItem = { label: string; icon: LucideIcon; href: string; external?: boolean };

const route = useRoute();
const { settings, fetchSettings } = useSystemSetting();
const items: NavItem[] = [
    { label: 'Tổng quan', icon: LayoutDashboard, href: '/dashboard' },
    { label: 'Tra cứu trên website', icon: Search, href: '/tra-cuu-phat-nguoi', external: true },
    { label: 'Lịch sử tra cứu', icon: History, href: '/dashboard/history' },
    { label: 'Xe của tôi', icon: CarFront, href: '/dashboard/vehicles' },
    { label: 'Theo dõi biển số', icon: Eye, href: '/dashboard/monitoring' },
    { label: 'API', icon: Code2, href: '/dashboard/api' },
    { label: 'Lượt dùng API', icon: BarChart3, href: '/dashboard/api-usage' },
    { label: 'Nạp tiền', icon: Wallet, href: '/dashboard/wallet' },
    { label: 'Giao dịch', icon: CreditCard, href: '/dashboard/transactions' },
    { label: 'Tài khoản', icon: CircleUserRound, href: '/dashboard/account' },
];

const isActive = (href: string): boolean => (href === '/dashboard' ? route.path === href : route.path === href || route.path.startsWith(`${href}/`));
const siteLogo = computed(() => settings.value.light_logo || '');
const siteName = computed(() => settings.value.site_name || 'XemPhatNguoi.vn');
const siteInitial = computed(() => siteName.value.trim().charAt(0).toUpperCase() || 'X');

onMounted(fetchSettings);
</script>

<template>
    <Teleport to="body"
        ><button v-if="isOpen" type="button" aria-label="Đóng menu" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" @click="$emit('close')"
    /></Teleport>
    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 border-r border-slate-800 bg-slate-950 text-slate-100 transition duration-200 lg:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <RouterLink to="/dashboard" class="app-focus flex min-h-11 min-w-0 items-center gap-3 rounded-lg">
                    <img v-if="siteLogo" :src="siteLogo" :alt="siteName" class="max-h-10 max-w-44 object-contain" />
                    <template v-else
                        ><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-600 font-black text-white">{{
                            siteInitial
                        }}</span
                        ><span class="truncate font-bold text-white">{{ siteName }}</span></template
                    >
                </RouterLink>
                <button
                    type="button"
                    aria-label="Đóng menu"
                    class="app-focus flex min-h-11 min-w-11 items-center justify-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white lg:hidden"
                    @click="$emit('close')"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>
            <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-5" aria-label="Dashboard">
                <ul class="grid gap-1">
                    <li v-for="item in items" :key="item.href">
                        <a
                            v-if="item.external"
                            :href="item.href"
                            class="app-focus flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold text-slate-400 transition hover:bg-white/[0.07] hover:text-white"
                            @click="$emit('close')"
                            ><component :is="item.icon" class="h-5 w-5 shrink-0" /><span>{{ item.label }}</span></a
                        >
                        <RouterLink
                            v-else
                            :to="item.href"
                            class="app-focus flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-semibold transition"
                            :class="isActive(item.href) ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-white/[0.07] hover:text-white'"
                            @click="$emit('close')"
                            ><component :is="item.icon" class="h-5 w-5 shrink-0" /><span>{{ item.label }}</span></RouterLink
                        >
                    </li>
                </ul>
            </nav>
            <div class="border-t border-white/10 p-4">
                <a
                    href="/"
                    class="app-focus flex min-h-11 items-center justify-center rounded-lg border border-white/15 text-sm font-bold text-slate-200 hover:bg-white/10"
                    >Về website</a
                >
            </div>
        </div>
    </aside>
</template>
