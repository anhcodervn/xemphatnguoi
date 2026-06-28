<script setup lang="ts">
import { clientCronAlertService } from '@/services/client-cron-alert.service';
import { clientCronJobService, type CronAlertChannelItem, type CronJobPayload } from '@/services/client-cron-job.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const saving = ref(false);
const loading = ref(false);
const alertChannels = ref<CronAlertChannelItem[]>([]);

const form = reactive<CronJobPayload>({
    name: '',
    group_name: '',
    description: '',
    url: '',
    method: 'GET',
    headers: [],
    body_type: 'none',
    body: '',
    query_params: [],
    cron_expression: '',
    interval_seconds: 300,
    timezone: 'Asia/Ho_Chi_Minh',
    timeout_seconds: 10,
    connect_timeout_seconds: 5,
    retry_count: 0,
    retry_delay_seconds: 30,
    max_response_size_kb: 20,
    expected_status_codes: [200],
    expected_body_contains: '',
    expected_body_not_contains: '',
    follow_redirects: false,
    verify_ssl: true,
    alert_channel_ids: [],
});

const isEditing = computed(() => Boolean(route.params.cron_job_id));
const cronJobId = computed(() => route.params.cron_job_id as string | undefined);

const addHeader = (): void => {
    form.headers = [...(form.headers ?? []), { key: '', value: '' }];
};

const addQuery = (): void => {
    form.query_params = [...(form.query_params ?? []), { key: '', value: '' }];
};

const removeHeader = (index: number): void => {
    form.headers = (form.headers ?? []).filter((_, itemIndex) => itemIndex !== index);
};

const removeQuery = (index: number): void => {
    form.query_params = (form.query_params ?? []).filter((_, itemIndex) => itemIndex !== index);
};

const loadAlertChannels = async (): Promise<void> => {
    const response = await clientCronAlertService.list({ per_page: 100 });
    alertChannels.value = response.data;
};

const loadJob = async (): Promise<void> => {
    if (!cronJobId.value) {
        return;
    }

    loading.value = true;

    try {
        const response = await clientCronJobService.get(cronJobId.value);
        const job = response.cron_job;
        form.name = job.name;
        form.group_name = job.group_name ?? '';
        form.description = job.description ?? '';
        form.url = job.url;
        form.method = job.method;
        form.headers = Object.entries(job.headers ?? {}).map(([key, value]) => ({ key, value }));
        form.body_type = job.body_type;
        form.body = job.body ?? '';
        form.query_params = Object.entries(job.query_params ?? {}).map(([key, value]) => ({ key, value }));
        form.cron_expression = job.cron_expression ?? '';
        form.interval_seconds = job.interval_seconds;
        form.timezone = job.timezone;
        form.timeout_seconds = job.timeout_seconds;
        form.connect_timeout_seconds = job.connect_timeout_seconds;
        form.retry_count = job.retry_count;
        form.retry_delay_seconds = job.retry_delay_seconds;
        form.max_response_size_kb = job.max_response_size_kb;
        form.expected_status_codes = job.expected_status_codes ?? [200];
        form.expected_body_contains = job.expected_body_contains ?? '';
        form.expected_body_not_contains = job.expected_body_not_contains ?? '';
        form.follow_redirects = job.follow_redirects;
        form.verify_ssl = job.verify_ssl;
        form.alert_channel_ids = (job.alert_channels ?? []).map((item) => item.id);
    } catch (error) {
        handleErrorResponse(error);
        await router.push('/cron-jobs');
    } finally {
        loading.value = false;
    }
};

const submit = async (): Promise<void> => {
    saving.value = true;

    try {
        const payload: CronJobPayload = {
            ...form,
            group_name: form.group_name || null,
            headers: (form.headers ?? []).filter((item) => item.key.trim() !== ''),
            query_params: (form.query_params ?? []).filter((item) => item.key.trim() !== ''),
            cron_expression: form.cron_expression || null,
            expected_body_contains: form.expected_body_contains || null,
            expected_body_not_contains: form.expected_body_not_contains || null,
        };

        const response = isEditing.value && cronJobId.value
            ? await clientCronJobService.update(cronJobId.value, payload)
            : await clientCronJobService.create(payload);

        handleSuccessResponse(response, isEditing.value ? 'Đã cập nhật cron job.' : 'Đã tạo cron job.');
        await router.push('/cron-jobs');
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await Promise.all([loadAlertChannels(), loadJob()]);
});
</script>

