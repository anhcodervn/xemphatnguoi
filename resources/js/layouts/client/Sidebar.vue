<script setup lang="ts">
import { useSystemSetting } from "@/composables/useSystemSetting";
import {
    BanknoteIcon,
    BookText,
    CircleArrowUp,
    CircleUserRound,
    CreditCard,
    LayoutDashboard,
    Menu,
    MessageSquareMore,
    Newspaper,
    X,
    type LucideIcon,
} from "lucide-vue-next";
import { computed, onMounted } from "vue";
import { RouterLink, useRoute } from "vue-router";

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
    external?: boolean;
};

const route = useRoute();
const { settings, fetchSettings } = useSystemSetting();

const items: NavItem[] = [
    { label: "Tổng quan", icon: LayoutDashboard, href: "/" },
    { label: "Hồ sơ tài khoản", icon: CircleUserRound, href: "/profile" },
    { label: "Nạp tiền", icon: BanknoteIcon, href: "/recharge" },
    { label: "Quản lý thẻ", icon: CreditCard, href: "/bank-manager" },
    { label: "Quản lý nâng cấp", icon: CircleArrowUp, href: "/package" },
    { label: "Tài liệu API", icon: BookText, href: "/api-docs" },
    { label: "Tin tức", icon: Newspaper, href: "/blog", external: true },
    { label: "Liên hệ và góp ý", icon: MessageSquareMore, href: "/contact" },
];

const isActive = (href: string): boolean => {
    if (href === "/") {
        return route.path === href;
    }

    return route.path === href || route.path.startsWith(`${href}/`);
};

const siteLogo = computed(() => settings.value.light_logo || false);
const siteName = computed(() => settings.value.site_name || "Client Panel");
const siteDescription = computed(() => settings.value.site_description || "Simple dashboard");
const supportLine = computed(() => settings.value.support_email || settings.value.hotline || "");

onMounted(async () => {
    await fetchSettings();
});
</script>

<template>
    <Teleport to="body">
        <button v-if="isOpen" type="button" class="fixed inset-0 z-40 bg-black/30 lg:hidden" @click="$emit('close')" />
    </Teleport>

    <aside
        class="fixed inset-y-0 left-0 z-50 w-72 bg-[#212120] text-white transition duration-200 lg:relative lg:translate-x-0"
        :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <div v-if="!siteLogo" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white px-3 text-[#41b883]">
                        <Menu class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="font-semibold">{{ siteName }}</p>
                        <p class="line-clamp-1 text-xs text-white/50">{{ siteDescription }}</p>
                    </div>
                </div>

                <div v-else class="flex items-center">
                    <img :src="siteLogo || ''" alt="logo website" />
                </div>

                <button type="button" class="rounded-xl p-2 text-white/70 lg:hidden" @click="$emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <nav class="flex-1 px-4 py-5">
                <ul class="space-y-2">
                    <li v-for="item in items" :key="item.label" class="relative">
                        <a
                            v-if="item.external"
                            :href="item.href"
                            class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-medium text-white/65 transition hover:bg-white/5 hover:text-white"
                            @click="$emit('close')"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                            <span>{{ item.label }}</span>
                        </a>
                        <RouterLink
                            v-else
                            :to="item.href"
                            class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-medium transition"
                            :class="isActive(item.href) ? 'bg-white text-[#212120]' : 'text-white/65 hover:bg-white/5 hover:text-white'"
                            @click="$emit('close')"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                            <span>{{ item.label }}</span>
                        </RouterLink>
                    </li>
                </ul>
            </nav>

            <div class="border-t border-white/10 px-5 py-4 text-sm text-white/55">
                <p>&copy; {{ new Date().getFullYear() }} {{ siteName }}.</p>
                <p v-if="supportLine" class="mt-1 text-xs text-white/40">{{ supportLine }}</p>
            </div>
        </div>
    </aside>
</template>
