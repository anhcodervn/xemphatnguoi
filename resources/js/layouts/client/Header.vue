<script setup lang="ts">
import api from '@/config/axios';
import { clientNotificationService } from '@/services/client-notification.service';
import { useUserStore } from '@/stores/user.store';
import type { ClientNotificationItem } from '@/types/client-notification.type';
import formatCash from '@/utils/helpers/formatCash';
import { handleErrorResponse } from '@/utils/response';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { Bell, CheckCheck, ChevronDown, LogOut, Menu as MenuIcon, Settings, UserRound, Wallet } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

type UserActionItem = {
    label: string;
    icon: typeof UserRound;
    href?: string;
    isAdmin: boolean;
    action?: 'logout';
};

const userStore = useUserStore();

defineEmits<{
    toggleSidebar: [];
}>();

const notifications = ref<ClientNotificationItem[]>([]);
const loadingNotifications = ref(false);
const markingAllRead = ref(false);
const loggingOut = ref(false);

const unreadCount = computed(() => notifications.value.filter((item) => !item.is_read).length);

const userActions: UserActionItem[] = [
    { label: 'Quản trị website', icon: UserRound, href: '/admin', isAdmin: true },
    { label: 'Ví và nạp tiền', icon: Wallet, href: '/wallet', isAdmin: false },
    { label: 'Thông tin tài khoản', icon: UserRound, href: '/profile', isAdmin: false },
    { label: 'Cài đặt mật khẩu', icon: Settings, href: '/profile?tab=password', isAdmin: false },
    { label: 'Đăng xuất', icon: LogOut, isAdmin: false, action: 'logout' },
];

const formatTime = (value: string | null): string => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN');
};

const loadNotifications = async (): Promise<void> => {
    try {
        loadingNotifications.value = true;
        const response = await clientNotificationService.list({ scope: 'user', per_page: 10 });
        notifications.value = response.data;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingNotifications.value = false;
    }
};

const onClickNotification = async (item: ClientNotificationItem): Promise<void> => {
    if (!item.is_read) {
        try {
            await clientNotificationService.markRead(item.id);
            item.is_read = true;
        } catch (error) {
            handleErrorResponse(error);
        }
    }

    if (item.redirect_url) {
        window.location.href = item.redirect_url;
    }
};

const markAllRead = async (): Promise<void> => {
    const unreadItems = notifications.value.filter((item) => !item.is_read);
    if (unreadItems.length === 0) {
        return;
    }

    try {
        markingAllRead.value = true;
        await Promise.all(unreadItems.map((item) => clientNotificationService.markRead(item.id)));
        notifications.value = notifications.value.map((item) => ({ ...item, is_read: true }));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        markingAllRead.value = false;
    }
};

const logout = async (): Promise<void> => {
    if (loggingOut.value) {
        return;
    }

    try {
        loggingOut.value = true;
        const response = await api.post<{ redirect?: string }>('/logout');
        userStore.resetState();
        window.location.href = response.data.redirect || '/login';
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loggingOut.value = false;
    }
};

onMounted(loadNotifications);
</script>

