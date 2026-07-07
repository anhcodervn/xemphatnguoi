<script setup lang="ts">
import { clientApiKeyService } from '@/services/client-api-key.service';
import { clientCaptchaService, type ClientCaptchaServiceItem } from '@/services/client-captcha.service';
import type { ClientApiKeyType } from '@/types/api-key.type';
import { handleErrorResponse } from '@/utils/response';
import { ChevronDown, ChevronUp, Eye, EyeOff, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';

type ExampleBlock = {
    key: string;
    label: string;
    serviceCode: string;
    description: string;
    requestExample: string;
};

type EndpointDoc = {
    key: string;
    method: string;
    path: string;
    title: string;
    note: string;
    requestExample: string;
    responseExample: string;
};

const apiKey = ref<ClientApiKeyType | null>(null);
const apiSecret = ref<string | null>(null);
const revealSecret = ref(false);
const services = ref<ClientCaptchaServiceItem[]>([]);
const activeCreateExample = ref('recaptcha-v2-token');

const defaultPermissions = ['captcha-services.read', 'captcha-tasks.create', 'captcha-tasks.read'];

const requestBodyFallbackMap: Record<string, string> = {
    'recaptcha-v2-token': `{
  "website_url": "https://example.com/login",
  "website_key": "6Ldemo_site_key"
}`,
    'recaptcha-v3-token': `{
  "website_url": "https://example.com/checkout",
  "website_key": "6Ldemo_v3_site_key",
  "action": "submit",
  "min_score": 0.3
}`,
    'image-base64': `{
  "body": "iVBORw0KGgoAAAANSUhEUgAA...",
  "case_sensitive": false,
  "numeric": 0
}`,
    'cloudflare-turnstile': `{
  "website_url": "https://example.com/register",
  "website_key": "0x4AAAAA-demo-turnstile-key"
}`,
    'geetest-v4': `{
  "website_url": "https://example.com/auth",
  "captcha_id": "54088bb07d814dd7b1caffcc"
}`,
};

const descriptionFallbackMap: Record<string, string> = {
    'recaptcha-v2-token': 'Token reCAPTCHA v2.',
    'recaptcha-v3-token': 'Token reCAPTCHA v3.',
    'image-base64': 'Ảnh captcha base64.',
    'cloudflare-turnstile': 'Token Cloudflare Turnstile.',
    'geetest-v4': 'GeeTest v4.',
};

const createTaskResponseExample = `{
  "status": true,
  "message": "Tạo task thành công.",
  "data": {
    "task_code": "ct_xxxxxxxxxxxxxxxxxxxxxxxx",
    "task_data": {
      "...": "payload user đã gửi lên"
    }
  }
}`;

const endpoints: EndpointDoc[] = [
    {
        key: 'result',
        method: 'POST',
        path: '/api/v1/result',
        title: 'Kiểm tra task',
        note: 'Trả về captcha đã giải.',
        requestExample: `curl -X POST "https://giapcaptcha.vn/api/v1/result" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: ak_xxx" \\
  -H "X-API-SECRET: sk_xxx" \\
  -d '{
    "task_code": "ct_xxx"
  }'`,
        responseExample: `{
  "status": true,
  "data": {
    "task_code": "ct_xxx",
    "captcha": "03AFcWeA7..."
  }
}`,
    },
    {
        key: 'user',
        method: 'GET',
        path: '/api/v1/user',
        title: 'Thông tin tài khoản',
        note: 'Thông tin user, ví và API key.',
        requestExample: `curl "https://giapcaptcha.vn/api/v1/user" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: ak_xxx" \\
  -H "X-API-SECRET: sk_xxx"`,
        responseExample: `{
  "status": true,
  "data": {
    "user": {
      "id": 12,
      "username": "demo_user",
      "full_name": "Demo User",
      "email": "demo@example.com",
      "status": "active",
      "role": "user",
      "created_at": "2026-07-07T03:15:00.000000Z"
    },
    "wallet": {
      "balance": "1500",
      "hold_balance": "0",
      "total_recharge": "5000",
      "total_spent": "3500"
    },
    "api_key": {
      "id": 3,
      "name": "Primary Captcha API key",
      "api_key": "ak_xxx",
      "status": "active",
      "permissions": [
        "captcha-services.read",
        "captcha-tasks.create",
        "captcha-tasks.read"
      ],
      "ip_whitelist": [
        "*"
      ],
      "last_used_at": "2026-07-07T03:18:00.000000Z",
      "expired_at": null,
      "created_at": "2026-07-07T03:16:00.000000Z"
    },
    "task_stats": {
      "total_tasks": 120,
      "pending_tasks": 4,
      "solved_tasks": 112,
      "failed_tasks": 4
    }
  }
}`,
    },
    {
        key: 'services',
        method: 'GET',
        path: '/api/v1/services',
        title: 'Danh sách dịch vụ',
        note: 'Danh sách service đang mở.',
        requestExample: `curl "https://giapcaptcha.vn/api/v1/services" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: ak_xxx" \\
  -H "X-API-SECRET: sk_xxx"`,
        responseExample: `{
  "status": true,
  "data": {
    "services": [
      {
        "id": 1,
        "code": "recaptcha-v2-token",
        "name": "reCAPTCHA v2 Token",
        "category": "recaptcha",
        "description": "Dịch vụ token cho website sử dụng reCAPTCHA v2.",
        "selling_price": "15",
        "estimated_seconds": 8,
        "is_active": true,
        "settings": {
          "icon_url": "https://giapcaptcha.vn/storage/captcha-icons/recaptcha-v2.png"
        },
        "stats": {
          "success_rate": 98,
          "processing_time_label": "8s",
          "avg_processing_seconds": 8
        }
      }
    ]
  }
}`,
    },
    {
        key: 'balance',
        method: 'GET',
        path: '/api/v1/balance',
        title: 'Số dư',
        note: 'Số dư ví hiện tại.',
        requestExample: `curl "https://giapcaptcha.vn/api/v1/balance" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: ak_xxx" \\
  -H "X-API-SECRET: sk_xxx"`,
        responseExample: `{
  "status": true,
  "data": {
    "balance": "1500",
    "hold_balance": "0"
  }
}`,
    },
];

const expandedSections = reactive<Record<string, boolean>>({
    createTaskRequest: true,
    createTaskResponse: true,
    'result-request': true,
    'result-response': true,
    'user-request': false,
    'user-response': false,
    'services-request': false,
    'services-response': false,
    'balance-request': false,
    'balance-response': false,
});

const createTaskExamples = computed<ExampleBlock[]>(() => {
    if (services.value.length === 0) {
        return Object.entries(requestBodyFallbackMap).map(([serviceCode, body]) => ({
            key: serviceCode,
            label: serviceCode,
            serviceCode,
            description: descriptionFallbackMap[serviceCode] ?? 'Payload mẫu.',
            requestExample: buildCreateRequestExample(serviceCode, body),
        }));
    }

    return services.value.map((service) => {
        const body =
            normalizeExampleBody(service.settings?.request_example_body) ??
            requestBodyFallbackMap[service.code] ??
            `{
  "website_url": "https://example.com",
  "website_key": "your_site_key"
}`;

        return {
            key: service.code,
            label: service.name,
            serviceCode: service.code,
            description: service.description || descriptionFallbackMap[service.code] || 'Payload mẫu.',
            requestExample: buildCreateRequestExample(service.code, body),
        };
    });
});

const activeCreateTask = computed(
    () => createTaskExamples.value.find((item) => item.key === activeCreateExample.value) ?? createTaskExamples.value[0] ?? null,
);

const maskedSecret = computed(() => {
    if (!apiSecret.value) {
        return 'Secret chỉ hiển thị sau khi tạo hoặc đổi mới.';
    }

    return revealSecret.value ? apiSecret.value : '•'.repeat(Math.max(16, apiSecret.value.length));
});

function buildCreateRequestExample(serviceCode: string, taskBody: string): string {
    return `curl -X POST "https://giapcaptcha.vn/api/v1/create" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: ak_xxx" \\
  -H "X-API-SECRET: sk_xxx" \\
  -d '{
    "service_code": "${serviceCode}",
    "task": ${indentJsonBlock(taskBody, 4)}
  }'`;
}

function indentJsonBlock(value: string, spaces: number): string {
    const pad = ' '.repeat(spaces);

    return value
        .trim()
        .split('\n')
        .map((line) => `${pad}${line}`)
        .join('\n');
}

function normalizeExampleBody(value?: string | null): string | null {
    if (typeof value !== 'string') {
        return null;
    }

    const normalized = value.trim();

    return normalized !== '' ? normalized : null;
}

const loadApiKey = async (): Promise<void> => {
    try {
        const response = await clientApiKeyService.list();
        apiKey.value = response.data.find((item) => item.key_type === 'wallet') ?? response.data[0] ?? null;
    } catch (error) {
        handleErrorResponse(error);
    }
};

const loadServices = async (): Promise<void> => {
    try {
        const response = await clientCaptchaService.services();
        services.value = response.services ?? [];

        if (services.value.length > 0) {
            activeCreateExample.value = services.value[0]?.code ?? activeCreateExample.value;
        }
    } catch (error) {
        handleErrorResponse(error);
    }
};

const createApiKey = async (): Promise<void> => {
    try {
        const response = await clientApiKeyService.create({
            name: 'Wallet API key',
            permissions: defaultPermissions,
            ip_whitelist: ['*'],
        });

        apiKey.value = response.api_key;
        apiSecret.value = response.api_secret;
    } catch (error) {
        handleErrorResponse(error);
    }
};

const rotateSecret = async (): Promise<void> => {
    if (!apiKey.value) {
        return;
    }

    try {
        const response = await clientApiKeyService.rotate(apiKey.value.id);
        apiKey.value = response.api_key;
        apiSecret.value = response.api_secret;
        revealSecret.value = false;
    } catch (error) {
        handleErrorResponse(error);
    }
};

const copyText = async (value: string): Promise<void> => {
    if (!value) {
        return;
    }

    await navigator.clipboard.writeText(value);
};

const toggleSection = (key: string): void => {
    expandedSections[key] = !expandedSections[key];
};

onMounted(async () => {
    await Promise.all([loadApiKey(), loadServices()]);
});
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-[16px] border border-teal-200 bg-[linear-gradient(135deg,#082f49_0%,#0f766e_48%,#06b6d4_100%)] p-6 text-white shadow-[0_24px_60px_-32px_rgba(8,145,178,0.45)]">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-100">API Documentation</p>
            <h1 class="mt-3 text-3xl font-black tracking-[-0.05em]">API Captcha</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-teal-50/90">
                3 bước: lấy service, tạo task, kiểm tra kết quả.
            </p>
        </section>

        <section class="rounded-[14px] border border-teal-100 bg-white/95 p-5 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Xác thực</h2>
                    <p class="mt-2 text-sm text-slate-600">Dùng `X-API-KEY` và `X-API-SECRET` cho mọi request.</p>
                </div>
                <button v-if="!apiKey" type="button" class="rounded-[10px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500" @click="createApiKey">
                    Tạo API key
                </button>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-[10px] border border-teal-100 bg-teal-50/70 px-4 py-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">X-API-KEY</p>
                    <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ apiKey?.api_key || 'Chưa có API key' }}</p>
                    <button v-if="apiKey?.api_key" type="button" class="mt-3 text-xs font-semibold text-teal-700" @click="copyText(apiKey.api_key)">Copy</button>
                </div>

                <div class="rounded-[10px] border border-teal-100 bg-teal-50/70 px-4 py-4">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">X-API-SECRET</p>
                        <button v-if="apiSecret" type="button" class="text-slate-500" @click="revealSecret = !revealSecret">
                            <Eye v-if="!revealSecret" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <p class="mt-2 break-all font-mono text-sm text-slate-900">{{ maskedSecret }}</p>

                    <div class="mt-3 flex gap-3 text-xs font-semibold">
                        <button v-if="apiSecret" type="button" class="text-teal-700" @click="copyText(apiSecret)">Copy</button>
                        <button v-if="apiKey" type="button" class="inline-flex items-center gap-1 text-slate-700" @click="rotateSecret">
                            <RefreshCw class="h-3.5 w-3.5" />
                            Đổi secret
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[14px] border border-teal-100 bg-white/95 p-5 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Tạo task</h2>
                    <p class="mt-1 text-sm text-slate-500">Chọn service và copy request.</p>
                </div>
                <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">POST /api/v1/create</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button
                    v-for="item in createTaskExamples"
                    :key="item.key"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-semibold transition"
                    :class="
                        activeCreateExample === item.key
                            ? 'bg-teal-600 text-white'
                            : 'border border-teal-100 bg-white text-teal-700 hover:bg-teal-50'
                    "
                    @click="activeCreateExample = item.key"
                >
                    {{ item.label }}
                </button>
            </div>

            <div v-if="activeCreateTask" class="mt-4 rounded-[10px] border border-teal-100 bg-teal-50/70 px-4 py-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ activeCreateTask.label }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ activeCreateTask.description }}</p>
                    </div>
                    <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-teal-700 ring-1 ring-teal-100">
                        {{ activeCreateTask.serviceCode }}
                    </div>
                </div>
            </div>

            <div class="mt-4 space-y-4">
                <div class="overflow-hidden rounded-[10px] border border-teal-100">
                    <button type="button" class="flex w-full items-center justify-between bg-teal-50/80 px-4 py-3 text-left" @click="toggleSection('createTaskRequest')">
                        <span class="text-sm font-semibold text-teal-900">Request</span>
                        <component :is="expandedSections.createTaskRequest ? ChevronUp : ChevronDown" class="h-4 w-4 text-slate-500" />
                    </button>
                    <div v-if="expandedSections.createTaskRequest && activeCreateTask" class="overflow-x-auto bg-slate-950 p-4">
                        <pre class="whitespace-pre-wrap break-words text-[11px] leading-6 text-slate-100">{{ activeCreateTask.requestExample }}</pre>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[10px] border border-teal-100">
                    <button type="button" class="flex w-full items-center justify-between bg-teal-50/80 px-4 py-3 text-left" @click="toggleSection('createTaskResponse')">
                        <span class="text-sm font-semibold text-teal-900">Response</span>
                        <component :is="expandedSections.createTaskResponse ? ChevronUp : ChevronDown" class="h-4 w-4 text-slate-500" />
                    </button>
                    <div v-if="expandedSections.createTaskResponse" class="overflow-x-auto bg-slate-950 p-4">
                        <pre class="whitespace-pre-wrap break-words text-[11px] leading-6 text-teal-100">{{ createTaskResponseExample }}</pre>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4">
            <article v-for="endpoint in endpoints" :key="endpoint.key" class="rounded-[14px] border border-teal-100 bg-white/95 p-5 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">{{ endpoint.title }}</h2>
                        <p class="mt-2 text-sm font-medium text-slate-600">{{ endpoint.path }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ endpoint.note }}</p>
                    </div>
                    <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700">{{ endpoint.method }}</span>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="overflow-hidden rounded-[10px] border border-teal-100">
                        <button type="button" class="flex w-full items-center justify-between bg-teal-50/80 px-4 py-3 text-left" @click="toggleSection(`${endpoint.key}-request`)">
                            <span class="text-sm font-semibold text-teal-900">Request</span>
                            <component :is="expandedSections[`${endpoint.key}-request`] ? ChevronUp : ChevronDown" class="h-4 w-4 text-slate-500" />
                        </button>
                        <div v-if="expandedSections[`${endpoint.key}-request`]" class="overflow-x-auto bg-slate-950 p-4">
                            <pre class="whitespace-pre-wrap break-words text-[11px] leading-6 text-slate-100">{{ endpoint.requestExample }}</pre>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-[10px] border border-teal-100">
                        <button type="button" class="flex w-full items-center justify-between bg-teal-50/80 px-4 py-3 text-left" @click="toggleSection(`${endpoint.key}-response`)">
                            <span class="text-sm font-semibold text-teal-900">Response</span>
                            <component :is="expandedSections[`${endpoint.key}-response`] ? ChevronUp : ChevronDown" class="h-4 w-4 text-slate-500" />
                        </button>
                        <div v-if="expandedSections[`${endpoint.key}-response`]" class="overflow-x-auto bg-slate-950 p-4">
                            <pre class="whitespace-pre-wrap break-words text-[11px] leading-6 text-teal-100">{{ endpoint.responseExample }}</pre>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
