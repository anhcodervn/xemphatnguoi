<script setup lang="ts">
import UploadImage from '@/components/shared/UpladImage/index.vue';
import { adminCaptchaServiceService } from '@/services/admin-captcha-service.service';
import { adminCaptchaSourceService } from '@/services/admin-captcha-source.service';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import {
    BadgeDollarSign,
    Bot,
    Layers3,
    Pencil,
    Plus,
    RefreshCw,
    Save,
    Search,
    ShieldCheck,
    Sparkles,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface CaptchaSourceOption {
    id: number;
    name: string;
}

interface CaptchaServiceStats {
    sample_size?: number;
    success_rate?: number;
    processing_time_label?: string;
    avg_processing_seconds?: number | null;
}

interface CaptchaServiceSettings {
    success_rate?: number;
    speed_label?: string;
    icon_url?: string;
    request_example_body?: string;
}

interface CaptchaServiceRow {
    id: number;
    code: string;
    name: string;
    category: string;
    description: string | null;
    provider_service_code: string | null;
    default_source_id: number | null;
    sort_order: number;
    base_price: string;
    selling_price: string;
    estimated_seconds: number | null;
    is_active: boolean;
    settings?: CaptchaServiceSettings;
    source?: {
        id: number;
        name: string;
    } | null;
    stats?: CaptchaServiceStats;
}

interface ServiceFormState {
    code: string;
    name: string;
    category: string;
    description: string;
    provider_service_code: string;
    default_source_id: number | null;
    sort_order: number;
    base_price: number;
    selling_price: number;
    estimated_seconds: number;
    is_active: boolean;
    settings: {
        success_rate: number;
        speed_label: string;
        icon_url: string;
        request_example_body: string;
    };
}

const fieldLabelClass = 'mb-1.5 block text-sm font-semibold text-slate-700';
const fieldHintClass = 'mt-1 text-xs leading-5 text-slate-500';
const fieldInputClass =
    'w-full rounded-[10px] border border-teal-100 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-400 focus:ring-2 focus:ring-teal-100';

const emptyForm = (): ServiceFormState => ({
    code: '',
    name: '',
    category: 'image',
    description: '',
    provider_service_code: '',
    default_source_id: null,
    sort_order: 0,
    base_price: 0,
    selling_price: 0,
    estimated_seconds: 15,
    is_active: true,
    settings: {
        success_rate: 99,
        speed_label: '15s',
        icon_url: '',
        request_example_body: '',
    },
});

const rows = ref<CaptchaServiceRow[]>([]);
const sources = ref<CaptchaSourceOption[]>([]);
const loading = ref(true);
const saving = ref(false);
const selectedId = ref<number | null>(null);
const mode = ref<'create' | 'edit'>('create');
const isModalOpen = ref(false);
const keyword = ref('');
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');
const sourceFilter = ref<'all' | string>('all');
const form = ref<ServiceFormState>(emptyForm());

const normalizeForm = (service?: Partial<CaptchaServiceRow> | null): ServiceFormState => {
    const settings = (service?.settings as CaptchaServiceSettings | undefined) ?? {};

    return {
        code: String(service?.code ?? ''),
        name: String(service?.name ?? ''),
        category: String(service?.category ?? 'image'),
        description: String(service?.description ?? ''),
        provider_service_code: String(service?.provider_service_code ?? ''),
        default_source_id: typeof service?.default_source_id === 'number' ? service.default_source_id : null,
        sort_order: Number(service?.sort_order ?? 0),
        base_price: Number(service?.base_price ?? 0),
        selling_price: Number(service?.selling_price ?? 0),
        estimated_seconds: Number(service?.estimated_seconds ?? 15),
        is_active: Boolean(service?.is_active ?? true),
        settings: {
            success_rate: Number(settings.success_rate ?? 99),
            speed_label: String(settings.speed_label ?? `${service?.estimated_seconds ?? 15}s`),
            icon_url: String(settings.icon_url ?? ''),
            request_example_body: String(settings.request_example_body ?? ''),
        },
    };
};

const selectedRow = computed(() => rows.value.find((row) => row.id === selectedId.value) ?? null);

const summaryCards = computed(() => {
    const activeCount = rows.value.filter((row) => row.is_active).length;
    const inactiveCount = rows.value.length - activeCount;
    const autoStatsCount = rows.value.filter((row) => Number(row.stats?.sample_size ?? 0) > 0).length;

    return [
        { label: 'Tổng dịch vụ', value: rows.value.length, icon: Layers3, tone: 'bg-teal-50 text-teal-700' },
        { label: 'Đang hoạt động', value: activeCount, icon: ShieldCheck, tone: 'bg-teal-50 text-teal-700' },
        { label: 'Tạm tắt', value: inactiveCount, icon: X, tone: 'bg-slate-100 text-slate-700' },
        { label: 'Có stats tự động', value: autoStatsCount, icon: Sparkles, tone: 'bg-lime-50 text-lime-700' },
    ];
});

const filteredRows = computed(() => {
    const q = keyword.value.trim().toLowerCase();

    return rows.value.filter((row) => {
        const matchesKeyword =
            q === '' ||
            row.name.toLowerCase().includes(q) ||
            row.code.toLowerCase().includes(q) ||
            row.category.toLowerCase().includes(q) ||
            (row.source?.name ?? '').toLowerCase().includes(q);

        const matchesStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'active' && row.is_active) ||
            (statusFilter.value === 'inactive' && !row.is_active);

        const matchesSource =
            sourceFilter.value === 'all' ||
            String(row.default_source_id ?? '') === sourceFilter.value;

        return matchesKeyword && matchesStatus && matchesSource;
    });
});