<template>
    <header class="fixed left-0 right-0 top-0 z-30 border-b border-sky-100 bg-white/92 backdrop-blur lg:left-72">
        <div class="flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center">
                <button
                    type="button"
                    class="rounded-2xl border border-sky-100 bg-white p-2.5 text-blue-700 shadow-[0_10px_28px_rgba(15,23,42,0.06)] transition hover:bg-sky-50 lg:hidden"
                    @click="$emit('toggleSidebar')"
                >
                    <MenuIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="flex items-center gap-3">
                <Menu as="div" class="relative">
                    <MenuButton class="relative rounded-full border border-sky-100 bg-white p-3 text-slate-700 shadow-[0_10px_28px_rgba(15,23,42,0.06)] transition hover:bg-sky-50 hover:text-blue-700">
                        <Bell class="h-4 w-4" />
                        <span v-if="unreadCount > 0" class="absolute right-3 top-3 h-2 w-2 rounded-full bg-orange-400" />
                    </MenuButton>

                    <Transition
                        enter-active-class="transform transition duration-200 ease-out"
                        enter-from-class="translate-y-2 scale-[0.98] opacity-0"
                        enter-to-class="translate-y-0 scale-100 opacity-100"
                        leave-active-class="transform transition duration-150 ease-in"
                        leave-from-class="translate-y-0 scale-100 opacity-100"
                        leave-to-class="translate-y-1 scale-[0.98] opacity-0"
                    >
                        <MenuItems
                            class="fixed inset-x-4 top-16 z-40 w-auto rounded-3xl border border-sky-100 bg-white p-2 shadow-[0_18px_40px_rgba(37,99,235,0.12)] outline-none sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-[19rem] sm:origin-top-right"
                        >
                            <div class="flex items-center justify-between px-3 py-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950">Thông báo của bạn</p>
                                    <p class="text-xs text-slate-500">{{ unreadCount }} mục chưa đọc</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-xl p-2 text-blue-600 transition hover:bg-sky-50 hover:text-blue-700 disabled:opacity-60"
                                    :disabled="markingAllRead || unreadCount === 0"
                                    @click="markAllRead"
                                >
                                    <CheckCheck class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="mt-1 max-h-[65vh] space-y-1 overflow-y-auto">
                                <div v-if="loadingNotifications" class="px-3 py-4 text-xs text-slate-500">Đang tải thông báo...</div>
                                <div v-else-if="notifications.length === 0" class="px-3 py-4 text-xs text-slate-500">Chưa có thông báo.</div>

                                <MenuItem v-for="item in notifications" :key="item.id" v-slot="{ active }">
                                    <button
                                        type="button"
                                        class="w-full rounded-2xl px-3 py-3 text-left transition"
                                        :class="[active ? 'bg-sky-50' : '', item.is_read ? 'opacity-80' : '']"
                                        @click="onClickNotification(item)"
                                    >
                                        <p class="text-sm font-medium text-slate-800">{{ item.title }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ item.content }}</p>
                                        <p class="mt-1 text-xs text-slate-400">{{ formatTime(item.created_at) }}</p>
                                    </button>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </Transition>
                </Menu>

                <Menu as="div" class="relative">
                    <MenuButton class="flex items-center gap-3 rounded-full border border-sky-100 bg-white px-4 py-2 shadow-[0_10px_28px_rgba(15,23,42,0.06)] transition hover:bg-sky-50">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-cyan-500 text-sm font-semibold text-white">
                            A
                        </div>
                        <div class="text-left">
                            <p class="max-w-[90px] truncate text-sm font-semibold text-slate-900 sm:max-w-[190px]">{{ userStore.user?.email }}</p>
                            <p class="text-xs text-slate-500">{{ formatCash(parseInt(userStore.user?.wallet?.balance ?? '0')) }}đ</p>
                        </div>
                        <ChevronDown class="hidden h-4 w-4 text-blue-500 sm:block" />
                    </MenuButton>

                    <Transition
                        enter-active-class="transform transition duration-200 ease-out"
                        enter-from-class="translate-y-2 scale-[0.98] opacity-0"
                        enter-to-class="translate-y-0 scale-100 opacity-100"
                        leave-active-class="transform transition duration-150 ease-in"
                        leave-from-class="translate-y-0 scale-100 opacity-100"
                        leave-to-class="translate-y-1 scale-[0.98] opacity-0"
                    >
                        <MenuItems class="absolute right-0 mt-3 w-56 max-w-[calc(100vw-2rem)] origin-top-right rounded-3xl border border-sky-100 bg-white p-2 shadow-[0_18px_40px_rgba(37,99,235,0.12)] outline-none">
                            <div class="px-3 py-2">
                                <p class="text-sm font-semibold text-slate-950">{{ userStore.user?.full_name ?? userStore.user?.username }}</p>
                                <p class="text-xs text-slate-500">{{ userStore.user?.email }}</p>
                            </div>

                            <div class="mt-1 space-y-1">
                                <MenuItem v-for="item in userActions" :key="item.label" v-slot="{ active }">
                                    <RouterLink
                                        v-if="item.href && (!item.isAdmin || userStore.user?.role === 'admin')"
                                        :to="item.href"
                                        class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-sm transition"
                                        :class="active ? 'bg-sky-50 text-blue-700' : 'text-slate-600'"
                                    >
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ item.label }}</span>
                                    </RouterLink>

                                    <button
                                        v-else-if="item.action === 'logout'"
                                        type="button"
                                        class="flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left text-sm transition"
                                        :class="active ? 'bg-slate-50 text-slate-900' : 'text-slate-600'"
                                        :disabled="loggingOut"
                                        @click="logout"
                                    >
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ loggingOut ? 'Đang đăng xuất...' : item.label }}</span>
                                    </button>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </Transition>
                </Menu>
            </div>
        </div>
    </header>
</template>
