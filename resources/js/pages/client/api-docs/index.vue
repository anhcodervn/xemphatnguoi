<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import { Blocks, KeyRound, LockKeyhole, RefreshCcwDot, ShieldCheck, Webhook } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ApiEndpointCard from './components/ApiEndpointCard.vue';
import ApiFlowSteps from './components/ApiFlowSteps.vue';
import ApiHero from './components/ApiHero.vue';
import ApiQuickAccess from './components/ApiQuickAccess.vue';
import ApiSecurityChecklist from './components/ApiSecurityChecklist.vue';
import ApiWebhookStatus from './components/ApiWebhookStatus.vue';

type QuickAccessItem = {
    key: string;
    label: string;
    value: string;
    note: string;
    copyValue?: string;
};

type EndpointParam = {
    name: string;
    type: string;
    required: boolean;
    description: string;
};

type EndpointItem = {
    id: string;
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    path: string;
    title: string;
    description: string;
    tags: string[];
    params: EndpointParam[];
    requestExample: string;
    responseExample: string;
    statusLabel?: string;
};

type EndpointGroup = {
    key: string;
    label: string;
    endpoints: EndpointItem[];
};

type SecurityItem = {
    key: string;
    title: string;
    description: string;
    icon: LucideIcon;
    iconClass: string;
};

type FlowStep = {
    order: string;
    title: string;
    description: string;
};

type StatItem = {
    label: string;
    value: string;
    note: string;
    icon: LucideIcon;
};

type WebhookStatusItem = {
    key: string;
    label: string;
    value: string;
    tone: 'success' | 'warning' | 'danger' | 'neutral';
    note: string;
};

const endpointSection = ref<HTMLElement | null>(null);
const selectedGroup = ref('all');
const copiedKey = ref<string | null>(null);
let copiedTimer: ReturnType<typeof setTimeout> | null = null;

const baseUrl = `${window.location.origin}/api`;