const loadData = async (): Promise<void> => {
    try {
        loading.value = true;

        const [serviceResponse, sourceResponse] = await Promise.all([
            adminCaptchaServiceService.list({ per_page: 100 }),
            adminCaptchaSourceService.list({ per_page: 100 }),
        ]);

        rows.value = serviceResponse.services?.data ?? [];
        sources.value = (sourceResponse.sources?.data ?? []).map((source: Record<string, unknown>) => ({
            id: Number(source.id),
            name: String(source.name),
        }));

        if (mode.value === 'edit' && selectedId.value) {
            const latestRow = rows.value.find((row) => row.id === selectedId.value);

            if (latestRow) {
                form.value = normalizeForm(latestRow);
            }
        }
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const closeModal = (): void => {
    isModalOpen.value = false;
    saving.value = false;
};

const openCreateModal = (): void => {
    mode.value = 'create';
    selectedId.value = null;
    form.value = emptyForm();
    isModalOpen.value = true;
};

const openEditModal = (row: CaptchaServiceRow): void => {
    mode.value = 'edit';
    selectedId.value = row.id;
    form.value = normalizeForm(row);
    isModalOpen.value = true;
};

const saveService = async (): Promise<void> => {
    try {
        saving.value = true;

        if (mode.value === 'edit' && selectedId.value) {
            const response = await adminCaptchaServiceService.update(selectedId.value, form.value);
            handleSuccessResponse(response, 'Đã cập nhật dịch vụ captcha.');
        } else {
            const response = await adminCaptchaServiceService.create(form.value);
            handleSuccessResponse(response, 'Đã tạo dịch vụ captcha.');
        }

        await loadData();
        closeModal();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const toggleStatus = async (row: CaptchaServiceRow): Promise<void> => {
    try {
        const payload = {
            ...normalizeForm(row),
            is_active: !row.is_active,
        };

        const response = await adminCaptchaServiceService.update(row.id, payload);
        handleSuccessResponse(response, row.is_active ? 'Đã tắt dịch vụ captcha.' : 'Đã bật dịch vụ captcha.');
        await loadData();
    } catch (error) {
        handleErrorResponse(error);
    }
};

onMounted(async () => {
    await loadData();
});
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-[16px] border border-teal-100 bg-white/95 p-5 shadow-[0_18px_40px_-28px_rgba(8,145,178,0.25)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-teal-700">
                        <Bot class="h-4 w-4" />
                        Captcha Services
                    </div>
                    <h1 class="mt-3 flex items-center gap-3 text-2xl font-black tracking-[-0.04em] text-slate-950">
                        <span class="flex h-11 w-11 items-center justify-center rounded-[12px] bg-[linear-gradient(135deg,_#0f766e_0%,_#06b6d4_100%)] text-white">
                            <Layers3 class="h-5 w-5" />
                        </span>
                        Dịch vụ captcha
                    </h1>
                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Mẫu hiển thị chuẩn của dịch vụ là: icon, tên, tỷ lệ thành công, tốc độ và giá đ /1.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-[10px] border border-teal-100 px-4 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                        @click="loadData"
                    >
                        <RefreshCw class="h-4 w-4" />
                        Làm mới
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-[10px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500"
                        @click="openCreateModal"
                    >
                        <Plus class="h-4 w-4" />
                        Thêm dịch vụ
                    </button>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article v-for="item in summaryCards" :key="item.label" class="rounded-[14px] border border-teal-100 bg-white/95 p-4 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">{{ item.label }}</p>
                        <p class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950">{{ item.value }}</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-[10px]" :class="item.tone">
                        <component :is="item.icon" class="h-5 w-5" />
                    </div>
                </div>
            </article>
        </section>

        <section class="rounded-[14px] border border-teal-100 bg-white/95 p-4 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_170px_180px]">
                <label class="relative block">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="keyword"
                        type="text"
                        class="w-full rounded-[10px] border border-teal-100 bg-white py-2.5 pl-10 pr-3 text-sm outline-none transition focus:border-teal-400 focus:ring-2 focus:ring-teal-100"
                        placeholder="Tìm theo tên, mã, category hoặc nguồn"
                    />
                </label>

                <select v-model="statusFilter" class="rounded-[10px] border border-teal-100 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-400 focus:ring-2 focus:ring-teal-100">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active">Đang hoạt động</option>
                    <option value="inactive">Tạm tắt</option>
                </select>

                <select v-model="sourceFilter" class="rounded-[10px] border border-teal-100 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-400 focus:ring-2 focus:ring-teal-100">
                    <option value="all">Tất cả nguồn</option>
                    <option v-for="source in sources" :key="source.id" :value="String(source.id)">
                        {{ source.name }}
                    </option>
                </select>
            </div>
        </section>

        <section class="space-y-3">
            <article
                v-for="row in filteredRows"
                :key="row.id"
                class="rounded-[14px] border border-teal-100 bg-white/95 p-4 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)] transition hover:border-teal-200"
            >
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-[14px] bg-teal-50 text-teal-700">
                            <img
                                v-if="row.settings?.icon_url"
                                :src="row.settings.icon_url"
                                :alt="`${row.name} icon`"
                                class="h-full w-full object-cover"
                            />
                            <Bot v-else class="h-6 w-6" />
                        </span>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-950">{{ row.name }}</h2>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="row.is_active ? 'bg-teal-50 text-teal-700' : 'bg-rose-50 text-rose-700'"
                                >
                                    {{ row.is_active ? 'active' : 'inactive' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ row.code }} · {{ row.source?.name || 'Chưa chọn nguồn' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[460px]">
                        <div class="rounded-[10px] bg-teal-50/70 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Tỷ lệ thành công</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">{{ row.stats?.success_rate || 99 }}%</p>
                        </div>
                        <div class="rounded-[10px] bg-teal-50/70 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Tốc độ</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">{{ row.stats?.processing_time_label || `${row.estimated_seconds || 15}s` }}</p>
                        </div>
                        <div class="rounded-[10px] bg-teal-50/70 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Giá đ /1</p>
                            <p class="mt-2 text-lg font-bold text-slate-950">{{ row.selling_price }}? /1</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-[10px] border border-teal-100 px-3 py-2 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                            @click="openEditModal(row)"
                        >
                            <Pencil class="h-4 w-4" />
                            Chỉnh sửa
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-[10px] px-3 py-2 text-sm font-semibold transition"
                            :class="row.is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-teal-50 text-teal-700 hover:bg-teal-100'"
                            @click="toggleStatus(row)"
                        >
                            {{ row.is_active ? 'Tắt dịch vụ' : 'Bật dịch vụ' }}
                        </button>
                    </div>
                </div>
            </article>

            <section
                v-if="!loading && filteredRows.length === 0"
                class="rounded-[14px] border border-dashed border-teal-200 bg-white/95 px-4 py-12 text-center text-sm text-slate-500 shadow-[0_16px_32px_-28px_rgba(8,145,178,0.28)]"
            >
                <p>Không có dịch vụ nào khớp với bộ lọc hiện tại.</p>
                <button
                    type="button"
                    class="mt-4 inline-flex items-center gap-2 rounded-[10px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500"
                    @click="openCreateModal"
                >
                    <Plus class="h-4 w-4" />
                    Thêm dịch vụ mới
                </button>
            </section>
        </section>

        <Teleport to="body">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-teal-950/35 p-4">
                <div class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[18px] border border-teal-100 bg-white shadow-2xl">
                    <div class="flex items-start justify-between gap-4 border-b border-teal-100 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                {{ mode === 'edit' ? 'edit mode' : 'create mode' }}
                            </p>
                            <h2 class="mt-2 flex items-center gap-3 text-2xl font-black tracking-[-0.04em] text-slate-950">
                                <span class="flex h-11 w-11 items-center justify-center rounded-[12px] bg-[linear-gradient(135deg,_#0f766e_0%,_#06b6d4_100%)] text-white">
                                    <Bot class="h-5 w-5" />
                                </span>
                                {{ mode === 'edit' ? 'Chỉnh sửa dịch vụ captcha' : 'Thêm dịch vụ captcha' }}
                            </h2>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ mode === 'edit' ? selectedRow?.name || 'Đang chọn dịch vụ' : 'Nhập đầy đủ thông tin để tạo một dịch vụ captcha mới.' }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-[10px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="saving"
                                @click="saveService"
                            >
                                <Save class="h-4 w-4" />
                                {{ saving ? 'Đang lưu...' : mode === 'edit' ? 'Lưu' : 'Tạo dịch vụ' }}
                            </button>

                            <button
                                type="button"
                                class="rounded-[12px] border border-teal-100 p-2 text-teal-700 transition hover:bg-teal-50 hover:text-teal-900"
                                @click="closeModal"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <div class="grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_320px]">
                            <div class="grid gap-5">
                                <section class="space-y-4 rounded-[14px] border border-teal-100 p-4">
                                    <div class="flex items-center gap-2">
                                        <Bot class="h-4 w-4 text-teal-600" />
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900">Thông tin cơ bản</h3>
                                            <p class="text-xs text-slate-500">Thông tin nhận diện và mapping dịch vụ với nguồn solve.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label>
                                            <span :class="fieldLabelClass">Tên dịch vụ</span>
                                            <input v-model="form.name" type="text" :class="fieldInputClass" placeholder="Ví dụ: ReCaptcha v2 Token" />
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">Mã dịch vụ nội bộ</span>
                                            <input v-model="form.code" type="text" :class="fieldInputClass" placeholder="Ví dụ: recaptcha-v2-token" />
                                            <p :class="fieldHintClass">Mã này dùng cho API và routing nội bộ.</p>
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">Nhóm captcha</span>
                                            <input v-model="form.category" type="text" :class="fieldInputClass" placeholder="Ví dụ: token, image, turnstile" />
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">Mã dịch vụ từ nhà cung cấp</span>
                                            <input v-model="form.provider_service_code" type="text" :class="fieldInputClass" placeholder="Ví dụ: userrecaptcha" />
                                            <p :class="fieldHintClass">Đây là mã service của bên solve thứ 3 để backend map khi gọi API.</p>
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">Nguồn solve mặc định</span>
                                            <select v-model="form.default_source_id" :class="fieldInputClass">
                                                <option :value="null">Chọn nguồn solve mặc định</option>
                                                <option v-for="source in sources" :key="source.id" :value="source.id">{{ source.name }}</option>
                                            </select>
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">Thứ tự hiển thị</span>
                                            <input v-model.number="form.sort_order" type="number" min="0" :class="fieldInputClass" placeholder="0" />
                                            <p :class="fieldHintClass">Số nhỏ hơn sẽ hiển thị trước.</p>
                                        </label>

                                        <label class="rounded-[10px] border border-teal-100 bg-teal-50/60 px-3 py-3">
                                            <span :class="fieldLabelClass">Trạng thái dịch vụ</span>
                                            <span class="flex items-center gap-3 text-sm text-slate-700">
                                                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                                Kích hoạt ngay sau khi lưu
                                            </span>
                                        </label>
                                    </div>

                                    <label class="block">
                                        <span :class="fieldLabelClass">Mô tả dịch vụ</span>
                                        <textarea
                                            v-model="form.description"
                                            rows="4"
                                            :class="fieldInputClass"
                                            placeholder="Mô tả ngắn để admin và client dễ phân biệt dịch vụ này dùng cho trường hợp nào"
                                        ></textarea>
                                    </label>

                                    <label class="block">
                                        <span :class="fieldLabelClass">Body request mẫu (JSON)</span>
                                        <textarea
                                            v-model="form.settings.request_example_body"
                                            rows="8"
                                            :class="`${fieldInputClass} font-mono text-[13px] leading-6`"
                                            placeholder='{
  "website_url": "https://example.com/login",
  "website_key": "6Ldemo_site_key"
}'
                                        ></textarea>
                                        <p :class="fieldHintClass">Dùng để hiển thị ở tài liệu API cho service này. Chỉ nhập phần body nằm trong `task`.</p>
                                    </label>
                                </section>

                                <section class="space-y-4 rounded-[14px] border border-teal-100 p-4">
                                    <div class="flex items-center gap-2">
                                        <BadgeDollarSign class="h-4 w-4 text-teal-600" />
                                        <div>
                                            <h3 class="text-sm font-semibold text-slate-900">Giá và tốc độ</h3>
                                            <p class="text-xs text-slate-500">Chỉ giữ các thông số sẽ được show ra ngoài: tỷ lệ, tốc độ và giá đ /1.</p>
                                        </div>
                                    </div>

                                    <div class="grid gap-4 md:grid-cols-3">
                                        <label>
                                            <span :class="fieldLabelClass">Giá gốc đ /1</span>
                                            <input v-model.number="form.base_price" type="number" min="0" step="0.0001" :class="fieldInputClass" placeholder="0.5" />
                                            <p :class="fieldHintClass">Nhập giá gốc cho 1 lượt solve, đơn vị đ.</p>
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">Giá bán đ /1</span>
                                            <input v-model.number="form.selling_price" type="number" min="0" step="0.0001" :class="fieldInputClass" placeholder="1" />
                                            <p :class="fieldHintClass">Nhập giá bán cho 1 lượt solve, đơn vị đ.</p>
                                        </label>

                                        <label>
                                            <span :class="fieldLabelClass">ETA mặc định (giây)</span>
                                            <input v-model.number="form.estimated_seconds" type="number" min="1" :class="fieldInputClass" placeholder="15" />
                                        </label>
                                    </div>

                                    <div class="rounded-[12px] border border-dashed border-teal-200 bg-teal-50/70 p-4">
                                        <div class="flex items-center gap-2">
                                            <Sparkles class="h-4 w-4 text-amber-600" />
                                            <div>
                                                <h4 class="text-sm font-semibold text-slate-900">Fallback hiển thị</h4>
                                                <p class="text-xs text-slate-500">Chỉ dùng khi dịch vụ chưa có đủ dữ liệu thực tế từ 100 task gần nhất.</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                                            <label>
                                                <span :class="fieldLabelClass">Tỷ lệ thành công fallback (%)</span>
                                                <input
                                                    v-model.number="form.settings.success_rate"
                                                    type="number"
                                                    min="1"
                                                    max="100"
                                                    :class="fieldInputClass"
                                                    placeholder="99"
                                                />
                                            </label>

                                            <label>
                                                <span :class="fieldLabelClass">Tốc độ fallback</span>
                                                <input
                                                    v-model="form.settings.speed_label"
                                                    type="text"
                                                    :class="fieldInputClass"
                                                    placeholder="Ví dụ: 8-15s"
                                                />
                                            </label>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <aside class="space-y-4 rounded-[14px] border border-teal-100 bg-teal-50/70 p-4">
                                <div class="flex items-center gap-2">
                                    <Bot class="h-4 w-4 text-fuchsia-600" />
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Icon captcha</h3>
                                        <p class="text-xs text-slate-500">Chỉ cần icon tượng trưng để hiển thị ở danh sách dịch vụ.</p>
                                    </div>
                                </div>

                                <div class="rounded-[12px] border border-teal-100 bg-white p-4">
                                    <label class="block">
                                        <span :class="fieldLabelClass">Icon captcha</span>
                                        <div class="mt-2 flex h-24 items-center justify-center overflow-hidden rounded-[12px] bg-teal-50">
                                            <img
                                                v-if="form.settings.icon_url"
                                                :src="form.settings.icon_url"
                                                alt="captcha icon"
                                                class="h-full w-full object-cover"
                                            />
                                            <Bot v-else class="h-8 w-8 text-slate-300" />
                                        </div>
                                        <p :class="fieldHintClass">Khuyến nghị ảnh vuông nhỏ, dùng làm icon hiển thị ở client và landing.</p>
                                    </label>
                                    <div class="mt-3">
                                        <UploadImage
                                            :image-src="form.settings.icon_url"
                                            :accept="['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']"
                                            :compress="true"
                                            @uploaded="(url: string) => (form.settings.icon_url = url)"
                                        />
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div class="sticky bottom-0 flex flex-wrap items-center justify-between gap-3 border-t border-teal-100 bg-white px-6 py-4">
                        <p class="text-xs text-slate-500">
                            {{ mode === 'edit' ? 'Bạn đang cập nhật dịch vụ đã tồn tại.' : 'Dịch vụ mới sẽ hiển thị theo cấu hình sau khi lưu.' }}
                        </p>

                        <div class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="rounded-[10px] border border-teal-100 px-4 py-2.5 text-sm font-semibold text-teal-700 transition hover:bg-teal-50"
                                @click="closeModal"
                            >
                                Hủy
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-[10px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="saving"
                                @click="saveService"
                            >
                                <Save class="h-4 w-4" />
                                {{ saving ? 'Đang lưu...' : mode === 'edit' ? 'Lưu cập nhật' : 'Tạo dịch vụ' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

