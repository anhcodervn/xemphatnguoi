<script setup lang="ts">
import { clientCronAlertService, type CronAlertPayload } from '@/services/client-cron-alert.service';
import type { CronAlertChannelItem } from '@/services/client-cron-job.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { BellRing, Plus, Send, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const saving = ref(false);
const rows = ref<CronAlertChannelItem[]>([]);
const form = reactive<CronAlertPayload>({
    name: '',
    type: 'discord',
    target_url: '',
    telegram_bot_token: '',
    telegram_chat_id: '',
    email: '',
    events: ['on_fail', 'on_recovered'],
    is_enabled: true,
});

const loadChannels = async (): Promise<void> => {
    loading.value = true;

    try {
        const response = await clientCronAlertService.list({ per_page: 50 });
        rows.value = response.data;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const resetForm = (): void => {
    form.name = '';
    form.type = 'discord';
    form.target_url = '';
    form.telegram_bot_token = '';
    form.telegram_chat_id = '';
    form.email = '';
    form.events = ['on_fail', 'on_recovered'];
    form.is_enabled = true;
};

const submit = async (): Promise<void> => {
    saving.value = true;

    try {
        const response = await clientCronAlertService.create(form);
        handleSuccessResponse(response, 'Đã tạo alert channel.');
        resetForm();
        await loadChannels();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const testChannel = async (channel: CronAlertChannelItem): Promise<void> => {
    try {
        const response = await clientCronAlertService.test(channel.id);
        handleSuccessResponse(response, 'Đã gửi test alert.');
    } catch (error) {
        handleErrorResponse(error);
    }
};

const deleteChannel = async (channel: CronAlertChannelItem): Promise<void> => {
    const confirmed = await Swal.fire({
        icon: 'warning',
        title: 'Xóa alert channel?',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
    });

    if (!confirmed.isConfirmed) {
        return;
    }

    try {
        const response = await clientCronAlertService.delete(channel.id);
        handleSuccessResponse(response, 'Đã xóa alert channel.');
        await loadChannels();
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(async () => {
    await loadChannels();
});
</script>

<template>
    <div class="space-y-5 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[10px] bg-slate-950 text-white">
                    <BellRing class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-3xl font-black tracking-[-0.04em] text-slate-950">Alert Channels</h1>
                    <p class="mt-2 text-sm leading-7 text-slate-600">Cấu hình Discord, Telegram, Webhook hoặc Email để nhận cảnh báo khi cron job lỗi hoặc hồi phục.</p>
                </div>
            </div>

            <form class="mt-5 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
                <label class="space-y-1.5">
                    <span class="text-sm font-semibold text-slate-700">Tên channel</span>
                    <input v-model="form.name" type="text" placeholder="Ví dụ: Discord lỗi production" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                </label>
                <label class="space-y-1.5">
                    <span class="text-sm font-semibold text-slate-700">Loại kênh</span>
                    <select v-model="form.type" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                        <option value="discord">Discord</option>
                        <option value="telegram">Telegram</option>
                        <option value="webhook">Webhook</option>
                        <option value="email">Email</option>
                    </select>
                </label>
                <label class="space-y-1.5 md:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Target URL</span>
                    <input v-model="form.target_url" type="text" placeholder="Webhook URL hoặc endpoint nhận cảnh báo" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                </label>
                <label class="space-y-1.5">
                    <span class="text-sm font-semibold text-slate-700">Telegram bot token</span>
                    <input v-model="form.telegram_bot_token" type="text" placeholder="Dùng khi loại kênh là Telegram" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                </label>
                <label class="space-y-1.5">
                    <span class="text-sm font-semibold text-slate-700">Telegram chat ID</span>
                    <input v-model="form.telegram_chat_id" type="text" placeholder="Ví dụ: -1001234567890" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                </label>
                <label class="space-y-1.5 md:col-span-2">
                    <span class="text-sm font-semibold text-slate-700">Email nhận cảnh báo</span>
                    <input v-model="form.email" type="email" placeholder="ops@example.com" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                </label>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-[10px] bg-slate-950 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 md:col-span-2" :disabled="saving">
                    <Plus class="h-4 w-4" />
                    {{ saving ? 'Đang lưu...' : 'Tạo channel' }}
                </button>
            </form>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <div v-if="loading" class="text-sm text-slate-500">Đang tải alert channels...</div>
            <div v-else class="grid gap-3">
                <article v-for="channel in rows" :key="channel.id" class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <p class="break-words text-base font-bold text-slate-950">{{ channel.name }}</p>
                            <p class="mt-1 break-all text-sm text-slate-500">{{ channel.type }} • {{ channel.target_url || channel.email || channel.telegram_chat_id || 'Target riêng' }}</p>
                            <p class="mt-2 break-words text-xs text-slate-400">Events: {{ channel.events.join(', ') }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="inline-flex items-center gap-1 rounded-[8px] border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50" @click="testChannel(channel)">
                                <Send class="h-3.5 w-3.5" />
                                Test
                            </button>
                            <button type="button" class="inline-flex items-center gap-1 rounded-[8px] border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" @click="deleteChannel(channel)">
                                <Trash2 class="h-3.5 w-3.5" />
                                Xóa
                            </button>
                        </div>
                    </div>
                </article>
                <div v-if="rows.length === 0" class="rounded-[10px] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                    Chưa có alert channel nào.
                </div>
            </div>
        </section>
    </div>
</template>
