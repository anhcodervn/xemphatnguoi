<script setup lang="ts">
import { clientApiKeyService } from '@/services/client-api-key.service';
import type { ApiKeyPermissionType, ClientApiKeyType } from '@/types/api-key.type';
import type { AxiosError } from 'axios';
import { AlertTriangle, CheckCircle2, Code2, Copy, KeyRound, RefreshCw, RotateCw, ShoppingCart } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';

interface EndpointDoc {
    key: string;
    method: 'GET' | 'POST';
    path: string;
    permission: string;
    title: string;
    description: string;
    body?: Record<string, unknown>;
    response: Record<string, unknown>;
}

const baseUrl = window.location.origin;
const endpoints: EndpointDoc[] = [
    {
        key: 'products',
        method: 'GET',
        path: '/api/v1/proxy/products',
        permission: 'proxy-products.read',
        title: 'Danh sách sản phẩm',
        description: 'Lấy productCode, giá bán, giao thức và giới hạn số lượng trước khi tạo đơn.',
        response: {
            status: true,
            data: {
                products: [{ code: 'proxy-vn-static', selling_price: '1200.0000', supported_protocols: ['http', 'socks5'] }],
            },
        },
    },
    {
        key: 'order',
        method: 'POST',
        path: '/api/v1/proxy/orders',
        permission: 'proxy-orders.create',
        title: 'Mua proxy',
        description: 'Mỗi phần tử luôn có cả key và proxy. Proxy tĩnh có key=null; proxy xoay có proxy=null. Mã giao dịch do backend quản lý.',
        body: {
            productCode: 'proxy-vn-static',
            quantity: 1,
            durationDays: 30,
            protocol: 'http',
        },
        response: {
            status: true,
            message: 'Mua proxy thành công.',
            orderCode: 'PXY-01KZN32WQYHMWX1CWGMH74PQ23',
            proxy: [
                {
                    id: 42,
                    protocol: 'http',
                    key: null,
                    proxy: '127.0.0.1:8080:user:password',
                    country_code: 'VN',
                    expired_at: '2026-09-09T05:42:17.000000Z',
                },
            ],
        },
    },
    {
        key: 'change',
        method: 'POST',
        path: '/api/v1/proxy/change',
        permission: 'proxy-operations.write',
        title: 'Đổi proxy tĩnh',
        description: 'orderCode là ID của proxy trong user_proxies. API gọi trực tiếp provider và chỉ trả kết quả sau khi đổi xong.',
        body: {
            orderCode: 42,
        },
        response: {
            status: true,
            message: 'Đổi proxy thành công.',
            products: [{ id: 42, proxy: '127.0.0.1:8080:user:password' }],
        },
    },
    {
        key: 'renew',
        method: 'POST',
        path: '/api/v1/proxy/renew',
        permission: 'proxy-operations.write',
        title: 'Gia hạn proxy',
        description:
            'Dùng được cho proxy tĩnh và xoay. API gọi trực tiếp provider; orderCode là ID user_proxies, durationDays là số ngày cần cộng thêm.',
        body: {
            orderCode: 42,
            durationDays: 30,
        },
        response: {
            status: true,
            message: 'Gia hạn proxy thành công.',
            products: [{ id: 42, expired_at: '2026-09-09T05:42:17.000000Z' }],
        },
    },
    {
        key: 'rotating',
        method: 'POST',
        path: '/api/v1/proxy/rotating',
        permission: 'proxy-rotating.read',
        title: 'Lấy proxy xoay',
        description: 'Chỉ gửi ID user_proxies và protocol http hoặc socks5. Endpoint này không thay đổi dữ liệu đã lưu.',
        body: {
            id: 57,
            protocol: 'http',
        },
        response: {
            status: true,
            data: {
                proxy_id: 57,
                proxy: '127.0.0.1:18080:user:password',
                protocol: 'http',
                message: 'Lấy proxy xoay thành công.',
            },
        },
    },
];

