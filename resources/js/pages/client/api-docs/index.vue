<script setup lang="ts">
import TabApiKeys from '@/pages/client/profile/components/TabApiKeys.vue';
import { clientApiKeyService } from '@/services/client-api-key.service';
import { trafficFineService } from '@/services/traffic-fine.service';
import type { ApiKeyPermissionType, ClientApiKeyType } from '@/types/api-key.type';
import formatCash from '@/utils/helpers/formatCash';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const apiRequestPrice = ref(20);
const apiKeys = ref<ClientApiKeyType[]>([]);
const permissions = ref<ApiKeyPermissionType[]>([]);
const keyLoading = ref(true);
const keyCreating = ref(false);
const updatingApiKeyId = ref<number | null>(null);
const copiedKey = ref<string | null>(null);
const generatedSecret = ref<{ api_key: string; api_secret: string; name: string } | null>(null);
const formName = ref('Đối tác API');
const formIpWhitelist = ref('*');
const thousandRequestCost = computed(() => apiRequestPrice.value * 1000);
const requestExample = `curl -G "${window.location.origin}/api/v1/lookup" \\
  -H "Accept: application/json" \\
  -H "X-API-KEY: YOUR_API_KEY" \\
  -H "X-API-SECRET: YOUR_API_SECRET" \\
  --data-urlencode "plate=30A12345" \\
  --data-urlencode "vehicle_type=car"`;