const endpointGroups: EndpointGroup[] = [
    {
        key: 'auth',
        label: 'Auth',
        endpoints: [
            {
                id: 'v1-me',
                method: 'GET',
                path: '/v1/me',
                title: 'Lấy thông tin tài khoản',
                description: 'Kiểm tra API key đang hoạt động và lấy hồ sơ tài khoản hiện tại.',
                tags: ['Auth', 'Profile'],
                params: [],
                requestExample: `curl --request GET \\
  --url ${baseUrl}/v1/me \\
  --header "Accept: application/json" \\
  --header "X-API-KEY: YOUR_API_KEY" \\
  --header "X-API-SECRET: YOUR_API_SECRET"`,
                responseExample: `{
  "status": true,
  "message": "Success",
  "data": {
    "id": 1,
    "username": "demo_user",
    "email": "user@example.com",
    "phone": "0900000000",
    "full_name": "Nguyen Van A",
    "status": "active",
    "wallet": {
      "balance": 1250000
    },
    "active_subscription_count": 1
  }
}`,
                statusLabel: 'Xác thực bắt buộc',
            },
        ],
    },
    {
        key: 'bank',
        label: 'Bank',
        endpoints: [
            {
                id: 'v1-list-bank-accounts',
                method: 'GET',
                path: '/v1/list-bank-accounts',
                title: 'Lấy danh sách ngân hàng nhận tiền',
                description: 'Trả về danh sách bank account đang hoạt động để chọn bank_id khi tạo lệnh nạp.',
                tags: ['Bank', 'Recharge'],
                params: [],
                requestExample: `curl --request GET \\
  --url ${baseUrl}/v1/list-bank-accounts \\
  --header "Accept: application/json" \\
  --header "X-API-KEY: YOUR_API_KEY" \\
  --header "X-API-SECRET: YOUR_API_SECRET"`,
                responseExample: `{
  "status": true,
  "message": "Success",
  "data": {
    "bank_accounts": [
      {
        "bank_id": 12,
        "bank_code": "mb",
        "bank_name": "MB Bank",
        "bank_full_name": "Ngân hàng Quân đội",
        "account_name": "CONG TY ABC",
        "account_number": "9363449824",
        "username": "0363449824",
        "status": "active",
        "last_sync_at": "2026-05-30T09:30:00+07:00"
      }
    ]
  }
}`,
                statusLabel: 'Xác thực bắt buộc',
            },
            {
                id: 'v1-transactions',
                method: 'POST',
                path: '/v1/transactions',
                title: 'Đồng bộ giao dịch theo ngân hàng',
                description: 'Lấy giao dịch mới theo bank_id và tự đối soát các lệnh nạp đối tác đang chờ.',
                tags: ['Bank', 'Sync'],
                params: [
                    { name: 'bank_id', type: 'integer', required: true, description: 'ID bank lấy từ endpoint /v1/list-bank-accounts.' },
                    { name: 'limit', type: 'integer', required: false, description: 'Số giao dịch cần trả về, tối đa 100.' },
                    { name: 'force_refresh', type: 'boolean', required: false, description: 'Đặt true để bỏ qua cooldown và lấy dữ liệu mới ngay.' },
                ],
                requestExample: `curl --request POST \\
  --url ${baseUrl}/v1/transactions \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "X-API-KEY: YOUR_API_KEY" \\
  --header "X-API-SECRET: YOUR_API_SECRET" \\
  --data '{
    "bank_id": 12,
    "limit": 20,
    "force_refresh": true
  }'`,
                responseExample: `{
  "status": true,
  "message": "Success",
  "data": {
    "bank_id": 12,
    "transactions": [
      {
        "transaction_id": "FT26145295030431",
        "amount": 625000,
        "description": "NAPA7K92X user transfer",
        "transaction_time": "2026-05-24 20:15:01",
        "type": "credit"
      }
    ],
    "new_transactions": [
      {
        "transaction_id": "FT26145295030431",
        "amount": 625000,
        "description": "NAPA7K92X user transfer",
        "transaction_time": "2026-05-24 20:15:01",
        "type": "credit"
      }
    ],
    "matched_recharge_clients": 1
  }
}`,
                statusLabel: 'Xác thực bắt buộc',
            },
        ],
    },
    {
        key: 'recharge',
        label: 'Recharge',
        endpoints: [
            {
                id: 'v1-recharge-orders-store',
                method: 'POST',
                path: '/v1/recharge-orders',
                title: 'Tạo lệnh nạp đối tác',
                description: 'Tạo lệnh nạp trên hệ thống và nhận lại nội dung chuyển khoản ngẫu nhiên để user cuối thanh toán.',
                tags: ['Recharge', 'Order'],
                params: [
                    { name: 'bank_id', type: 'integer', required: true, description: 'ID bank account dùng để nhận tiền.' },
                    { name: 'amount', type: 'number', required: true, description: 'Số tiền cần nạp, phải khớp chính xác khi đối soát.' },
                    { name: 'client_order_code', type: 'string', required: false, description: 'Mã đơn hàng phía hệ thống của bạn để đối soát nội bộ.' },
                ],
                requestExample: `curl --request POST \\
  --url ${baseUrl}/v1/recharge-orders \\
  --header "Accept: application/json" \\
  --header "Content-Type: application/json" \\
  --header "X-API-KEY: YOUR_API_KEY" \\
  --header "X-API-SECRET: YOUR_API_SECRET" \\
  --data '{
    "bank_id": 12,
    "amount": 500000,
    "client_order_code": "ORDER-10001"
  }'`,
                responseExample: `{
  "status": true,
  "message": "Tạo lệnh nạp cho đối tác thành công.",
  "data": {
    "order": {
      "id": 18,
      "order_code": "RCL260530AB12CD",
      "client_order_code": "ORDER-10001",
      "bank_id": 12,
      "method": "mb",
      "method_label": "MB Bank",
      "amount": 500000,
      "bank_name": "MB Bank",
      "account_number": "9363449824",
      "account_name": "CONG TY ABC",
      "transfer_content": "NAPA7K92X",
      "status": "pending",
      "requested_at": "2026-05-30T09:45:00+07:00",
      "paid_at": null,
      "expires_at": "2026-05-30T10:45:00+07:00",
      "matched_bank_transaction_id": null,
      "metadata": {
        "source": "api.v1"
      }
    }
  }
}`,
                statusLabel: 'Xác thực bắt buộc',
            },
            {
                id: 'v1-recharge-orders-show',
                method: 'GET',
                path: '/v1/recharge-orders/{orderCode}',
                title: 'Kiểm tra trạng thái lệnh nạp',
                description: 'Dùng để polling trạng thái sau khi đã hiển thị thông tin chuyển khoản cho user cuối.',
                tags: ['Recharge', 'Status'],
                params: [{ name: 'orderCode', type: 'string', required: true, description: 'Mã order do hệ thống trả về ở bước tạo lệnh nạp.' }],
                requestExample: `curl --request GET \\
  --url ${baseUrl}/v1/recharge-orders/RCL260530AB12CD \\
  --header "Accept: application/json" \\
  --header "X-API-KEY: YOUR_API_KEY" \\
  --header "X-API-SECRET: YOUR_API_SECRET"`,
                responseExample: `{
  "status": true,
  "message": "Success",
  "data": {
    "order": {
      "id": 18,
      "order_code": "RCL260530AB12CD",
      "client_order_code": "ORDER-10001",
      "bank_id": 12,
      "amount": 500000,
      "transfer_content": "NAPA7K92X",
      "status": "paid",
      "requested_at": "2026-05-30T09:45:00+07:00",
      "paid_at": "2026-05-30T09:52:18+07:00",
      "expires_at": "2026-05-30T10:45:00+07:00",
      "matched_bank_transaction_id": 991
    }
  }
}`,
                statusLabel: 'Xác thực bắt buộc',
            },
        ],
    },
];

