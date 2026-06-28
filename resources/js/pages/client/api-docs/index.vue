<script setup lang="ts">
import { clientApiKeyService } from '@/services/client-api-key.service';
import type { ClientApiKeyType } from '@/types/api-key.type';
import { handleErrorResponse } from '@/utils/response';
import { Check, Copy, Eye, EyeOff, KeyRound, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const apiKey = ref<ClientApiKeyType | null>(null);
const apiSecret = ref<string | null>(null);
const loadingKey = ref(false);
const creatingKey = ref(false);
const rotatingSecret = ref(false);
const copiedField = ref<string | null>(null);
const revealSecret = ref(false);

const defaultPermissions = ['cron-jobs.read', 'cron-jobs.write', 'cron-logs.read'];

const apiKeyValue = computed(() => apiKey.value?.api_key ?? 'Chưa có API key');
const apiSecretDisplayValue = computed(() => {
    if (apiSecret.value === null) {
        return 'Secret chỉ hiển thị sau khi tạo mới hoặc đổi secret.';
    }

    if (revealSecret.value) {
        return apiSecret.value;
    }

    return '•'.repeat(Math.max(16, apiSecret.value.length));
});
const hasApiKey = computed(() => apiKey.value !== null);
const endpoints = [
    {
        title: 'Lấy danh sách cron',
        method: 'GET',
        path: '/api/v1/cron-jobs',
        description: 'Lấy danh sách cron jobs của tài khoản đang sở hữu API key.',
        example: `curl -X GET "https://your-domain.com/api/v1/cron-jobs?status=active&per_page=10" \\
  -H "X-API-KEY: ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -H "X-API-SECRET: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"`,
    },
    {
        title: 'Thêm cron mới',
        method: 'POST',
        path: '/api/v1/cron-jobs',
        description: 'Tạo mới một HTTP cron job.',
        example: `curl -X POST "https://your-domain.com/api/v1/cron-jobs" \\
  -H "Content-Type: application/json" \\
  -H "X-API-KEY: ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -H "X-API-SECRET: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -d '{
    "name": "Ping healthcheck",
    "group_name": "monitoring",
    "url": "https://example.com/health",
    "method": "GET",
    "body_type": "none",
    "interval_seconds": 300,
    "timezone": "Asia/Ho_Chi_Minh",
    "timeout_seconds": 10,
    "connect_timeout_seconds": 5,
    "retry_count": 0,
    "retry_delay_seconds": 30,
    "max_response_size_kb": 20,
    "follow_redirects": false,
    "verify_ssl": true
  }'`,
    },
    {
        title: 'Sửa cron',
        method: 'PATCH',
        path: '/api/v1/cron-jobs/{cron_job}',
        description: 'Cập nhật cấu hình cron hiện có.',
        example: `curl -X PATCH "https://your-domain.com/api/v1/cron-jobs/12" \\
  -H "Content-Type: application/json" \\
  -H "X-API-KEY: ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -H "X-API-SECRET: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -d '{
    "name": "Ping production",
    "url": "https://example.com/api/health",
    "method": "GET",
    "body_type": "none",
    "interval_seconds": 600,
    "timezone": "Asia/Ho_Chi_Minh"
  }'`,
    },
    {
        title: 'Xóa hoặc pause cron',
        method: 'DELETE / POST',
        path: '/api/v1/cron-jobs/{cron_job} hoặc /api/v1/cron-jobs/{cron_job}/pause',
        description: 'Dùng DELETE để xóa cron job, hoặc pause để tạm dừng cron.',
        example: `# Xóa cron
curl -X DELETE "https://your-domain.com/api/v1/cron-jobs/12" \\
  -H "X-API-KEY: ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -H "X-API-SECRET: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"

# Pause cron
curl -X POST "https://your-domain.com/api/v1/cron-jobs/12/pause" \\
  -H "X-API-KEY: ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -H "X-API-SECRET: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"`,
    },
    {
        title: 'Lấy log theo cron_id',
        method: 'GET',
        path: '/api/v1/cron-jobs/{cron_job}/logs',
        description: 'Lấy log chạy của một cron job cụ thể.',
        example: `curl -X GET "https://your-domain.com/api/v1/cron-jobs/12/logs?status=failed&per_page=20" \\
  -H "X-API-KEY: ak_xxxxxxxxxxxxxxxxxxxxxxxxxxxx" \\
  -H "X-API-SECRET: sk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"`,
    },
];

async function loadApiKey(): Promise<void> {
    try {
        loadingKey.value = true;
        const response = await clientApiKeyService.list();
        apiKey.value = response.data[0] ?? null;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loadingKey.value = false;
    }
}

async function createApiKey(): Promise<void> {
    try {
        creatingKey.value = true;
        const response = await clientApiKeyService.create({
            name: 'Primary AutoCron API key',
            permissions: defaultPermissions,
            ip_whitelist: ['*'],
        });

        apiKey.value = response.api_key;
        apiSecret.value = response.api_secret;
        revealSecret.value = false;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        creatingKey.value = false;
    }
}

async function rotateSecret(): Promise<void> {
    if (!apiKey.value) {
        return;
    }

    try {
        rotatingSecret.value = true;
        const response = await clientApiKeyService.rotate(apiKey.value.id);
        apiKey.value = response.api_key;
        apiSecret.value = response.api_secret;
        revealSecret.value = false;
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        rotatingSecret.value = false;
    }
}

async function copyValue(value: string, key: string): Promise<void> {
    if (!value || value.startsWith('Chưa có API key') || value.startsWith('Secret chỉ hiển thị')) {
        return;
    }

    try {
        await navigator.clipboard.writeText(value);
        copiedField.value = key;

        window.setTimeout(() => {
            if (copiedField.value === key) {
                copiedField.value = null;
            }
        }, 1500);
    } catch (error) {
        handleErrorResponse(error);
    }
}

onMounted(async () => {
    await loadApiKey();
});
</script>

<template>
    <div class="space-y-5 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-[linear-gradient(135deg,#0f172a_0%,#1e293b_45%,#1d4ed8_100%)] p-4 text-white shadow-[0_28px_60px_-40px_rgba(15,23,42,0.8)] sm:p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-200">AutoCron API</p>
            <h1 class="mt-3 text-2xl font-black tracking-[-0.04em] sm:text-3xl">Tài liệu API V1</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-200">
                Xác thực bằng header <code class="rounded bg-white/10 px-2 py-1">X-API-KEY</code> và
                <code class="rounded bg-white/10 px-2 py-1">X-API-SECRET</code>.
                Bộ tài liệu này tập trung vào quản lý cron jobs và log theo đúng luồng AutoCron hiện tại.
            </p>
        </section>

        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-950">Headers bắt buộc</h2>
                    <p class="mt-2 text-sm leading-7 text-slate-600">
                        Mỗi tài khoản chỉ có một cặp key duy nhất. <span class="font-semibold text-slate-900">API key</span> được giữ cố định để tránh gãy integration,
                        còn <span class="font-semibold text-slate-900">API secret</span> có thể đổi khi cần.
                    </p>
                </div>

                <button
                    v-if="!hasApiKey"
                    type="button"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-[10px] bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    :disabled="creatingKey || loadingKey"
                    @click="createApiKey"
                >
                    <KeyRound class="h-4 w-4" />
                    {{ creatingKey ? 'Đang tạo...' : 'Tạo cặp key' }}
                </button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">X-API-KEY</p>
                            <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ apiKeyValue }}</p>
                            <p class="mt-2 text-xs text-slate-500">Giữ cố định để integration không phải đổi cấu hình nhận diện.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex shrink-0 self-start items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700"
                            :disabled="!hasApiKey || loadingKey"
                            @click="copyValue(apiKeyValue, 'api-key')"
                        >
                            <Check v-if="copiedField === 'api-key'" class="h-3.5 w-3.5 text-emerald-600" />
                            <Copy v-else class="h-3.5 w-3.5" />
                            {{ copiedField === 'api-key' ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                </div>
                <div class="rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">X-API-SECRET</p>
                            <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ apiSecretDisplayValue }}</p>
                            <p class="mt-2 text-xs text-slate-500">Secret chỉ xem lại được ngay sau khi tạo mới hoặc đổi secret.</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 sm:flex-col sm:items-end">
                            <button
                                v-if="apiSecret !== null"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700"
                                @click="revealSecret = !revealSecret"
                            >
                                <EyeOff v-if="revealSecret" class="h-3.5 w-3.5" />
                                <Eye v-else class="h-3.5 w-3.5" />
                                {{ revealSecret ? 'Ẩn secret' : 'Hiện secret' }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700"
                                :disabled="apiSecret === null"
                                @click="copyValue(apiSecret ?? '', 'api-secret')"
                            >
                                <Check v-if="copiedField === 'api-secret'" class="h-3.5 w-3.5 text-emerald-600" />
                                <Copy v-else class="h-3.5 w-3.5" />
                                {{ copiedField === 'api-secret' ? 'Copied' : 'Copy' }}
                            </button>
                            <button
                                v-if="hasApiKey"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="rotatingSecret"
                                @click="rotateSecret"
                            >
                                <RefreshCw class="h-3.5 w-3.5" :class="rotatingSecret ? 'animate-spin' : ''" />
                                {{ rotatingSecret ? 'Đang đổi...' : 'Đổi secret' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4">
            <article
                v-for="endpoint in endpoints"
                :key="endpoint.title"
                class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-slate-950">{{ endpoint.title }}</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ endpoint.description }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap items-start gap-2">
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ endpoint.method }}</span>
                        <span class="break-all rounded-full bg-slate-100 px-3 py-1 font-mono text-xs text-slate-700">{{ endpoint.path }}</span>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto rounded-[10px] bg-slate-950 p-4">
                    <pre class="whitespace-pre-wrap break-words text-[11px] leading-6 text-slate-100 sm:text-xs">{{ endpoint.example }}</pre>
                </div>
            </article>
        </section>
    </div>
</template>
