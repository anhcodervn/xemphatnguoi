<script setup lang="ts">
import { adminPackageService, type PackagePayload } from '@/services/admin-package.service';
import type { PackageLimitsType } from '@/types/user-subscription.type';
import { handleErrorResponse } from '@/utils/response';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const inputClass =
    'block h-11 w-full rounded border border-gray-300 px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500';
const textareaClass =
    'block w-full rounded border border-gray-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500';
const checkboxCardClass = 'flex items-center gap-3 rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-slate-700';

const processing = ref(false);
const isSlugEdited = ref(false);

const defaultLimits = (): PackageLimitsType => ({
    max_cron_jobs: 3,
    min_interval_seconds: 900,
    max_logs_per_job: 100,
    max_request_timeout_seconds: 10,
    max_response_size_kb: 5,
    max_retries_per_run: 0,
    max_headers_count: 5,
    max_body_size_kb: 5,
    allowed_methods: ['GET'],
    allow_custom_headers: false,
    allow_custom_body: false,
    allow_cron_expression: false,
    allow_run_now: false,
    allow_alerts: false,
    max_alert_channels: 0,
    monthly_run_quota: 1000,
    daily_run_quota: 100,
    concurrent_runs_limit: 1,
    priority: 'low',
    queue_name: 'cron-low',
    allow_expected_body_check: false,
    allow_webhook_alert: false,
    allow_discord_alert: false,
    allow_telegram_alert: false,
    allow_email_alert: true,
});

const availableMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

const form = ref<PackagePayload>({
    name: '',
    slug: '',
    description: '',
    price: 0,
    duration_days: 30,
    account_limit: 0,
    can_buy_extra_account: false,
    extra_account_price: 0,
    request_limit: 0,
    request_per_minute: 1,
    concurrent_limit: 1,
    features: [],
    package_limits: defaultLimits(),
    status: 'active',
});

const packageId = computed(() => route.params.package_id as string | undefined);
const isEditing = computed(() => Boolean(packageId.value));

const featuresInput = computed({
    get: () => form.value.features.join('\n'),
    set: (value: string) => {
        form.value.features = value
            .split('\n')
            .map((item) => item.trim())
            .filter(Boolean);
    },
});

watch(
    () => form.value.name,
    (value) => {
        if (!isSlugEdited.value) {
            form.value.slug = slugify(value);
        }
    },
);

watch(
    () => form.value.package_limits.min_interval_seconds,
    (value) => {
        form.value.request_per_minute = Math.max(1, Math.floor(60 / Math.max(1, value)));
    },
);

watch(
    () => form.value.package_limits.max_cron_jobs,
    (value) => {
        form.value.account_limit = value;
    },
);

watch(
    () => form.value.package_limits.monthly_run_quota,
    (value) => {
        form.value.request_limit = value ?? 0;
    },
);

watch(
    () => form.value.package_limits.concurrent_runs_limit,
    (value) => {
        form.value.concurrent_limit = value;
    },
);

const toggleMethod = (method: string): void => {
    const methods = form.value.package_limits.allowed_methods;

    if (methods.includes(method)) {
        form.value.package_limits.allowed_methods = methods.filter((item) => item !== method);
        return;
    }

    form.value.package_limits.allowed_methods = [...methods, method];
};

async function loadPackage(): Promise<void> {
    if (!packageId.value) {
        return;
    }

    try {
        const data = await adminPackageService.get(packageId.value);

        form.value = {
            name: data.name,
            slug: data.slug,
            description: data.description ?? '',
            price: Number(data.price),
            duration_days: data.duration_days,
            account_limit: data.account_limit,
            can_buy_extra_account: false,
            extra_account_price: 0,
            request_limit: data.request_limit ?? 0,
            request_per_minute: data.request_per_minute ?? 1,
            concurrent_limit: data.concurrent_limit,
            features: Array.isArray(data.features) ? data.features : [],
            package_limits: {
                ...defaultLimits(),
                ...(data.package_limits ?? {}),
            },
            status: data.status,
        };

        isSlugEdited.value = true;
    } catch (error) {
        handleErrorResponse(error);
        await router.push('/admin/packages');
    }
}

async function submitForm(): Promise<void> {
    try {
        processing.value = true;

        if (form.value.package_limits.allowed_methods.length === 0) {
            form.value.package_limits.allowed_methods = ['GET'];
        }

        const response = isEditing.value
            ? await adminPackageService.update(packageId.value!, form.value)
            : await adminPackageService.create(form.value);

        await Swal.fire('Thành công', response.data.message, 'success');
        await router.push('/admin/packages');
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        processing.value = false;
    }
}

