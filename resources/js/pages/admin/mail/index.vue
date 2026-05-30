<script setup lang="ts">
import { adminMailService } from '@/services/admin-mail.service';
import type { AdminMailUserItem, AdminMailUserListResponse, AdminSendMailPayload } from '@/types/admin-mail.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const loadingUsers = ref(false);
const sending = ref(false);
const users = ref<AdminMailUserItem[]>([]);
const selectedUserIds = ref<number[]>([]);
const pagination = reactive<AdminMailUserListResponse['meta']>({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const filters = reactive({
    search: '',
    per_page: 10,
});

const form = reactive({
    recipient_type: 'users' as 'all' | 'users',
    subject: '',
    title: '',
    message: '',
    cta_text: '',
    cta_url: '',
});

const allChecked = computed(() => {
    if (users.value.length === 0) {
        return false;
    }

    return users.value.every((user) => selectedUserIds.value.includes(user.id));
});

const selectedCount = computed(() => selectedUserIds.value.length);

const fetchUsers = async (page = 1): Promise<void> => {
    try {
        loadingUsers.value = true;
        const response = await adminMailService.users({
            search: filters.search || undefined,
            per_page: filters.per_page,
            page,
        });
        users.value = response.data;
        pagination.current_page = response.meta.current_page;
        pagination.last_page = response.meta.last_page;
        pagination.per_page = response.meta.per_page;
        pagination.total = response.meta.total;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingUsers.value = false;
    }
};

const toggleSelectAll = (): void => {
    if (allChecked.value) {
        selectedUserIds.value = selectedUserIds.value.filter((id) => !users.value.some((user) => user.id === id));
        return;
    }

    const ids = users.value.map((user) => user.id);
    selectedUserIds.value = Array.from(new Set([...selectedUserIds.value, ...ids]));
};

const toggleUser = (userId: number): void => {
    if (selectedUserIds.value.includes(userId)) {
        selectedUserIds.value = selectedUserIds.value.filter((id) => id !== userId);
        return;
    }

    selectedUserIds.value = [...selectedUserIds.value, userId];
};

const submit = async (): Promise<void> => {
    try {
        sending.value = true;

        const payload: AdminSendMailPayload = {
            recipient_type: form.recipient_type,
            user_ids: form.recipient_type === 'users' ? selectedUserIds.value : [],
            subject: form.subject.trim(),
            title: form.title.trim(),
            message: form.message.trim(),
            cta_text: form.cta_text.trim() || null,
            cta_url: form.cta_url.trim() || null,
        };

        const result = await adminMailService.send(payload);

        handleSuccessResponse({
            data: {
                status: true,
                message: `Đã xếp hàng ${result.queued} email${result.skipped > 0 ? `, bỏ qua ${result.skipped}` : ''}.`,
            },
        });

        if (form.recipient_type === 'users') {
            selectedUserIds.value = [];
        }
        form.subject = '';
        form.title = '';
        form.message = '';
        form.cta_text = '';
        form.cta_url = '';
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        sending.value = false;
    }
};

watch(
    () => filters.search,
    async () => {
        await fetchUsers(1);
    },
);

watch(
    () => filters.per_page,
    async () => {
        await fetchUsers(1);
    },
);

onMounted(async () => {
    await fetchUsers(1);
});
</script>

<template>
    <div class="space-y-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-xl font-bold text-slate-950">Gửi mail người dùng</h1>
            <p class="mt-1 text-sm text-slate-500">Gửi mail hàng loạt hoặc theo danh sách user cụ thể. Mail sẽ được xử lý qua queue `mails`.</p>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <input
                        v-model="filters.search"
                        type="text"
                        class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400 sm:w-[320px]"
                        placeholder="Tìm user theo id, username, email, phone..."
                    />
                    <select
                        v-model.number="filters.per_page"
                        class="rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                    >
                        <option :value="10">10 / trang</option>
                        <option :value="20">20 / trang</option>
                        <option :value="50">50 / trang</option>
                    </select>
                </div>

                <div class="overflow-x-auto rounded-[10px] border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <input type="checkbox" :checked="allChecked" @change="toggleSelectAll" />
                                </th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">User</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Email</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-if="loadingUsers">
                                <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">Đang tải người dùng...</td>
                            </tr>
                            <tr v-else-if="users.length === 0">
                                <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">Không có dữ liệu người dùng.</td>
                            </tr>
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-3 py-2">
                                    <input type="checkbox" :checked="selectedUserIds.includes(user.id)" @change="toggleUser(user.id)" />
                                </td>
                                <td class="px-3 py-2 text-sm text-slate-800">
                                    <p class="font-semibold">{{ user.full_name || user.username }}</p>
                                    <p class="text-xs text-slate-500">#{{ user.id }} · {{ user.username }}</p>
                                </td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ user.email || '-' }}</td>
                                <td class="px-3 py-2 text-sm text-slate-700">{{ user.status || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                    <span>Đã chọn: {{ selectedCount }} user</span>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 px-2 py-1 hover:bg-slate-50 disabled:opacity-50"
                            :disabled="pagination.current_page <= 1"
                            @click="fetchUsers(pagination.current_page - 1)"
                        >
                            Trước
                        </button>
                        <span>Trang {{ pagination.current_page }}/{{ pagination.last_page }}</span>
                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 px-2 py-1 hover:bg-slate-50 disabled:opacity-50"
                            :disabled="pagination.current_page >= pagination.last_page"
                            @click="fetchUsers(pagination.current_page + 1)"
                        >
                            Sau
                        </button>
                    </div>
                </div>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <form class="space-y-3" @submit.prevent="submit">
                    <label class="block space-y-1">
                        <span class="text-sm font-semibold text-slate-700">Đối tượng nhận</span>
                        <select
                            v-model="form.recipient_type"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                        >
                            <option value="users">User đã chọn</option>
                            <option value="all">Tất cả user có email</option>
                        </select>
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-semibold text-slate-700">Subject</span>
                        <input
                            v-model="form.subject"
                            type="text"
                            maxlength="255"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            required
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-semibold text-slate-700">Heading</span>
                        <input
                            v-model="form.title"
                            type="text"
                            maxlength="255"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            required
                        />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-semibold text-slate-700">Nội dung</span>
                        <textarea
                            v-model="form.message"
                            rows="6"
                            maxlength="5000"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                            required
                        />
                    </label>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <label class="block space-y-1">
                            <span class="text-sm font-semibold text-slate-700">Nút CTA (tuỳ chọn)</span>
                            <input
                                v-model="form.cta_text"
                                type="text"
                                maxlength="100"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                                placeholder="Ví dụ: Mở dashboard"
                            />
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-semibold text-slate-700">Link CTA (tuỳ chọn)</span>
                            <input
                                v-model="form.cta_url"
                                type="url"
                                maxlength="500"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400"
                                placeholder="https://..."
                            />
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="sending || (form.recipient_type === 'users' && selectedCount === 0)"
                    >
                        {{ sending ? 'Đang đưa vào queue...' : 'Gửi mail' }}
                    </button>
                </form>
            </article>
        </section>
    </div>
</template>
