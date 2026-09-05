<script setup lang="ts">
import TabApiKeys from '@/pages/client/profile/components/TabApiKeys.vue';
import { clientApiKeyService } from '@/services/client-api-key.service';
import { trafficFineService } from '@/services/traffic-fine.service';
import type { ApiKeyPermissionType, ClientApiKeyType } from '@/types/api-key.type';
import formatCash from '@/utils/helpers/formatCash';
import { CheckCircle2, Copy, KeyRound, PlayCircle, WalletCards } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const apiRequestPrice = ref(20);
const apiV2RequestPrice = ref(150);
const apiV1Description = ref('Phiên bản ổn định, phù hợp với các hệ thống đang tích hợp.');
const apiV2Description = ref('Phiên bản mới, tối ưu cho các kết nối và ứng dụng mới.');
const apiKeys = ref<ClientApiKeyType[]>([]);
const permissions = ref<ApiKeyPermissionType[]>([]);
const keyLoading = ref(true);
const keyCreating = ref(false);
const updatingApiKeyId = ref<number | null>(null);
const copiedKey = ref<string | null>(null);
const generatedSecret = ref<{ api_key: string; api_secret: string; name: string } | null>(null);
const formName = ref('Đối tác API');
const formIpWhitelist = ref('*');
const selectedVersion = ref<'v1' | 'v2'>('v2');
const requestCopied = ref(false);
const activeApi = computed(() =>
    selectedVersion.value === 'v2'
        ? {
              endpoint: '/api/v2/lookup',
              price: apiV2RequestPrice.value,
              description: apiV2Description.value,
              parameters: 'plate=30A12345 và vehicle_type=car',
              vehicleTypes: 'vehicle_type: car · motorbike · electric_motorbike',
          }
        : {
              endpoint: '/api/v1/lookup',
              price: apiRequestPrice.value,
              description: apiV1Description.value,
              parameters: 'plate=30A12345 và vehicle_type=car',
              vehicleTypes: 'vehicle_type: car · motorbike · electric_motorbike',
          },
);
const requestExample = computed(() => {
    const query = ['plate=30A12345', 'vehicle_type=car'];

    return `curl -G "${window.location.origin}${activeApi.value.endpoint}" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: YOUR_API_KEY" \\
  -H "X-API-SECRET: YOUR_API_SECRET" \\
  --data-urlencode "${query[0]}" \\
  --data-urlencode "${query[1]}"`;
});

const responseExample = `{
  "success": true,
  "cached": false,
  "data": {
    "plate": "30A12345",
    "vehicle_type": "car",
    "status": "no_violation",
    "violation_count": 0,
    "violations": []
  }
}`;

const copyRequestExample = async (): Promise<void> => {
    try {
        await navigator.clipboard.writeText(requestExample.value);
        requestCopied.value = true;
        window.setTimeout(() => (requestCopied.value = false), 1800);
    } catch {
        await Swal.fire('Không thể sao chép', 'Hãy chọn và sao chép lệnh curl thủ công.', 'error');
    }
};

const ipWhitelist = (value: string): string[] =>
    value
        .split(/\r?\n|,/)
        .map((item) => item.trim())
        .filter(Boolean);

const loadApiKeys = async (): Promise<void> => {
    keyLoading.value = true;

    try {
        const response = await clientApiKeyService.list();
        apiKeys.value = response.data;
        permissions.value = response.permissions;
    } finally {
        keyLoading.value = false;
    }
};

const createApiKey = async (): Promise<void> => {
    keyCreating.value = true;

    try {
        const response = await clientApiKeyService.create({
            name: formName.value.trim(),
            permissions: permissions.value.filter((permission) => permission.self_service).map((permission) => permission.key),
            ip_whitelist: ipWhitelist(formIpWhitelist.value),
        });

        apiKeys.value = [response.api_key];
        generatedSecret.value = {
            api_key: response.api_key.api_key,
            api_secret: response.api_secret,
            name: response.api_key.name,
        };
    } catch {
        await Swal.fire('Không thể tạo API key', 'Mỗi tài khoản chỉ có một cặp key/secret. Hãy làm mới danh sách và thử lại.', 'error');
    } finally {
        keyCreating.value = false;
    }
};