function slugify(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

onMounted(async () => {
    await loadPackage();
});
</script>

<template>
    <div class="mx-auto w-full max-w-6xl">
        <div class="rounded border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-6 flex flex-col gap-3 border-b border-gray-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ isEditing ? 'Cập nhật gói AutoCron' : 'Tạo gói AutoCron' }}</h2>
                    <p class="text-sm text-slate-500">Tinh chỉnh package limits cho cron jobs, logs, alerts, queue và usage quota.</p>
                </div>

                <RouterLink
                    to="/admin/packages"
                    class="inline-flex items-center justify-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Quay lại danh sách
                </RouterLink>
            </div>

            <form class="space-y-6" @submit.prevent="submitForm">
                <section class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-slate-700" for="package-name">Tên gói</label>
                        <input id="package-name" v-model="form.name" type="text" :class="inputClass" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-slug">Slug</label>
                        <input id="package-slug" v-model="form.slug" type="text" :class="inputClass" @input="isSlugEdited = true" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-status">Trạng thái</label>
                        <select id="package-status" v-model="form.status" :class="inputClass">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-slate-700" for="package-description">Mô tả</label>
                        <textarea id="package-description" v-model="form.description" rows="4" :class="textareaClass" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-price">Giá</label>
                        <input id="package-price" v-model.number="form.price" type="number" min="0" step="0.01" :class="inputClass" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-duration-days">Số ngày</label>
                        <input id="package-duration-days" v-model.number="form.duration_days" type="number" min="1" step="1" :class="inputClass" />
                    </div>
                </section>

                <section class="space-y-4 border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Giới hạn chính</h3>
                        <p class="text-sm text-slate-500">Điều khiển số cron jobs, số lượng logs giữ lại, quota và concurrency.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max cron jobs</label>
                            <input v-model.number="form.package_limits.max_cron_jobs" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Min interval seconds</label>
                            <input v-model.number="form.package_limits.min_interval_seconds" type="number" min="1" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max logs per job</label>
                            <input v-model.number="form.package_limits.max_logs_per_job" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Monthly run quota</label>
                            <input v-model.number="form.package_limits.monthly_run_quota" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Daily run quota</label>
                            <input v-model.number="form.package_limits.daily_run_quota" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Concurrent runs limit</label>
                            <input v-model.number="form.package_limits.concurrent_runs_limit" type="number" min="1" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max alert channels</label>
                            <input v-model.number="form.package_limits.max_alert_channels" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4 border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">HTTP và queue</h3>
                        <p class="text-sm text-slate-500">Giới hạn timeout, response preview, body size, retry và queue priority.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max timeout seconds</label>
                            <input v-model.number="form.package_limits.max_request_timeout_seconds" type="number" min="1" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max response size KB</label>
                            <input v-model.number="form.package_limits.max_response_size_kb" type="number" min="1" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max retries per run</label>
                            <input v-model.number="form.package_limits.max_retries_per_run" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max body size KB</label>
                            <input v-model.number="form.package_limits.max_body_size_kb" type="number" min="1" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Max headers count</label>
                            <input v-model.number="form.package_limits.max_headers_count" type="number" min="0" step="1" :class="inputClass" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Priority</label>
                            <select v-model="form.package_limits.priority" :class="inputClass">
                                <option value="low">low</option>
                                <option value="normal">normal</option>
                                <option value="high">high</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-slate-700">Queue name</label>
                            <select v-model="form.package_limits.queue_name" :class="inputClass">
                                <option value="cron-low">cron-low</option>
                                <option value="cron-default">cron-default</option>
                                <option value="cron-high">cron-high</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="method in availableMethods"
                            :key="method"
                            type="button"
                            class="rounded border px-3 py-2 text-sm font-medium"
                            :class="
                                form.package_limits.allowed_methods.includes(method)
                                    ? 'border-sky-500 bg-sky-50 text-sky-700'
                                    : 'border-gray-300 bg-white text-gray-600'
                            "
                            @click="toggleMethod(method)"
                        >
                            {{ method }}
                        </button>
                    </div>
                </section>

                <section class="space-y-4 border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Tính năng mở rộng</h3>
                        <p class="text-sm text-slate-500">Bật tắt custom request, alert channels, body validation và run-now.</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_custom_headers" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow custom headers</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_custom_body" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow custom body</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_cron_expression" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow cron expression</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_run_now" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow run now</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_alerts" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow alerts</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_expected_body_check" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow expected body check</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_webhook_alert" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow webhook alert</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_discord_alert" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow Discord alert</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_telegram_alert" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow Telegram alert</span>
                        </label>
                        <label :class="checkboxCardClass">
                            <input v-model="form.package_limits.allow_email_alert" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            <span>Allow email alert</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-features">Feature bullets</label>
                        <textarea
                            id="package-features"
                            v-model="featuresInput"
                            rows="6"
                            :class="textareaClass"
                            placeholder="Mỗi dòng là một bullet hiển thị bên client"
                        />
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end">
                    <RouterLink
                        to="/admin/packages"
                        class="inline-flex items-center justify-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Hủy
                    </RouterLink>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-blue-300"
                        :disabled="processing"
                    >
                        {{ processing ? 'Đang lưu...' : isEditing ? 'Cập nhật gói' : 'Tạo gói' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
