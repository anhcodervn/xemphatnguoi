<script setup lang="ts">
import { useSystemSetting } from '@/composables/useSystemSetting';
import { clientContactService } from '@/services/client-contact.service';
import { useUserStore } from '@/stores/user.store';
import { handleErrorResponse } from '@/utils/response';
import type { AxiosError } from 'axios';
import { Mail, MessageSquare, Phone } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';
import Swal from 'sweetalert2';

type ValidationErrors = Record<string, string[]>;

const userStore = useUserStore();
const { settings, fetchSettings } = useSystemSetting();
const submitting = ref(false);

const form = reactive({
    name: '',
    email: '',
    phone: '',
    subject: '',
    content: '',
});

const errors = reactive({
    name: '',
    email: '',
    phone: '',
    subject: '',
    content: '',
});

const clearErrors = (): void => {
    errors.name = '';
    errors.email = '';
    errors.phone = '';
    errors.subject = '';
    errors.content = '';
};

const applyValidationErrors = (payload?: ValidationErrors): void => {
    clearErrors();
    if (!payload) {
        return;
    }

    errors.name = payload.name?.[0] ?? '';
    errors.email = payload.email?.[0] ?? '';
    errors.phone = payload.phone?.[0] ?? '';
    errors.subject = payload.subject?.[0] ?? '';
    errors.content = payload.content?.[0] ?? '';
};

const submitFeedback = async (): Promise<void> => {
    clearErrors();
    submitting.value = true;

    try {
        await clientContactService.submitFeedback({
            name: form.name.trim() || null,
            email: form.email.trim() || null,
            phone: form.phone.trim() || null,
            subject: form.subject.trim(),
            content: form.content.trim(),
        });

        form.subject = '';
        form.content = '';
        await Swal.fire('Đã gửi', 'Cảm ơn bạn đã gửi góp ý.', 'success');
    } catch (error) {
        const axiosError = error as AxiosError<{ errors?: ValidationErrors; message?: string }>;
        applyValidationErrors(axiosError.response?.data?.errors);
        handleErrorResponse(error);
    } finally {
        submitting.value = false;
    }
};

onMounted(async () => {
    if (!userStore.user) {
        await userStore.bootstrap({ silent: true });
    }

    await fetchSettings();

    form.name = userStore.user?.full_name ?? userStore.user?.username ?? '';
    form.email = userStore.user?.email ?? '';
    form.phone = userStore.user?.phone ?? '';
});
</script>

<template>
    <div class="space-y-3 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-lg font-bold text-slate-900">Liên hệ & góp ý</h1>
            <p class="mt-1 text-sm text-slate-500">Gửi phản hồi để admin hỗ trợ hoặc cải thiện dịch vụ.</p>
        </section>

        <section class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_280px]">
            <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                <form class="space-y-3" @submit.prevent="submitFeedback">
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Họ tên</span>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                                placeholder="Nhập họ tên"
                            />
                            <p v-if="errors.name" class="text-xs text-rose-500">{{ errors.name }}</p>
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Email</span>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                                placeholder="you@example.com"
                            />
                            <p v-if="errors.email" class="text-xs text-rose-500">{{ errors.email }}</p>
                        </label>
                    </div>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold text-slate-600">Số điện thoại</span>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            placeholder="Nhập số điện thoại"
                        />
                        <p v-if="errors.phone" class="text-xs text-rose-500">{{ errors.phone }}</p>
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold text-slate-600">Tiêu đề</span>
                        <input
                            v-model="form.subject"
                            type="text"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            placeholder="Ví dụ: Góp ý giao diện / Báo lỗi giao dịch"
                        />
                        <p v-if="errors.subject" class="text-xs text-rose-500">{{ errors.subject }}</p>
                    </label>

                    <label class="space-y-1">
                        <span class="text-xs font-semibold text-slate-600">Nội dung góp ý</span>
                        <textarea
                            v-model="form.content"
                            rows="6"
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                            placeholder="Mô tả chi tiết nội dung cần hỗ trợ..."
                        />
                        <p v-if="errors.content" class="text-xs text-rose-500">{{ errors.content }}</p>
                    </label>

                    <button
                        type="submit"
                        class="inline-flex rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Đang gửi...' : 'Gửi góp ý' }}
                    </button>
                </form>
            </article>

            <article class="rounded-[10px] border border-slate-200 bg-slate-50 p-4 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">Thông tin hỗ trợ</h2>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <p class="flex items-center gap-2"><Mail class="h-4 w-4 text-indigo-500" /> {{ settings.support_email || form.email || 'support@system.local' }}</p>
                    <p class="flex items-center gap-2"><Phone class="h-4 w-4 text-indigo-500" /> {{ settings.hotline || form.phone || 'Chưa cập nhật số điện thoại' }}</p>
                    <p class="flex items-center gap-2"><MessageSquare class="h-4 w-4 text-indigo-500" /> Admin sẽ phản hồi theo thứ tự gửi.</p>
                </div>
            </article>
        </section>
    </div>
</template>