const selectedKey = ref('order');
const apiKeys = ref<ClientApiKeyType[]>([]);
const apiKeyPermissions = ref<ApiKeyPermissionType[]>([]);
const loadingApiKeys = ref(true);
const creatingApiKey = ref(false);
const updatingApiKeyId = ref<number | null>(null);
const copiedCredential = ref<string | null>(null);
const generatedCredential = ref<{ api_key: string; api_secret: string; name: string } | null>(null);
const apiKey = computed(() => apiKeys.value[0] ?? null);
const selected = computed(() => endpoints.find((endpoint) => endpoint.key === selectedKey.value) ?? endpoints[0]);
const requestBody = computed(() => (selected.value.body ? JSON.stringify(selected.value.body, null, 2) : ''));
const responseBody = computed(() => JSON.stringify(selected.value.response, null, 2));
const curlExample = computed(() => {
    const lines = [
        `curl -X ${selected.value.method} "${baseUrl}${selected.value.path}" \\`,
        '  -H "Accept: application/json" \\',
        '  -H "Content-Type: application/json" \\',
        '  -H "X-API-KEY: your_api_key" \\',
        '  -H "X-API-SECRET: your_api_secret"',
    ];

    if (selected.value.body) {
        lines[lines.length - 1] += ' \\';
        lines.push(`  --data '${JSON.stringify(selected.value.body)}'`);
    }

    return lines.join('\n');
});

const copy = async (value: string) => {
    await navigator.clipboard.writeText(value);
    await Swal.fire({ title: 'Đã sao chép', icon: 'success', timer: 900, showConfirmButton: false });
};

const extractErrorMessage = (error: unknown, fallback: string): string => {
    const axiosError = error as AxiosError<{ message?: string }>;

    return axiosError.response?.data?.message || fallback;
};

const loadApiKeys = async (): Promise<void> => {
    loadingApiKeys.value = true;

    try {
        const result = await clientApiKeyService.list();
        apiKeys.value = result.data;
        apiKeyPermissions.value = result.permissions;
    } catch (error) {
        await Swal.fire('Không thể tải API key', extractErrorMessage(error, 'Vui lòng thử lại sau.'), 'error');
    } finally {
        loadingApiKeys.value = false;
    }
};

const createApiKey = async (): Promise<void> => {
    if (apiKey.value || creatingApiKey.value) {
        return;
    }

    creatingApiKey.value = true;

    try {
        const result = await clientApiKeyService.create({
            name: 'DailyProxy API v1',
            permissions: apiKeyPermissions.value.map((permission) => permission.key),
            ip_whitelist: ['*'],
        });

        apiKeys.value = [result.api_key, ...apiKeys.value.filter((apiKey) => apiKey.id !== result.api_key.id)];
        generatedCredential.value = {
            api_key: result.api_key.api_key,
            api_secret: result.api_secret,
            name: result.api_key.name,
        };
        apiKeyPermissions.value = result.permission_catalog;

        await Swal.fire('Đã tạo API key', 'Hãy sao chép API Secret ngay vì hệ thống chỉ hiển thị một lần.', 'success');
    } catch (error) {
        await Swal.fire('Không thể tạo API key', extractErrorMessage(error, 'Vui lòng kiểm tra dữ liệu và thử lại.'), 'error');
    } finally {
        creatingApiKey.value = false;
    }
};