const endpointTabs = computed(() => [
    { key: 'all', label: 'Tất cả' },
    ...endpointGroups.map((group) => ({
        key: group.key,
        label: group.label,
    })),
]);

const activeEndpoints = computed(() => {
    if (selectedGroup.value === 'all') {
        return endpointGroups.flatMap((group) => group.endpoints);
    }

    return endpointGroups.find((group) => group.key === selectedGroup.value)?.endpoints ?? [];
});

const stats: StatItem[] = [
    { label: 'Endpoint', value: '5', note: 'Đang hoạt động', icon: Blocks },
    { label: 'JSON', value: 'Response', note: 'Chuẩn dữ liệu', icon: KeyRound },
    { label: 'Webhook', value: 'Ready', note: 'Sẵn sàng tích hợp', icon: Webhook },
];

const quickAccessItems: QuickAccessItem[] = [
    {
        key: 'base-url',
        label: 'Base URL',
        value: baseUrl,
        note: 'Đường dẫn gốc để gọi toàn bộ endpoint trong tài liệu này.',
        copyValue: baseUrl,
    },
    {
        key: 'authorization',
        label: 'Authorization',
        value: 'X-API-KEY + X-API-SECRET',
        note: 'Gửi cặp header này trong mọi request API v1.',
        copyValue: 'X-API-KEY: YOUR_API_KEY\nX-API-SECRET: YOUR_API_SECRET',
    },
    {
        key: 'response',
        label: 'Response',
        value: '{ status, message, data }',
        note: 'Cấu trúc response mặc định của API.',
        copyValue: '{ "status": true, "message": "Success", "data": {} }',
    },
    {
        key: 'content-type',
        label: 'Content-Type',
        value: 'application/json',
        note: 'Định dạng body cho request POST và phản hồi JSON.',
        copyValue: 'application/json',
    },
];

const securityItems: SecurityItem[] = [
    {
        key: 'token',
        title: 'Xác thực API',
        description: 'Luôn gửi X-API-KEY và X-API-SECRET trong header, không truyền qua URL.',
        icon: KeyRound,
        iconClass: 'bg-blue-100 text-blue-600',
    },
    {
        key: 'bank-info',
        title: 'Thông tin ngân hàng',
        description: 'Chỉ lưu dữ liệu cần thiết để nhận tiền. Dữ liệu nhạy cảm được mã hóa trước khi lưu.',
        icon: LockKeyhole,
        iconClass: 'bg-emerald-100 text-emerald-600',
    },
    {
        key: 'webhook',
        title: 'Webhook',
        description: 'Nếu dùng callback, hãy xác minh chữ ký hoặc IP nguồn trước khi chấp nhận dữ liệu.',
        icon: Webhook,
        iconClass: 'bg-violet-100 text-violet-600',
    },
    {
        key: 'rate-limit',
        title: 'Rate limit',
        description: 'Không gọi force refresh liên tục. Chỉ sync khi thực sự cần để tránh bị giới hạn.',
        icon: RefreshCcwDot,
        iconClass: 'bg-amber-100 text-amber-600',
    },
];

