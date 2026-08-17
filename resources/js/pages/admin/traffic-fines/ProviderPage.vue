<script setup lang="ts">
import { adminTrafficFineService, type AdminProviderStatus } from '@/services/admin-traffic-fine.service';
import { onMounted, ref } from 'vue';
const provider = ref<AdminProviderStatus | null>(null);
const loading = ref(true);
onMounted(async () => {
    try {
        provider.value = await adminTrafficFineService.provider();
    } finally {
        loading.value = false;
    }
});
</script>
<template>
    <div class="mx-auto max-w-3xl">
        <header>
            <p class="text-sm font-bold text-sky-700">Provider</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Cấu hình nguồn dữ liệu</h1>
            <p class="mt-2 text-sm text-slate-500">Secret chỉ được đọc từ ENV và không hiển thị trên giao diện.</p>
        </header>
        <div v-if="loading" class="mt-7 h-64 animate-pulse rounded-xl bg-slate-200" />
        <section v-else-if="provider" class="mt-7 rounded-xl border border-slate-200 bg-white p-6">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-slate-500">Tên</dt>
                    <dd class="mt-1 font-bold">{{ provider.name }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Trạng thái</dt>
                    <dd class="mt-1 font-bold">{{ provider.status }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Priority</dt>
                    <dd class="mt-1 font-bold">{{ provider.priority }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Timeout</dt>
                    <dd class="mt-1 font-bold">{{ provider.timeout }} giây</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">API URL</dt>
                    <dd class="mt-1 font-bold">{{ provider.url_configured ? 'Đã cấu hình' : 'Chưa cấu hình' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-slate-500">Credential</dt>
                    <dd class="mt-1 font-bold">{{ provider.credential_configured ? 'Đã cấu hình' : 'Chưa cấu hình' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-slate-500">Lỗi gần nhất</dt>
                    <dd class="mt-1 font-bold">
                        {{ provider.last_error ? new Date(provider.last_error).toLocaleString('vi-VN') : 'Chưa ghi nhận lỗi' }}
                    </dd>
                </div>
            </dl>
            <p class="mt-6 rounded-lg bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Credential chỉ được cấu hình trực tiếp trên server. UI không nhận hoặc trả về giá trị secret.
            </p>
        </section>
    </div>
</template>