const updateIpWhitelist = async (apiKeyId: number, value: string): Promise<void> => {
    updatingApiKeyId.value = apiKeyId;

    try {
        const response = await clientApiKeyService.update(apiKeyId, { ip_whitelist: ipWhitelist(value) });
        apiKeys.value = apiKeys.value.map((apiKey) => (apiKey.id === apiKeyId ? response.api_key : apiKey));
    } catch {
        await Swal.fire('Không thể cập nhật', 'Danh sách IP chưa được lưu. Vui lòng kiểm tra lại.', 'error');
    } finally {
        updatingApiKeyId.value = null;
    }
};

const rotateSecret = async (apiKeyId: number): Promise<void> => {
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Đổi API secret?',
        text: 'Secret cũ sẽ mất hiệu lực ngay lập tức.',
        showCancelButton: true,
        confirmButtonText: 'Đổi secret',
        cancelButtonText: 'Hủy',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    updatingApiKeyId.value = apiKeyId;

    try {
        const response = await clientApiKeyService.rotate(apiKeyId);
        apiKeys.value = apiKeys.value.map((apiKey) => (apiKey.id === apiKeyId ? response.api_key : apiKey));
        generatedSecret.value = {
            api_key: response.api_key.api_key,
            api_secret: response.api_secret,
            name: response.api_key.name,
        };
    } catch {
        await Swal.fire('Không thể đổi secret', 'Vui lòng thử lại sau.', 'error');
    } finally {
        updatingApiKeyId.value = null;
    }
};

const copyCredential = async (value: string, key: string): Promise<void> => {
    try {
        await navigator.clipboard.writeText(value);
        copiedKey.value = key;
        window.setTimeout(() => {
            if (copiedKey.value === key) {
                copiedKey.value = null;
            }
        }, 1800);
    } catch {
        await Swal.fire('Không thể sao chép', 'Hãy chọn và sao chép credential thủ công.', 'error');
    }
};

onMounted(async () => {
    try {
        const dashboard = await trafficFineService.dashboard();
        apiRequestPrice.value = dashboard.api_request_price;
        apiV2RequestPrice.value = dashboard.api_v2_request_price;
        apiV1Description.value = dashboard.api_v1_description;
        apiV2Description.value = dashboard.api_v2_description;
    } catch {
        apiRequestPrice.value = 20;
        apiV2RequestPrice.value = 150;
    }

    try {
        await loadApiKeys();
    } catch {
        keyLoading.value = false;
    }
});
</script>