const responseExample = `{
  "success": true,
  "cached": false,
  "data": {
    "plate": "30A12345",
    "display_plate": "30A-123.45",
    "vehicle_type": "car",
    "status": "has_violation",
    "violation_count": 1,
    "violations": [
      {
        "plate_color": "Nền màu trắng, chữ và số màu đen",
        "time": "2026-02-13T05:34:00.000Z",
        "location": "Quốc Lộ 1, Tỉnh Bắc Ninh",
        "behavior": "Điều khiển xe chạy quá tốc độ quy định",
        "status": "Chưa xử phạt",
        "agency": "Phòng Cảnh sát giao thông",
        "resolution_agency": "Đội CSGT ĐB số 2",
        "resolution_address": "Số 8A Xuân La, TP Hà Nội",
        "resolution_phone": null
      }
    ],
    "checked_at": "2026-08-16T12:00:00+07:00"
  }
}`;

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
        apiRequestPrice.value = (await trafficFineService.dashboard()).api_request_price;
    } catch {
        apiRequestPrice.value = 20;
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
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Developers</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">API tra cứu phạt nguội</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Tích hợp server-to-server bằng API key và secret. Không đặt secret trong frontend, URL công khai hoặc mã nguồn client.
                </p>
            </div>
            <RouterLink to="/dashboard/api-usage" class="text-sm font-bold text-sky-700">Xem usage →</RouterLink>
        </header>

        <section>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Bắt đầu thuê API</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">3 bước để chạy request đầu tiên</h2>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <article
                    v-for="step in [
                        { number: 1, title: 'Tạo credential', text: 'Tạo một cặp API key và secret, sau đó lưu secret ở backend của bạn.' },
                        { number: 2, title: 'Nạp số dư', text: 'Nạp tiền vào ví. Không cần mua gói hoặc cam kết số lượng request.' },
                        { number: 3, title: 'Gọi API GET', text: 'Gửi biển số, loại xe và hai credential trong header để nhận kết quả.' },
                    ]"
                    :key="step.number"
                    class="rounded-xl border border-slate-200 bg-white p-5"
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-700 text-sm font-black text-white">{{
                        step.number
                    }}</span>
                    <h3 class="mt-4 font-bold text-slate-950">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ step.text }}</p>
                </article>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-[1fr_280px]">
            <article class="rounded-xl border border-slate-200 bg-white p-5 sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-sky-700">Endpoint v1</p>
                        <h2 class="mt-2 font-mono text-lg font-bold text-slate-950">GET /api/v1/lookup</h2>
                    </div>
                    <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800">Cần API key + secret</span>
                </div>
                <p class="mt-5 text-sm leading-7 text-slate-600">
                    Gửi <code>plate</code> và <code>vehicle_type</code> trong query. Key cần quyền <code>traffic-fines.lookup</code>; credential chỉ
                    gửi qua header.
                </p>
            </article>
            <aside class="rounded-xl bg-slate-950 p-6 text-white">
                <p class="text-sm font-bold text-sky-300">Giá hiện tại</p>
                <p class="mt-2 text-3xl font-black">{{ formatCash(apiRequestPrice) }}đ</p>
                <p class="mt-1 text-sm text-slate-300">mỗi request thành công</p>
                <p class="mt-4 text-xs leading-5 text-slate-400">
                    Cache hit vẫn tính một lượt. Lỗi xác thực, validation, thiếu số dư và lỗi nguồn dữ liệu không tính phí.
                </p>
            </aside>
        </section>

        <section>
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Credential</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-950">Tạo và quản lý API key</h2>
                </div>
                <p class="text-sm text-slate-500">Secret chỉ hiển thị khi tạo hoặc xoay secret.</p>
            </div>
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
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-xl font-bold text-slate-950">Tham số request</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Tên</th>
                            <th class="px-5 py-3">Vị trí</th>
                            <th class="px-5 py-3">Bắt buộc</th>
                            <th class="px-5 py-3">Giá trị</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-600">
                        <tr>
                            <td class="px-5 py-4 font-mono font-bold text-slate-950">plate</td>
                            <td class="px-5 py-4">Query</td>
                            <td class="px-5 py-4">Có</td>
                            <td class="px-5 py-4">Biển số đã hoặc chưa có dấu phân cách, ví dụ 30A12345.</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4 font-mono font-bold text-slate-950">vehicle_type</td>
                            <td class="px-5 py-4">Query</td>
                            <td class="px-5 py-4">Có</td>
                            <td class="px-5 py-4"><code>car</code>, <code>motorbike</code> hoặc <code>electric_motorbike</code>.</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4 font-mono font-bold text-slate-950">X-API-KEY</td>
                            <td class="px-5 py-4">Header</td>
                            <td class="px-5 py-4">Có</td>
                            <td class="px-5 py-4">API key của tài khoản.</td>
                        </tr>
                        <tr>
                            <td class="px-5 py-4 font-mono font-bold text-slate-950">X-API-SECRET</td>
                            <td class="px-5 py-4">Header</td>
                            <td class="px-5 py-4">Có</td>
                            <td class="px-5 py-4">Secret tương ứng, chỉ lưu ở backend.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-950">Request</h2>
            <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-5 text-sm leading-7 text-slate-200"><code>{{ requestExample }}</code></pre>
        </section>
        <section>
            <h2 class="text-xl font-bold text-slate-950">Response chuẩn hóa</h2>
            <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-5 text-sm leading-7 text-slate-200"><code>{{ responseExample }}</code></pre>
        </section>
        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Cách tính phí</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Trừ tiền theo request thành công</h2>
                <dl class="mt-5 grid gap-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">1 request thành công</dt>
                        <dd class="font-bold text-slate-950">{{ formatCash(apiRequestPrice) }}đ</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Ví dụ 1.000 request</dt>
                        <dd class="font-bold text-slate-950">{{ formatCash(thousandRequestCost) }}đ</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Phí thuê bao tháng</dt>
                        <dd class="font-bold text-emerald-700">0đ</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Phí khởi tạo</dt>
                        <dd class="font-bold text-emerald-700">0đ</dd>
                    </div>
                </dl>
            </article>
            <article class="rounded-xl border border-sky-200 bg-sky-50 p-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Quy tắc billing</p>
                <ul class="mt-4 grid gap-3 text-sm leading-6 text-sky-950">
                    <li>✓ Cache hit vẫn là một request thành công và được tính phí.</li>
                    <li>✓ Không trừ tiền khi sai credential, sai dữ liệu hoặc không đủ số dư.</li>
                    <li>✓ Không trừ tiền khi nguồn dữ liệu trả lỗi.</li>
                    <li>✓ Mỗi lần retry thành công được tính là một request mới.</li>
                </ul>
            </article>
        </section>
        <section class="rounded-xl bg-sky-50 p-6">
            <h2 class="font-bold text-sky-950">Trạng thái lỗi</h2>
            <ul class="mt-3 grid gap-2 text-sm leading-6 text-sky-900">
                <li><strong>401:</strong> API key hoặc secret không hợp lệ.</li>
                <li><strong>402 insufficient_balance:</strong> ví không đủ tiền và request không bị tính phí.</li>
                <li><strong>422 invalid_plate:</strong> biển số hoặc loại xe không hợp lệ.</li>
                <li><strong>429:</strong> vượt giới hạn request.</li>
                <li><strong>503 provider_error:</strong> nguồn dữ liệu gián đoạn, request không bị tính phí.</li>
            </ul>
        </section>
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-6">
            <h2 class="font-bold text-amber-950">Checklist bảo mật trước khi chạy production</h2>
            <ul class="mt-3 grid gap-2 text-sm leading-6 text-amber-900">
                <li>• Chỉ gọi API từ backend/server của bạn, không gọi trực tiếp từ trình duyệt hoặc ứng dụng mobile.</li>
                <li>• Lưu secret trong biến môi trường hoặc secret manager, không commit vào Git.</li>
                <li>• Cấu hình IP whitelist và xoay secret ngay khi nghi ngờ bị lộ.</li>
                <li>
                    • Theo dõi request, chi phí và lỗi tại trang
                    <RouterLink to="/dashboard/api-usage" class="font-bold underline">Lượt dùng API</RouterLink>.
                </li>
            </ul>
        </section>
    </div>
</template>