<template>
    <div class="mx-auto max-w-5xl space-y-5 pb-4">
        <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-3xl font-black tracking-[-0.04em] text-slate-950">{{ isEditing ? 'Cập nhật Cron Job' : 'Tạo Cron Job' }}</h1>
            <p class="mt-2 text-sm leading-7 text-slate-600">Cấu hình endpoint, lịch chạy, timeout, điều kiện kiểm tra phản hồi và kênh cảnh báo trong cùng một form.</p>
        </section>

        <form class="space-y-5" @submit.prevent="submit">
            <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Thông tin cơ bản</h2>
                        <p class="mt-1 text-sm text-slate-500">Đặt tên dễ nhớ để sau này lọc, tìm kiếm và quản lý nhiều cron jobs thuận tiện hơn.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Tên cron job</span>
                        <input v-model="form.name" type="text" placeholder="Ví dụ: Ping homepage" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Nhóm</span>
                        <input
                            v-model="form.group_name"
                            type="text"
                            placeholder="Ví dụ: Monitoring, Billing..."
                            class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500"
                        />
                    </label>
                    <label class="space-y-1.5 md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">URL endpoint</span>
                        <input v-model="form.url" type="text" placeholder="https://example.com/endpoint" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Method</span>
                        <select v-model="form.method" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Timezone</span>
                        <input v-model="form.timezone" type="text" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                    <label class="space-y-1.5 md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Mô tả</span>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Mô tả ngắn về mục đích của task này..."
                            class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-500"
                        />
                    </label>
                </div>
            </section>

            <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Schedule</h2>
                    <p class="mt-1 text-sm text-slate-500">`Schedule` là cách hệ thống biết khi nào cần chạy task. Bạn có thể dùng khoảng lặp cố định hoặc cron expression nâng cao.</p>
                </div>

                <div class="mt-4 grid gap-3 rounded-[10px] border border-sky-100 bg-sky-50/70 p-4 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-900">Interval seconds:</span> chạy lặp lại sau mỗi N giây. Ví dụ `300` nghĩa là chạy mỗi 5 phút.</p>
                    <p><span class="font-semibold text-slate-900">Cron expression:</span> dùng khi bạn muốn lịch linh hoạt hơn, ví dụ `*/5 * * * *` để chạy mỗi 5 phút.</p>
                    <p><span class="font-semibold text-slate-900">Ưu tiên:</span> nếu có cron expression thì hệ thống sẽ dùng cron expression; nếu để trống thì dùng interval seconds.</p>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Interval seconds</span>
                        <input v-model.number="form.interval_seconds" type="number" min="60" placeholder="Ví dụ: 300 = 5 phút" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Cron expression</span>
                        <input v-model="form.cron_expression" type="text" placeholder="Ví dụ: */5 * * * *" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                </div>
            </section>

            <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Headers và Query</h2>
                        <p class="mt-1 text-sm text-slate-500">Thêm tham số URL hoặc header nếu endpoint yêu cầu token, API key hoặc filter dữ liệu.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <button type="button" class="inline-flex items-center justify-center gap-1 rounded-[8px] border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="addHeader">
                            <Plus class="h-3.5 w-3.5" />
                            Header
                        </button>
                        <button type="button" class="inline-flex items-center justify-center gap-1 rounded-[8px] border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="addQuery">
                            <Plus class="h-3.5 w-3.5" />
                            Query param
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="space-y-3">
                        <p class="text-sm font-semibold text-slate-700">Headers</p>
                        <div v-for="(item, index) in form.headers" :key="`header-${index}`" class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_108px]">
                            <input v-model="item.key" type="text" placeholder="Header key" class="h-10 min-w-0 rounded-[8px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                            <input v-model="item.value" type="text" placeholder="Header value" class="h-10 min-w-0 rounded-[8px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                            <button type="button" class="inline-flex h-10 items-center justify-center rounded-[8px] border border-rose-200 px-3 text-rose-700 hover:bg-rose-50" @click="removeHeader(index)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm font-semibold text-slate-700">Query params</p>
                        <div v-for="(item, index) in form.query_params" :key="`query-${index}`" class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_108px]">
                            <input v-model="item.key" type="text" placeholder="Param key" class="h-10 min-w-0 rounded-[8px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                            <input v-model="item.value" type="text" placeholder="Param value" class="h-10 min-w-0 rounded-[8px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                            <button type="button" class="inline-flex h-10 items-center justify-center rounded-[8px] border border-rose-200 px-3 text-rose-700 hover:bg-rose-50" @click="removeQuery(index)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Request và Validation</h2>
                    <p class="mt-1 text-sm text-slate-500">Dùng phần này để cấu hình body gửi đi và định nghĩa thế nào là một lần chạy thành công.</p>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Body type</span>
                        <select v-model="form.body_type" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500">
                            <option value="none">No body</option>
                            <option value="json">JSON</option>
                            <option value="form">Form</option>
                            <option value="raw">Raw</option>
                        </select>
                    </label>
                    <input v-model="form.expected_status_codes" type="text" disabled class="hidden" />
                    <label class="space-y-1.5 md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Request body</span>
                        <textarea v-model="form.body" rows="5" placeholder='Ví dụ: {"ping":"pong"}' class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-500" />
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Body phải chứa</span>
                        <input v-model="form.expected_body_contains" type="text" placeholder="Nội dung phản hồi phải chứa..." class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Body không được chứa</span>
                        <input v-model="form.expected_body_not_contains" type="text" placeholder="Nội dung phản hồi không được chứa..." class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>
                </div>
            </section>

            <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Timeout, Retry và Alerts</h2>
                    <p class="mt-1 text-sm text-slate-500">Điều chỉnh thời gian chờ, số lần thử lại và các kênh nhận cảnh báo khi task lỗi hoặc timeout.</p>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Timeout tổng</span>
                        <p class="text-xs text-slate-500">Thời gian tối đa cho toàn bộ request trước khi bị đánh dấu timeout.</p>
                        <input v-model.number="form.timeout_seconds" type="number" min="1" placeholder="Ví dụ: 10 giây" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Timeout kết nối</span>
                        <p class="text-xs text-slate-500">Thời gian chờ để mở kết nối tới server đích.</p>
                        <input v-model.number="form.connect_timeout_seconds" type="number" min="1" placeholder="Ví dụ: 5 giây" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Số lần thử lại</span>
                        <p class="text-xs text-slate-500">Bao nhiêu lần hệ thống thử chạy lại khi request lỗi.</p>
                        <input v-model.number="form.retry_count" type="number" min="0" placeholder="Ví dụ: 0, 1 hoặc 2" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>

                    <label class="space-y-1.5">
                        <span class="text-sm font-semibold text-slate-700">Delay giữa các lần retry</span>
                        <p class="text-xs text-slate-500">Số giây chờ trước khi hệ thống thử lại lần tiếp theo.</p>
                        <input v-model.number="form.retry_delay_seconds" type="number" min="1" placeholder="Ví dụ: 30 giây" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>

                    <label class="space-y-1.5 md:col-span-2">
                        <span class="text-sm font-semibold text-slate-700">Giới hạn response preview</span>
                        <p class="text-xs text-slate-500">Dung lượng tối đa được lưu vào log để xem nhanh nội dung phản hồi.</p>
                        <input v-model.number="form.max_response_size_kb" type="number" min="1" placeholder="Ví dụ: 20 KB" class="h-11 w-full rounded-[10px] border border-slate-200 px-3 text-sm outline-none focus:border-sky-500" />
                    </label>

                    <div class="grid grid-cols-1 gap-3 md:col-span-2 md:grid-cols-2">
                        <label class="space-y-2 rounded-[10px] border border-slate-200 px-3 py-3 text-sm text-slate-700">
                            <span class="font-semibold text-slate-700">Follow redirects</span>
                            <p class="text-xs text-slate-500">Cho phép request đi tiếp nếu server trả về mã chuyển hướng 3xx.</p>
                            <span class="flex items-center gap-2">
                                <input v-model="form.follow_redirects" type="checkbox" />
                                <span>Bật chuyển hướng tự động</span>
                            </span>
                        </label>

                        <label class="space-y-2 rounded-[10px] border border-slate-200 px-3 py-3 text-sm text-slate-700">
                            <span class="font-semibold text-slate-700">Verify SSL</span>
                            <p class="text-xs text-slate-500">Kiểm tra chứng chỉ SSL của website đích trước khi gửi request.</p>
                            <span class="flex items-center gap-2">
                                <input v-model="form.verify_ssl" type="checkbox" />
                                <span>Bật xác minh SSL</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 rounded-[10px] border border-slate-200 bg-slate-50 px-4 py-4">
                    <p class="text-sm font-semibold text-slate-700">Alert channels</p>
                    <p class="mt-1 text-sm text-slate-500">Chọn các kênh sẽ nhận cảnh báo khi cron job fail, timeout hoặc cần theo dõi đặc biệt.</p>
                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        <label v-for="channel in alertChannels" :key="channel.id" class="flex items-center gap-2 rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                            <input v-model="form.alert_channel_ids" :value="channel.id" type="checkbox" />
                            {{ channel.name }} • {{ channel.type }}
                        </label>
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="w-full rounded-[10px] border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto" @click="router.push('/cron-jobs')">
                    Hủy
                </button>
                <button type="submit" class="w-full rounded-[10px] bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 sm:w-auto" :disabled="saving || loading">
                    {{ saving ? 'Đang lưu...' : isEditing ? 'Cập nhật cron job' : 'Tạo cron job' }}
                </button>
            </div>
        </form>
    </div>
</template>