const rotateApiSecret = async (apiKeyId: number): Promise<void> => {
    const confirmation = await Swal.fire({
        title: 'Đổi API Secret?',
        text: 'Secret cũ sẽ ngừng hoạt động ngay. Bạn cần cập nhật secret mới trong ứng dụng đang tích hợp.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Đổi secret',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#2563eb',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    updatingApiKeyId.value = apiKeyId;

    try {
        const result = await clientApiKeyService.rotate(apiKeyId);
        apiKeys.value = apiKeys.value.map((apiKey) => (apiKey.id === apiKeyId ? result.api_key : apiKey));
        generatedCredential.value = {
            api_key: result.api_key.api_key,
            api_secret: result.api_secret,
            name: result.api_key.name,
        };

        await Swal.fire('Đã đổi API Secret', 'Hãy sao chép secret mới ngay. Secret cũ không còn hiệu lực.', 'success');
    } catch (error) {
        await Swal.fire('Không thể đổi secret', extractErrorMessage(error, 'Vui lòng thử lại sau.'), 'error');
    } finally {
        updatingApiKeyId.value = null;
    }
};

const copyCredential = async (value: string, key: string): Promise<void> => {
    await navigator.clipboard.writeText(value);
    copiedCredential.value = key;
    window.setTimeout(() => {
        if (copiedCredential.value === key) {
            copiedCredential.value = null;
        }
    }, 1500);
};

const endpointIcon = (key: string) => {
    if (key === 'order') return ShoppingCart;
    if (key === 'change') return RefreshCw;
    if (key === 'renew') return RotateCw;
    return Code2;
};

onMounted(loadApiKeys);
</script>

<template>
    <div class="space-y-6">
        <header class="rounded-[14px] border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-cyan-50 p-6 shadow-sm sm:p-8">
            <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-blue-600">DailyProxy API v1</p>
                    <h1 class="mt-2 text-3xl font-black text-slate-950">Tài liệu tích hợp Proxy API</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                        Mua proxy, đổi proxy tĩnh, gia hạn và lấy proxy xoay bằng API key của tài khoản. Mọi thao tác chỉ truy cập dữ liệu thuộc chủ
                        API key.
                    </p>
                </div>
                <div
                    class="flex items-center gap-3 rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
                >
                    <CheckCircle2 class="h-5 w-5" /> API v1 đang hoạt động
                </div>
            </div>
        </header>

        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-[12px] border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="flex items-start gap-4">
                    <div class="rounded-[10px] bg-blue-50 p-3 text-blue-600"><KeyRound class="h-6 w-6" /></div>
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-slate-950">Xác thực mỗi request</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Gửi hai header <code class="rounded bg-slate-100 px-1.5 py-0.5">X-API-KEY</code> và
                            <code class="rounded bg-slate-100 px-1.5 py-0.5">X-API-SECRET</code>. API key phải được cấp đúng permission của endpoint.
                        </p>

                        <div v-if="loadingApiKeys" class="mt-4 h-12 animate-pulse rounded-[9px] bg-slate-100" />

                        <div
                            v-else-if="apiKey"
                            class="mt-4 flex flex-col gap-3 rounded-[10px] border border-blue-100 bg-blue-50/60 p-3 sm:flex-row sm:items-center"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500">API Key</p>
                                <code class="mt-1 block truncate text-sm font-semibold text-slate-900">{{ apiKey.api_key }}</code>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-[7px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    @click="copyCredential(apiKey.api_key, 'api-key')"
                                >
                                    <Copy class="h-3.5 w-3.5" /> {{ copiedCredential === 'api-key' ? 'Đã chép' : 'Sao chép' }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-[7px] bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                                    :disabled="updatingApiKeyId === apiKey.id"
                                    @click="rotateApiSecret(apiKey.id)"
                                >
                                    <RefreshCw class="h-3.5 w-3.5" :class="updatingApiKeyId === apiKey.id ? 'animate-spin' : ''" />
                                    Đổi secret
                                </button>
                            </div>
                        </div>

                        <button
                            v-else
                            type="button"
                            class="mt-4 inline-flex items-center gap-2 rounded-[8px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-200 hover:bg-blue-700 disabled:opacity-60"
                            :disabled="creatingApiKey"
                            @click="createApiKey"
                        >
                            <KeyRound class="h-4 w-4" /> {{ creatingApiKey ? 'Đang tạo...' : 'Tạo API Key / Secret' }}
                        </button>

                        <div v-if="generatedCredential" class="mt-3 rounded-[10px] border border-emerald-200 bg-emerald-50 p-3">
                            <p class="text-xs font-bold text-emerald-800">API Secret mới — chỉ hiển thị lần này</p>
                            <div class="mt-2 flex items-center gap-2 rounded-[7px] bg-white px-3 py-2 ring-1 ring-emerald-200">
                                <code class="min-w-0 flex-1 break-all text-xs font-semibold text-slate-900">{{
                                    generatedCredential.api_secret
                                }}</code>
                                <button
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold text-emerald-700"
                                    @click="copyCredential(generatedCredential.api_secret, 'api-secret')"
                                >
                                    <Copy class="h-3.5 w-3.5" /> {{ copiedCredential === 'api-secret' ? 'Đã chép' : 'Sao chép' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
            <article class="rounded-[12px] border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900">
                <p class="font-bold">Quy ước orderCode</p>
                <p class="mt-1">Trong API đổi và gia hạn, <strong>orderCode chính là ID của user_proxies</strong>, không phải mã PXY của đơn mua.</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[300px,minmax(0,1fr)]">
            <nav class="space-y-2 rounded-[12px] border border-slate-200 bg-white p-3 shadow-sm">
                <button
                    v-for="endpoint in endpoints"
                    :key="endpoint.key"
                    type="button"
                    class="flex w-full items-center gap-3 rounded-[9px] px-3 py-3 text-left transition"
                    :class="selectedKey === endpoint.key ? 'bg-blue-600 text-white shadow-md shadow-blue-200' : 'text-slate-700 hover:bg-slate-50'"
                    @click="selectedKey = endpoint.key"
                >
                    <component :is="endpointIcon(endpoint.key)" class="h-5 w-5 shrink-0" />
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold">{{ endpoint.title }}</span>
                        <span class="block truncate text-[11px] opacity-75">{{ endpoint.method }} {{ endpoint.path }}</span>
                    </span>
                </button>
            </nav>

            <article class="min-w-0 overflow-hidden rounded-[12px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-[5px] bg-blue-600 px-2.5 py-1 text-xs font-black text-white">{{ selected.method }}</span>
                        <code class="text-sm font-bold text-slate-800">{{ selected.path }}</code>
                    </div>
                    <h2 class="mt-4 text-2xl font-black text-slate-950">{{ selected.title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ selected.description }}</p>
                    <p class="mt-3 text-xs font-semibold text-violet-600">Permission: {{ selected.permission }}</p>
                </div>

                <div class="border-b border-slate-200 bg-slate-50 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-bold text-slate-900">cURL</h3>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-[6px] bg-white px-3 py-2 text-xs font-semibold text-blue-600 shadow-sm ring-1 ring-slate-200 hover:bg-blue-50"
                            @click="copy(curlExample)"
                        >
                            <Copy class="h-3.5 w-3.5" /> Sao chép lệnh
                        </button>
                    </div>
                    <pre class="mt-3 overflow-x-auto whitespace-pre-wrap rounded-[10px] bg-slate-900 p-4 text-xs leading-6 text-slate-200">{{
                        curlExample
                    }}</pre>
                </div>

                <div class="grid gap-5 p-5 sm:p-6 2xl:grid-cols-2">
                    <div v-if="selected.body" class="min-w-0">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Request JSON</h3>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700"
                                @click="copy(requestBody)"
                            >
                                <Copy class="h-3.5 w-3.5" /> Sao chép
                            </button>
                        </div>
                        <pre class="mt-3 overflow-x-auto rounded-[10px] bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{{ requestBody }}</pre>
                    </div>
                    <div class="min-w-0" :class="!selected.body ? '2xl:col-span-2' : ''">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Response mẫu</h3>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700"
                                @click="copy(responseBody)"
                            >
                                <Copy class="h-3.5 w-3.5" /> Sao chép
                            </button>
                        </div>
                        <pre class="mt-3 overflow-x-auto rounded-[10px] bg-slate-950 p-4 text-xs leading-6 text-emerald-100">{{ responseBody }}</pre>
                        <div
                            v-if="selected.key === 'order'"
                            role="alert"
                            class="mt-3 flex items-start gap-3 rounded-[9px] border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900"
                        >
                            <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                            <p>
                                <strong>Lưu ý:</strong> Nếu mua sản phẩm proxy xoay, kết quả được trả trong trường
                                <code class="rounded bg-amber-100 px-1.5 py-0.5 font-bold">key</code>. Nếu mua sản phẩm proxy tĩnh, kết quả được trả
                                trong trường <code class="rounded bg-amber-100 px-1.5 py-0.5 font-bold">proxy</code>.
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