const flowSteps: FlowStep[] = [
    {
        order: '1',
        title: 'Lấy danh sách ngân hàng nhận tiền',
        description: 'Gọi GET /v1/list-bank-accounts để lấy bank_id phù hợp cho từng lệnh nạp.',
    },
    {
        order: '2',
        title: 'Tạo lệnh nạp',
        description: 'Gọi POST /v1/recharge-orders để nhận bank account, số tiền và transfer_content ngẫu nhiên.',
    },
    {
        order: '3',
        title: 'Hiển thị nội dung chuyển khoản',
        description: 'Yêu cầu user cuối chuyển đúng số tiền và đúng transfer_content hệ thống đã tạo.',
    },
    {
        order: '4',
        title: 'Polling trạng thái',
        description: 'Kiểm tra GET /v1/recharge-orders/{orderCode} hoặc sync giao dịch để nhận trạng thái paid.',
    },
];

const webhookStatusItems: WebhookStatusItem[] = [
    {
        key: 'support',
        label: 'Webhook',
        value: 'Có thể tích hợp',
        tone: 'success',
        note: 'Có thể dùng callback riêng hoặc polling theo mã order.',
    },
    {
        key: 'matching',
        label: 'Đối soát',
        value: 'Tự động',
        tone: 'success',
        note: 'Khớp theo bank_id, số tiền và transfer_content trong giao dịch đến.',
    },
    {
        key: 'expires',
        label: 'Hết hạn lệnh',
        value: '60 phút',
        tone: 'warning',
        note: 'Lệnh quá hạn sẽ không còn ở trạng thái chờ thanh toán.',
    },
];

const copyToClipboard = async (value: string, key: string): Promise<void> => {
    await navigator.clipboard.writeText(value);
    copiedKey.value = key;

    if (copiedTimer) {
        clearTimeout(copiedTimer);
    }

    copiedTimer = setTimeout(() => {
        copiedKey.value = null;
    }, 1500);
};

const scrollToEndpoints = (): void => {
    endpointSection.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};
</script>

<template>
    <div class="space-y-4 pb-5">
        <ApiHero
            :base-url="baseUrl"
            :stats="stats"
            :copied-key="copiedKey"
            @copy-base-url="copyToClipboard(baseUrl, 'hero-base-url')"
            @view-endpoints="scrollToEndpoints"
        />

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-4">

                <section ref="endpointSection" class="rounded-[10px] border border-slate-200/80 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-bold tracking-[-0.03em] text-slate-950">Danh sách API</h2>
                            <p class="mt-1 text-sm text-slate-500">Danh sách endpoint dạng FAQ. Mở từng mục để xem cách dùng, mẫu cURL và response.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="group in endpointTabs"
                                :key="group.key"
                                type="button"
                                class="rounded-[8px] border px-3 py-2 text-sm font-semibold transition"
                                :class="
                                    selectedGroup === group.key
                                        ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                                        : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-white hover:text-slate-900'
                                "
                                @click="selectedGroup = group.key"
                            >
                                {{ group.label }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 p-3 md:p-4">
                        <ApiEndpointCard
                            v-for="endpoint in activeEndpoints"
                            :key="endpoint.id"
                            :endpoint="endpoint"
                            :copied-key="copiedKey"
                            @copy="copyToClipboard"
                        />

                        <div v-if="activeEndpoints.length === 0" class="rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-[10px] bg-slate-900 text-white">
                                <ShieldCheck class="h-5 w-5" />
                            </div>
                            <p class="mt-4 text-sm font-semibold text-slate-900">Chưa có API trong nhóm này</p>
                            <p class="mt-1 text-sm text-slate-500">Hãy chọn nhóm khác hoặc kiểm tra lại cấu hình dữ liệu tài liệu API.</p>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-5 xl:self-start">
                <ApiSecurityChecklist :items="securityItems" />
                <ApiFlowSteps :steps="flowSteps" />
                <ApiWebhookStatus :items="webhookStatusItems" />
            </aside>
        </div>
    </div>
</template>