<template>
    <div class="mx-auto grid max-w-5xl gap-8">
        <header class="rounded-2xl bg-slate-950 p-6 text-white sm:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-bold text-sky-300">Kết nối API</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight">Chạy request đầu tiên trong 3 bước</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Tạo key, nạp số dư rồi copy lệnh mẫu. Chỉ gọi API từ backend của bạn.
                    </p>
                </div>
                <RouterLink
                    to="/dashboard/api-usage"
                    class="app-focus inline-flex min-h-11 items-center justify-center rounded-lg bg-white px-4 text-sm font-bold text-slate-950"
                >
                    Xem lịch sử API
                </RouterLink>
            </div>
            <ol class="mt-6 grid gap-3 sm:grid-cols-3">
                <li
                    v-for="step in [
                        { icon: KeyRound, text: '1. Tạo API key' },
                        { icon: WalletCards, text: '2. Nạp số dư' },
                        { icon: PlayCircle, text: '3. Copy và chạy' },
                    ]"
                    :key="step.text"
                    class="flex items-center gap-3 rounded-xl bg-white/10 px-4 py-3 text-sm font-bold"
                >
                    <component :is="step.icon" class="h-5 w-5 text-sky-300" />{{ step.text }}
                </li>
            </ol>
        </header>

        <section id="api-key">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700">Bước 1</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Tạo API key và secret</h2>
                <p class="mt-1 text-sm text-slate-500">Secret chỉ hiện một lần sau khi tạo. Hãy lưu ngay vào backend của bạn.</p>
            </div>
            <div class="mt-4">
                <TabApiKeys
                    :profile="null"
                    :permissions="permissions"
                    :api-keys="apiKeys"
                    :loading="keyLoading"
                    :creating="keyCreating"
                    :updating-api-key-id="updatingApiKeyId"
                    :copied-key="copiedKey"
                    :generated-secret="generatedSecret"
                    :form-name="formName"
                    :form-ip-whitelist="formIpWhitelist"
                    @update-name="formName = $event"
                    @update-ip-whitelist="formIpWhitelist = $event"
                    @create="createApiKey"
                    @refresh="loadApiKeys"
                    @update-ip-list="updateIpWhitelist"
                    @rotate="rotateSecret"
                    @copy="copyCredential"
                />
            </div>
        </section>

        <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <WalletCards class="mt-0.5 h-6 w-6 shrink-0 text-emerald-700" />
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Bước 2</p>
                        <h2 class="mt-1 font-bold text-emerald-950">Nạp số dư để gọi API</h2>
                        <p class="mt-1 text-sm text-emerald-800">Không cần mua gói tháng. Chỉ trừ tiền khi request thành công.</p>
                    </div>
                </div>
                <RouterLink
                    to="/dashboard/wallet"
                    class="app-focus inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-700 px-5 text-sm font-bold text-white"
                    >Nạp tiền</RouterLink
                >
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-sky-700">Bước 3</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950">Chọn phiên bản và copy lệnh</h2>
                </div>
                <div class="inline-flex rounded-lg bg-slate-100 p-1" role="tablist" aria-label="Phiên bản API">
                    <button
                        v-for="version in ['v1', 'v2'] as const"
                        :key="version"
                        type="button"
                        role="tab"
                        :aria-selected="selectedVersion === version"
                        class="app-focus min-h-10 rounded-md px-5 text-sm font-black uppercase"
                        :class="selectedVersion === version ? 'bg-white text-sky-700 shadow-sm' : 'text-slate-500'"
                        @click="selectedVersion = version"
                    >
                        API {{ version }}
                    </button>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                <div class="min-w-0 overflow-hidden rounded-xl bg-slate-950">
                    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                        <code class="truncate text-sm font-bold text-sky-300">GET {{ activeApi.endpoint }}</code>
                        <button
                            type="button"
                            class="app-focus inline-flex min-h-9 shrink-0 items-center gap-2 rounded-lg bg-white/10 px-3 text-xs font-bold text-white"
                            @click="copyRequestExample"
                        >
                            <CheckCircle2 v-if="requestCopied" class="h-4 w-4 text-emerald-400" /><Copy v-else class="h-4 w-4" />
                            {{ requestCopied ? 'Đã copy' : 'Copy curl' }}
                        </button>
                    </div>
                    <pre class="overflow-x-auto p-4 text-sm leading-7 text-slate-200"><code>{{ requestExample }}</code></pre>
                </div>
                <aside class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-black uppercase text-sky-700">API {{ selectedVersion }}</span>
                    <p class="mt-3 text-2xl font-black text-slate-950">{{ formatCash(activeApi.price) }}đ</p>
                    <p class="text-xs text-slate-500">mỗi request thành công</p>
                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ activeApi.description }}</p>
                    <p class="mt-3 text-xs font-bold text-slate-500">Tham số</p>
                    <code class="mt-1 block break-words text-xs text-slate-800">{{ activeApi.parameters }}</code>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ activeApi.vehicleTypes }}</p>
                </aside>
            </div>
        </section>

        <section class="grid gap-3">
            <details class="group rounded-xl border border-slate-200 bg-white p-5">
                <summary class="cursor-pointer list-none font-bold text-slate-950">Xem response mẫu và mã lỗi</summary>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <pre class="overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs leading-6 text-slate-200"><code>{{ responseExample }}</code></pre>
                    <ul class="grid content-start gap-2 text-sm leading-6 text-slate-600">
                        <li><strong>401:</strong> sai API key hoặc secret.</li>
                        <li><strong>402:</strong> ví không đủ số dư.</li>
                        <li><strong>422:</strong> biển số hoặc loại xe không hợp lệ.</li>
                        <li><strong>429:</strong> gọi quá giới hạn.</li>
                        <li><strong>503:</strong> nguồn dữ liệu đang gián đoạn.</li>
                    </ul>
                </div>
            </details>
            <details class="group rounded-xl border border-slate-200 bg-white p-5">
                <summary class="cursor-pointer list-none font-bold text-slate-950">Lưu ý trước khi đưa lên production</summary>
                <ul class="mt-4 grid gap-2 text-sm leading-6 text-slate-600">
                    <li>• Chỉ gọi API từ backend, không đặt secret trong frontend hoặc ứng dụng mobile.</li>
                    <li>• Lưu secret trong biến môi trường và cấu hình IP whitelist.</li>
                    <li>• Cache hit vẫn tính phí; lỗi xác thực, validation, thiếu số dư và lỗi nguồn không tính phí.</li>
                </ul>
            </details>
        </section>
    </div>
</template>
