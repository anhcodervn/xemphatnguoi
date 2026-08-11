<script setup lang="ts">
import api from '@/config/axios';
import { clientNotificationService } from '@/services/client-notification.service';
import { useUserStore } from '@/stores/user.store';
import type { ClientNotificationItem } from '@/types/client-notification.type';
import type { WalletBalanceChangedEvent, WalletDepositCreditedEvent } from '@/types/wallet.type';
import formatCash from '@/utils/helpers/formatCash';
import { handleErrorResponse } from '@/utils/response';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { echo } from '@laravel/echo-vue';
import { Bell, CheckCheck, ChevronDown, LogOut, Menu as MenuIcon, Settings, ShieldAlert, UserRound, Wallet } from 'lucide-vue-next';
import Swal, { type SweetAlertIcon } from 'sweetalert2';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
const userInitial = computed(() => {
    const seed = userStore.user?.full_name || userStore.user?.username || userStore.user?.email || 'A';
    return seed.trim().charAt(0).toUpperCase();
});

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

let subscribedWalletUserId: number | null = null;

const leaveWalletChannel = (): void => {
    if (subscribedWalletUserId === null) {
        return;
    }

    echo().leave(`users.${subscribedWalletUserId}.wallet`);
    subscribedWalletUserId = null;
};

const updateUserWallet = (snapshot: Pick<WalletBalanceChangedEvent, 'balance' | 'hold_balance' | 'total_recharge' | 'total_spent'>): void => {
    if (!userStore.user?.wallet) {
        return;
    }

    userStore.user.wallet = {
        ...userStore.user.wallet,
        balance: snapshot.balance,
        hold_balance: snapshot.hold_balance,
        total_recharge: snapshot.total_recharge,
        total_spent: snapshot.total_spent,
    };
};

const prependNotification = (notification: ClientNotificationItem | null): boolean => {
    if (!notification || notifications.value.some((item) => item.id === notification.id)) {
        return false;
    }

    notifications.value = [notification, ...notifications.value].slice(0, 10);

    return true;
};

const showRealtimeNotification = (notification: ClientNotificationItem): void => {
    const supportedIcons: SweetAlertIcon[] = ['success', 'error', 'warning', 'info', 'question'];
    const icon = supportedIcons.includes(notification.type as SweetAlertIcon) ? (notification.type as SweetAlertIcon) : 'info';

    void Swal.fire({
        icon,
        title: notification.title,
        text: notification.content,
        confirmButtonText: 'Đã hiểu',
        confirmButtonColor: '#2563eb',
        allowOutsideClick: false,
    });
};

watch(
    () => userStore.user?.id ?? null,
    (userId) => {
        if (userId === subscribedWalletUserId) {
            return;
        }

        leaveWalletChannel();

        if (userId === null) {
            return;
        }

        subscribedWalletUserId = userId;
        const walletChannel = echo().private(`users.${userId}.wallet`);

        walletChannel.listen('.wallet.deposit.credited', (event: WalletDepositCreditedEvent) => {
            if (userStore.user?.wallet) {
                userStore.user.wallet = {
                    ...userStore.user.wallet,
                    balance: event.balance,
                    total_recharge: event.total_recharge,
                };
            }

            prependNotification(event.notification);
            window.dispatchEvent(new CustomEvent<WalletDepositCreditedEvent>('wallet:deposit-credited', { detail: event }));
        });

        walletChannel.listen('.wallet.balance.changed', (event: WalletBalanceChangedEvent) => {
            updateUserWallet(event);

            if (event.notification && prependNotification(event.notification)) {
                showRealtimeNotification(event.notification);
            }

            window.dispatchEvent(new CustomEvent<WalletBalanceChangedEvent>('wallet:balance-changed', { detail: event }));
        });
    },
    { immediate: true },
);

onMounted(loadNotifications);

onBeforeUnmount(leaveWalletChannel);
</script>

