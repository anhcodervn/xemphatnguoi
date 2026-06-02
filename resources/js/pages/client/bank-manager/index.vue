<template>
    <div class="space-y-3 pb-3">
        <PackageRequiredState v-if="!hasBankAccess" />

        <template v-else>
        <section
            class="flex flex-col gap-2.5 rounded-[10px] border border-white/70 bg-white/75 px-4 py-3 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.16)] backdrop-blur xl:flex-row xl:items-center xl:justify-between"
        >
            <div>
                <h1 class="text-[1.75rem] font-black tracking-[-0.04em] text-slate-900">Quản lý thẻ</h1>
                <p class="mt-0.5 text-xs text-slate-500">Theo dõi và quản lý các tài khoản ngân hàng đã liên kết trong hệ thống.</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <label class="flex min-w-0 items-center gap-2 rounded-[8px] border border-slate-200 bg-white px-3 py-2 shadow-sm sm:min-w-[280px]">
                    <Search class="h-4 w-4 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Tìm kiếm theo ngân hàng, tên hiển thị, username..."
                        class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400"
                    />
                </label>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-[8px] border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    disabled
                >
                    <SlidersHorizontal class="h-4 w-4" />
                    Bộ lọc
                </button>

                <RouterLink
                    :to="{ name: 'client.bank-manager.bank.create' }"
                    class="inline-flex items-center justify-center gap-2 rounded-[8px] bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-[0_14px_28px_-20px_rgba(37,99,235,0.8)] transition hover:bg-blue-700"
                >
                    <Plus class="h-4 w-4" />
                    Thêm thẻ
                </RouterLink>
            </div>
        </section>

        <section class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="metric in metrics"
                :key="metric.label"
                class="rounded-[10px] border border-slate-200/80 bg-white px-3.5 py-3 shadow-[0_14px_34px_-30px_rgba(15,23,42,0.16)]"
            >
                <div class="flex items-center gap-3">
                    <div :class="metric.iconClass">
                        <component :is="metric.icon" class="h-4.5 w-4.5" />
                    </div>

                    <div>
                        <p class="text-[11px] font-medium text-slate-400">{{ metric.label }}</p>
                        <p class="mt-0.5 text-[1.65rem] font-black tracking-[-0.05em]" :class="metric.valueClass">{{ metric.value }}</p>
                        <p class="text-[11px] text-slate-500">{{ metric.note }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 xl:grid-cols-[1.72fr_0.72fr]">
            <div class="rounded-[10px] border border-slate-200/80 bg-white shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold tracking-[-0.03em] text-slate-900">Danh sách thẻ đã thêm</h2>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">
                            {{ filteredCards.length }} thẻ
                        </span>
                    </div>
                </div>

                <div class="p-2.5">
                    <div v-if="isLoadingCards" class="space-y-2">
                        <div v-for="skeleton in 3" :key="skeleton" class="animate-pulse rounded-[8px] border border-slate-200 bg-white px-3 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-11 w-11 rounded-full bg-slate-100"></div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="h-4 w-40 rounded bg-slate-100"></div>
                                    <div class="h-3 w-28 rounded bg-slate-100"></div>
                                    <div class="h-3 w-full max-w-[320px] rounded bg-slate-100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="filteredCards.length === 0"
                        class="rounded-[8px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center"
                    >
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-900">
                            {{ search.trim() === '' ? 'Chưa có thẻ nào được liên kết' : 'Không tìm thấy thẻ phù hợp' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{
                                search.trim() === ''
                                    ? 'Thêm tài khoản ngân hàng đầu tiên để bắt đầu đồng bộ giao dịch.'
                                    : 'Thử đổi từ khóa tìm kiếm hoặc xóa bộ lọc hiện tại.'
                            }}
                        </p>
                    </div>

                    <div v-else class="space-y-2">
                        <article
                            v-for="card in filteredCards"
                            :key="card.id"
                            class="rounded-[8px] border border-slate-200 bg-white px-3 py-3 transition hover:border-slate-300"
                        >
                            <div class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_auto] xl:items-center">
                                <div class="flex min-w-0 gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white text-xs font-bold text-white"
                                        :style="{ backgroundColor: card.bank_bg_color }"
                                    >
                                        <img v-if="card.bank_logo" :src="card.bank_logo" :alt="card.bank_name" class="h-full w-full object-cover" />
                                        <span v-else>{{ getBankInitials(card) }}</span>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-sm font-bold tracking-[-0.02em] text-slate-900">
                                                {{ card.bank_name }} - {{ card.account_name }}
                                            </h3>
                                            <span :class="statusBadgeClass(card.status)">
                                                <span class="bg-current/80 h-1.5 w-1.5 rounded-full" />
                                                {{ statusLabel(card.status) }}
                                            </span>
                                        </div>

                                        <div class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-slate-500">
                                            <span class="text-xs tracking-[0.26em]">{{ maskAccountNumber(card.account_number) }}</span>
                                            <Copy class="h-3.5 w-3.5" />
                                        </div>

                                        <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                            <span class="inline-flex items-center gap-1.5">
                                                <UserRound class="h-3 w-3" />
                                                {{ card.username || 'Chưa có username' }}
                                            </span>
                                            <span class="inline-flex items-center gap-1.5">
                                                <CalendarClock class="h-3 w-3" />
                                                Cập nhật {{ formatDateTime(card.updated_at) }}
                                            </span>
                                            <span v-if="card.last_sync_at" class="inline-flex items-center gap-1.5">
                                                <RefreshCcw class="h-3 w-3" />
                                                Đồng bộ {{ formatDateTime(card.last_sync_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                                    <RouterLink
                                        v-if="card.status === 'active'"
                                        :to="{ name: 'client.bank-manager.detail', params: { bank_id: card.id } }"
                                        class="inline-flex items-center gap-1.5 rounded-[8px] border border-blue-200 bg-white px-2.5 py-2 text-xs font-semibold text-blue-600 transition hover:bg-blue-50"
                                    >
                                        Chi tiết
                                        <ChevronRight class="h-3.5 w-3.5" />
                                    </RouterLink>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 bg-slate-50 px-2.5 py-2 text-xs font-medium text-slate-400"
                                    >
                                        Thẻ đang tắt
                                    </span>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-[8px] border px-2.5 py-2 text-xs font-medium transition"
                                        :class="
                                            card.status === 'active'
                                                ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                                : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                        "
                                        :disabled="togglingCardId === card.id"
                                        @click="toggleCardStatus(card)"
                                    >
                                        <RefreshCcw class="h-3.5 w-3.5" :class="togglingCardId === card.id ? 'animate-spin' : ''" />
                                        {{ togglingCardId === card.id ? 'Đang cập nhật...' : card.status === 'active' ? 'Tắt thẻ' : 'Bật thẻ' }}
                                    </button>
                                    <RouterLink
                                        :to="{ name: 'client.bank-manager.bank.edit', params: { bank_id: card.id } }"
                                        class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 bg-white px-2.5 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                        Sửa
                                    </RouterLink>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <aside class="space-y-2.5 rounded-[10px] border border-slate-200/80 bg-white p-3.5 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
                <div class="flex items-center gap-2">
                    <ShieldCheck class="h-4.5 w-4.5 text-slate-500" />
                    <h2 class="text-base font-bold tracking-[-0.03em] text-slate-900">Thông tin bảo mật</h2>
                </div>

                <div
                    v-for="item in securityItems"
                    :key="item.title"
                    class="flex items-start gap-2.5 rounded-[8px] border border-slate-100 bg-white px-3 py-2.5"
                >
                    <div :class="item.iconClass">
                        <component :is="item.icon" class="h-3.5 w-3.5" />
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-slate-900">{{ item.title }}</p>
                        <p class="mt-0.5 text-[11px] leading-5 text-slate-500">{{ item.description }}</p>
                    </div>
                </div>

                <div class="rounded-[8px] border border-blue-200 bg-blue-50/70 px-3 py-3">
                    <div class="flex items-start gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-[8px] bg-white text-blue-600">
                            <Lock class="h-3.5 w-3.5" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-blue-700">Bảo mật là ưu tiên hàng đầu</p>
                            <p class="mt-0.5 text-[11px] leading-5 text-slate-600">
                                Thông tin tài khoản ngân hàng được quản lý tập trung để phục vụ đồng bộ, theo dõi và cảnh báo trạng thái kết nối.
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { clientBankService } from '@/services/client-bank.service';
import { useUserStore } from '@/stores/user.store';
import type { BankAccountType } from '@/types/bank.type';
import { handleErrorResponse } from '@/utils/response';
import PackageRequiredState from './components/PackageRequiredState.vue';
import {
    Bell,
    CalendarClock,
    CheckCircle2,
    ChevronRight,
    CircleAlert,
    Copy,
    CreditCard,
    Lock,
    Pencil,
    Plus,
    RefreshCcw,
    Search,
    ShieldCheck,
    ShieldEllipsis,
    SlidersHorizontal,
    TriangleAlert,
    UserRound,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import Swal from 'sweetalert2';

const search = ref('');
const cards = ref<BankAccountType[]>([]);
const isLoadingCards = ref(false);
const togglingCardId = ref<number | null>(null);
const userStore = useUserStore();

const hasBankAccess = computed(() => {
    const subscription = userStore.user?.user_subscriptions;

    if (!subscription || subscription.status !== 'active') {
        return false;
    }

    if (!subscription.expires_at) {
        return true;
    }

    return new Date(subscription.expires_at).getTime() > Date.now();
});

const filteredCards = computed(() => {
    const keyword = search.value.trim().toLowerCase();

    if (keyword === '') {
        return cards.value;
    }

    return cards.value.filter((card) =>
        [card.bank_name, card.bank_full_name, card.account_name, card.username, card.account_number]
            .filter((field): field is string => typeof field === 'string' && field !== '')
            .some((field) => field.toLowerCase().includes(keyword)),
    );
});

const metrics = computed(() => [
    {
        label: 'Tổng thẻ',
        value: cards.value.length,
        note: 'Tài khoản đã liên kết',
        valueClass: 'text-blue-600',
        icon: CreditCard,
        iconClass: 'flex h-9 w-9 items-center justify-center rounded-[8px] bg-blue-100 text-blue-600',
    },
    {
        label: 'Đang hoạt động',
        value: cards.value.filter((card) => card.status === 'active').length,
        note: 'Kết nối khả dụng',
        valueClass: 'text-emerald-600',
        icon: CheckCircle2,
        iconClass: 'flex h-9 w-9 items-center justify-center rounded-[8px] bg-emerald-100 text-emerald-600',
    },
    {
        label: 'Ngưng hoạt động',
        value: cards.value.filter((card) => card.status === 'inactive').length,
        note: 'Cần kiểm tra lại',
        valueClass: 'text-amber-500',
        icon: CircleAlert,
        iconClass: 'flex h-9 w-9 items-center justify-center rounded-[8px] bg-amber-100 text-amber-500',
    },
    {
        label: 'Lỗi kết nối',
        value: cards.value.filter((card) => card.status === 'error').length,
        note: 'Cần xử lý ngay',
        valueClass: 'text-rose-600',
        icon: TriangleAlert,
        iconClass: 'flex h-9 w-9 items-center justify-center rounded-[8px] bg-rose-100 text-rose-600',
    },
]);

const securityItems = [
    {
        title: 'Lưu trữ mã hóa',
        description: 'Thông tin đăng nhập và token được lưu trữ tập trung để phục vụ đồng bộ và được bảo vệ theo cấu hình hệ thống.',
        icon: Lock,
        iconClass: 'flex h-8 w-8 items-center justify-center rounded-[8px] bg-emerald-100 text-emerald-600',
    },
    {
        title: 'Kết nối an toàn',
        description: 'Luồng đồng bộ dữ liệu ngân hàng sử dụng kết nối bảo mật và tách riêng theo từng provider.',
        icon: ShieldCheck,
        iconClass: 'flex h-8 w-8 items-center justify-center rounded-[8px] bg-blue-100 text-blue-600',
    },
    {
        title: 'Không hiển thị dữ liệu nhạy cảm',
        description: 'Danh sách chỉ hiển thị số tài khoản đã được che bớt, không trả về mật khẩu hoặc token.',
        icon: ShieldEllipsis,
        iconClass: 'flex h-8 w-8 items-center justify-center rounded-[8px] bg-violet-100 text-violet-600',
    },
    {
        title: 'Cảnh báo thông minh',
        description: 'Theo dõi nhanh trạng thái active, inactive hoặc error để xử lý kết nối ngân hàng kịp thời.',
        icon: Bell,
        iconClass: 'flex h-8 w-8 items-center justify-center rounded-[8px] bg-amber-100 text-amber-500',
    },
];

const loadCards = async (): Promise<void> => {
    isLoadingCards.value = true;

    try {
        cards.value = await clientBankService.listAccounts();
    } catch (error) {
        console.error('load bank accounts failed', error);
        cards.value = [];
    } finally {
        isLoadingCards.value = false;
    }
};

const toggleCardStatus = async (card: BankAccountType): Promise<void> => {
    if (togglingCardId.value === card.id) {
        return;
    }

    const nextStatus: 'active' | 'inactive' = card.status === 'active' ? 'inactive' : 'active';

    const confirmation = await Swal.fire({
        icon: 'question',
        title: nextStatus === 'inactive' ? 'Tắt thẻ này?' : 'Bật lại thẻ này?',
        text:
            nextStatus === 'inactive'
                ? 'Khi tắt, thẻ sẽ không được cron đồng bộ và không thể xem chi tiết.'
                : 'Khi bật lại, các chức năng liên quan đến thẻ sẽ hoạt động bình thường.',
        showCancelButton: true,
        confirmButtonText: nextStatus === 'inactive' ? 'Tắt thẻ' : 'Bật thẻ',
        cancelButtonText: 'Hủy',
        confirmButtonColor: nextStatus === 'inactive' ? '#f59e0b' : '#2563eb',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    togglingCardId.value = card.id;

    try {
        const response = await clientBankService.updateAccountStatus(card.id, nextStatus);
        const updatedCard = response.data?.data as BankAccountType | undefined;

        if (updatedCard) {
            cards.value = cards.value.map((item) => (item.id === updatedCard.id ? { ...item, ...updatedCard } : item));
        } else {
            await loadCards();
        }

        await Swal.fire('Thành công', response.data?.message || 'Cập nhật trạng thái thẻ thành công.', 'success');
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        togglingCardId.value = null;
    }
};

const getBankInitials = (card: BankAccountType): string => {
    const label = card.bank_short_name || card.bank_name || card.bank_code || 'BK';

    return label
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
};

const maskAccountNumber = (accountNumber: string): string => {
    const trimmed = accountNumber.trim();

    if (trimmed.length <= 4) {
        return trimmed;
    }

    return `**** **** ${trimmed.slice(-4)}`;
};

const statusLabel = (status: BankAccountType['status']): string => {
    switch (status) {
        case 'active':
            return 'Đang hoạt động';
        case 'inactive':
            return 'Ngưng hoạt động';
        case 'error':
            return 'Lỗi kết nối';
        default:
            return status;
    }
};

const statusBadgeClass = (status: BankAccountType['status']): string => {
    switch (status) {
        case 'active':
            return 'inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700';
        case 'inactive':
            return 'inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700';
        case 'error':
            return 'inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700';
        default:
            return 'inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700';
    }
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return 'chưa có dữ liệu';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

onMounted(async () => {
    if (!userStore.user) {
        await userStore.bootstrap({ silent: true });
    }

    if (!hasBankAccess.value) {
        return;
    }

    await loadCards();
});
</script>
