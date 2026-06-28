<script setup lang="ts">
import type { ClientProfilePaginationMeta, UserLogItem } from '@/types/client-profile.type';
import { formatTime } from '@/utils/helpers/format';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    filters: {
        search: string;
        action: string;
    };
    logs: UserLogItem[];
    loading: boolean;
    meta: ClientProfilePaginationMeta;
}>();

const emit = defineEmits<{
    'change-page': [page: number];
    'update:search': [value: string];
    'update:action': [value: string];
}>();

const actionOptions = [
    { label: 'Tất cả hành động', value: 'all' },
    { label: 'Đăng nhập', value: 'login' },
    { label: 'Đổi mật khẩu', value: 'password_change' },
    { label: 'Cập nhật thông tin', value: 'profile_update' },
    { label: 'Tạo tài khoản', value: 'register' },
];

const statusClasses: Record<string, string> = {
    success: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    warning: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',
    failed: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
};

const statusLabels: Record<string, string> = {
    success: 'Thành công',
    warning: 'Cảnh báo',
    failed: 'Thất bại',
};

const getStatusClass = (status: string): string => statusClasses[status] ?? 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
const getStatusLabel = (status: string): string => statusLabels[status] ?? 'Không rõ';
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-col gap-2 rounded-[10px] bg-slate-50/80 p-3 md:flex-row md:items-center">
            <input
                :value="props.filters.search"
                type="text"
                class="w-full rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                placeholder="Tìm theo IP, thiết bị, trình duyệt..."
                @input="emit('update:search', ($event.target as HTMLInputElement).value)"
            />

            <select
                :value="props.filters.action"
                class="rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 md:w-[220px]"
                @change="emit('update:action', ($event.target as HTMLSelectElement).value)"
            >
                <option v-for="option in actionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-[10px] border border-slate-200/80">
            <div v-if="props.loading" class="bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">Đang tải lịch sử người dùng...</div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Hành động</th>
                            <th class="px-4 py-3">IP</th>
                            <th class="px-4 py-3">Thiết bị</th>
                            <th class="px-4 py-3">Trình duyệt</th>
                            <th class="px-4 py-3">Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="item in props.logs" :key="item.id" class="align-top">
                            <td class="px-4 py-3 font-medium text-slate-700">{{ formatTime(item.time, 'H:i:s d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ item.label }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ item.ip || '--' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.device }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ item.browser }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="getStatusClass(item.status)">
                                    {{ getStatusLabel(item.status) }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="props.logs.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Không có log nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Hiển thị {{ props.logs.length }} / {{ props.meta.total }} bản ghi</p>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5"
                    :class="props.meta.current_page <= 1 ? 'cursor-not-allowed text-slate-300' : 'text-slate-600'"
                    :disabled="props.meta.current_page <= 1"
                    @click="emit('change-page', props.meta.current_page - 1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <span class="rounded-[10px] bg-slate-900 px-3 py-1.5 font-semibold text-white">{{ props.meta.current_page }} / {{ props.meta.last_page }}</span>
                <button
                    type="button"
                    class="rounded-[10px] border border-slate-200 bg-white px-2.5 py-1.5"
                    :class="props.meta.current_page >= props.meta.last_page ? 'cursor-not-allowed text-slate-300' : 'text-slate-600'"
                    :disabled="props.meta.current_page >= props.meta.last_page"
                    @click="emit('change-page', props.meta.current_page + 1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