<template>
    <header
        class="fixed left-0 right-0 top-0 z-30 border-b border-slate-200/80 bg-white/85 shadow-[0_12px_35px_-28px_rgba(15,23,42,0.45)] backdrop-blur-xl lg:left-72"
    >
        <div class="relative z-20 flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center">
                <button
                    type="button"
                    class="proxy-focus rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 lg:hidden"
                    @click="$emit('toggleSidebar')"
                >
                    <MenuIcon class="h-5 w-5" />
                </button>
            </div>

            <div class="flex items-center gap-3">
                <Menu as="div" class="relative">
                    <MenuButton
                        class="proxy-focus relative rounded-full border border-slate-200 bg-white p-3 text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
                    >
                        <Bell class="h-4 w-4" />
                        <span v-if="unreadCount > 0" class="absolute right-3 top-3 h-2 w-2 rounded-full bg-amber-500" />
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
                            class="fixed inset-x-4 top-28 z-50 w-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-lg outline-none sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-3 sm:w-[19rem] sm:origin-top-right"
                        >
                            <div class="flex items-center justify-between px-3 py-2">
                                <div>
                                    <p class="text-sm font-semibold text-slate-950">Thông báo của bạn</p>
                                    <p class="text-xs text-slate-500">{{ unreadCount }} mục chưa đọc</p>
                                </div>
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-blue-600 transition hover:bg-blue-50 hover:text-blue-700 disabled:opacity-60"
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
                                        class="w-full rounded-xl px-3 py-3 text-left transition"
                                        :class="[active ? 'bg-slate-50' : '', item.is_read ? 'opacity-80' : '']"
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
                    <MenuButton
                        class="proxy-focus flex items-center gap-3 rounded-full border border-slate-200 bg-white px-3.5 py-2 transition hover:border-blue-200 hover:bg-blue-50/60"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-semibold text-white shadow-sm"
                        >
                            {{ userInitial }}
                        </div>
                        <div class="text-left">
                            <p class="max-w-[90px] truncate text-sm font-semibold text-slate-900 sm:max-w-[190px]">{{ userStore.user?.email }}</p>
                            <p class="text-xs text-slate-500">{{ formatCash(parseInt(userStore.user?.wallet?.balance ?? '0')) }}đ</p>
                        </div>
                        <ChevronDown class="hidden h-4 w-4 text-slate-400 sm:block" />
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
                            class="absolute right-0 z-50 mt-3 w-56 max-w-[calc(100vw-2rem)] origin-top-right rounded-2xl border border-slate-200 bg-white p-2 shadow-lg outline-none"
                        >
                            <div class="px-3 py-2">
                                <p class="text-sm font-semibold text-slate-950">{{ userStore.user?.full_name ?? userStore.user?.username }}</p>
                                <p class="text-xs text-slate-500">{{ userStore.user?.email }}</p>
                            </div>

                            <div class="mt-1 space-y-1">
                                <MenuItem v-for="item in userActions" :key="item.label" v-slot="{ active }">
                                    <RouterLink
                                        v-if="item.href && (!item.isAdmin || userStore.user?.role === 'admin')"
                                        :to="item.href"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition"
                                        :class="active ? 'bg-slate-50 text-slate-900' : 'text-slate-600'"
                                    >
                                        <component :is="item.icon" class="h-4 w-4" />
                                        <span>{{ item.label }}</span>
                                    </RouterLink>

                                    <button
                                        v-else-if="item.action === 'logout'"
                                        type="button"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition"
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

        <div
            class="relative z-0 flex h-10 overflow-hidden border-t border-amber-300 bg-amber-50 text-amber-950"
            role="note"
            aria-label="Cảnh báo sử dụng dịch vụ"
        >
            <div class="relative z-10 flex shrink-0 items-center border-r border-amber-300 bg-amber-100 px-3" aria-hidden="true">
                <ShieldAlert class="h-5 w-5 text-amber-700" />
            </div>
            <div class="global-warning-marquee relative min-w-0 flex-1 overflow-hidden">
                <div class="global-warning-marquee-track flex h-full w-max items-center whitespace-nowrap text-sm font-bold">
                    <p class="pr-12">
                        Nghiêm cấm sử dụng Proxy với mục đích trái pháp luật. Người dùng phải chịu toàn bộ trách nhiệm trước pháp luật khi sử dụng
                        dịch vụ của chúng tôi.
                    </p>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.global-warning-marquee-track {
    min-width: 100%;
    padding-left: 100%;
    will-change: transform;
    animation: global-warning-marquee 24s linear infinite;
}

@keyframes global-warning-marquee {
    from {
        transform: translate3d(0, 0, 0);
    }

    to {
        transform: translate3d(-100%, 0, 0);
    }
}
</style>
