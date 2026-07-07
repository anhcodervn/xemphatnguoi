<script setup lang="ts">
import Breadcrumb from "@/components/MasterLayouts/Breadcrumb/index.vue";
import { adminCaptchaServiceService } from "@/services/admin-captcha-service.service";
import { adminPackageService, type AdminPackageItem, type AdminPackagePayload } from "@/services/admin-package.service";
import { handleErrorResponse, handleSuccessResponse } from "@/utils/response";
import { Plus, Save, Trash2, X } from "lucide-vue-next";
import Swal from "sweetalert2";
import { computed, onMounted, ref } from "vue";

const loading = ref(true);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref<number | null>(null);
const services = ref<Array<{ code: string; name: string }>>([]);
const packageList = ref<AdminPackageItem[]>([]);
const summary = ref({ total: 0, active: 0, inactive: 0 });
const filters = ref({
    search: "",
    status: "",
});

const emptyForm = (): AdminPackagePayload => ({
    name: "",
    slug: "",
    description: "",
    price: 0,
    duration_days: 30,
    features: [],
    status: "active",
    package_limits: {
        max_api_keys: 1,
        requests_per_minute: 60,
        monthly_captcha_quota: 1000,
        max_concurrent_tasks: 5,
        max_whitelisted_ips: 10,
        supports_callback: false,
        supports_priority_queue: false,
        supports_manual_review: false,
        service_whitelist: [],
    },
});

const form = ref<AdminPackagePayload>(emptyForm());
const featureInput = ref("");
const serviceWhitelistInput = computed({
    get: () => form.value.package_limits.service_whitelist.join("\n"),
    set: (value: string) => {
        form.value.package_limits.service_whitelist = value
            .split(/\r?\n|,/)
            .map((item) => item.trim())
            .filter(Boolean);
    },
});

const filteredPackages = computed(() =>
    packageList.value.filter((item) => {
        const matchesSearch =
            filters.value.search.trim() === "" ||
            [item.name, item.slug].some((value) => value.toLowerCase().includes(filters.value.search.trim().toLowerCase()));
        const matchesStatus = filters.value.status === "" || item.status === filters.value.status;

        return matchesSearch && matchesStatus;
    }),
);

const loadData = async (): Promise<void> => {
    try {
        loading.value = true;
        const [packageResponse, serviceResponse] = await Promise.all([
            adminPackageService.list(),
            adminCaptchaServiceService.list({ per_page: 100 }),
        ]);

        packageList.value = packageResponse.packages.data;
        summary.value = packageResponse.summary;
        services.value = (serviceResponse.services?.data ?? []).map((item: Record<string, unknown>) => ({
            code: String(item.code ?? ""),
            name: String(item.name ?? ""),
        }));
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = (): void => {
    editingId.value = null;
    featureInput.value = "";
    form.value = emptyForm();
    modalOpen.value = true;
};

const openEditModal = async (item: AdminPackageItem): Promise<void> => {
    try {
        const detail = await adminPackageService.show(item.id);
        editingId.value = item.id;
        featureInput.value = Array.isArray(detail.features) ? detail.features.join("\n") : "";
        form.value = {
            name: detail.name,
            slug: detail.slug,
            description: detail.description ?? "",
            price: Number(detail.price),
            duration_days: detail.duration_days,
            features: Array.isArray(detail.features) ? detail.features : [],
            status: detail.status,
            package_limits: detail.package_limits,
        };
        modalOpen.value = true;
    } catch (error) {
        handleErrorResponse(error);
    }
};

const closeModal = (): void => {
    modalOpen.value = false;
};

const syncFeatures = (): void => {
    form.value.features = featureInput.value
        .split(/\r?\n/)
        .map((item) => item.trim())
        .filter(Boolean);
};

const savePackage = async (): Promise<void> => {
    try {
        saving.value = true;
        syncFeatures();

        const payload: AdminPackagePayload = {
            ...form.value,
            price: Number(form.value.price),
            duration_days: Number(form.value.duration_days),
            package_limits: {
                ...form.value.package_limits,
                max_api_keys: Number(form.value.package_limits.max_api_keys),
                requests_per_minute: Number(form.value.package_limits.requests_per_minute),
                monthly_captcha_quota:
                    form.value.package_limits.monthly_captcha_quota === null
                        ? null
                        : Number(form.value.package_limits.monthly_captcha_quota),
                max_concurrent_tasks: Number(form.value.package_limits.max_concurrent_tasks),
                max_whitelisted_ips: Number(form.value.package_limits.max_whitelisted_ips),
            },
        };

        if (editingId.value) {
            await adminPackageService.update(editingId.value, payload);
            handleSuccessResponse({ data: { status: true, message: "Đã cập nhật gói captcha." } });
        } else {
            await adminPackageService.create(payload);
            handleSuccessResponse({ data: { status: true, message: "Đã tạo gói captcha mới." } });
        }

        closeModal();
        await loadData();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};

const deletePackage = async (item: AdminPackageItem): Promise<void> => {
    const result = await Swal.fire({
        title: "Xóa gói captcha?",
        text: `Gói ${item.name} sẽ bị xóa khỏi danh sách bán.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Xóa gói",
        cancelButtonText: "Hủy",
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await adminPackageService.delete(item.id);
        handleSuccessResponse({ data: { status: true, message: "Đã xóa gói captcha." } });
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
        <Breadcrumb title="Gói captcha" description="Tạo gói lượt giải captcha, cấu hình quota và danh sách service được phép dùng trong gói.">
            <template #actions>
                <button type="button" class="inline-flex items-center gap-2 rounded-[12px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white" @click="openCreateModal">
                    <Plus class="h-4 w-4" />
                    Tạo gói mới
                </button>
            </template>
        </Breadcrumb>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-[16px] border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Tổng gói</p>
                <p class="mt-3 text-3xl font-black text-slate-950">{{ summary.total }}</p>
            </article>
            <article class="rounded-[16px] border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Đang bán</p>
                <p class="mt-3 text-3xl font-black text-emerald-700">{{ summary.active }}</p>
            </article>
            <article class="rounded-[16px] border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold text-slate-500">Tạm dừng</p>
                <p class="mt-3 text-3xl font-black text-amber-700">{{ summary.inactive }}</p>
            </article>
        </section>

        <section class="rounded-[18px] border border-slate-200 bg-white p-5">
            <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px]">
                <input v-model="filters.search" type="text" class="rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" placeholder="Tìm theo tên hoặc slug gói..." />
                <select v-model="filters.status" class="rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang bán</option>
                    <option value="inactive">Tạm dừng</option>
                </select>
            </div>

            <div v-if="loading" class="mt-4 rounded-[16px] bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">Đang tải gói captcha...</div>

            <div v-else class="mt-4 grid gap-4 lg:grid-cols-2">
                <article v-for="item in filteredPackages" :key="item.id" class="rounded-[18px] border border-slate-200 bg-slate-50/80 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-teal-700 ring-1 ring-teal-100">{{ item.slug }}</span>
                            <h3 class="mt-3 text-xl font-bold text-slate-950">{{ item.name }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ item.description || "Chưa có mô tả cho gói này." }}</p>
                        </div>

                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'">
                            {{ item.status === 'active' ? 'Đang bán' : 'Tạm dừng' }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-[14px] bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Giá</p>
                            <p class="mt-1 text-lg font-bold text-slate-950">{{ Number(item.price).toLocaleString('vi-VN') }} đ</p>
                        </div>
                        <div class="rounded-[14px] bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Thời hạn</p>
                            <p class="mt-1 text-lg font-bold text-slate-950">{{ item.duration_days }} ngày</p>
                        </div>
                        <div class="rounded-[14px] bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-slate-400">Quota</p>
                            <p class="mt-1 text-lg font-bold text-slate-950">
                                {{ item.package_limits.monthly_captcha_quota === null ? 'Không giới hạn' : `${item.package_limits.monthly_captcha_quota} lượt` }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-[14px] bg-white px-4 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Service áp dụng</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span v-for="serviceCode in item.package_limits.service_whitelist" :key="serviceCode" class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">
                                {{ serviceCode }}
                            </span>
                            <span v-if="item.package_limits.service_whitelist.length === 0" class="text-sm text-slate-400">Chưa chọn service nào.</span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button type="button" class="rounded-[12px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white" @click="openEditModal(item)">Chỉnh sửa</button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-[12px] border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600" @click="deletePackage(item)">
                            <Trash2 class="h-4 w-4" />
                            Xóa
                        </button>
                    </div>
                </article>

                <div v-if="filteredPackages.length === 0" class="rounded-[18px] border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500 lg:col-span-2">
                    Chưa có gói captcha nào phù hợp bộ lọc hiện tại.
                </div>
            </div>
        </section>

        <teleport to="body">
            <div v-if="modalOpen" class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/45 p-4">
                <div class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-[22px] bg-white p-6 shadow-2xl">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">{{ editingId ? "Cập nhật gói captcha" : "Tạo gói captcha" }}</h2>
                            <p class="mt-1 text-sm text-slate-500">Thiết lập quota, danh sách service và giá bán cho gói lượt giải captcha.</p>
                        </div>
                        <button type="button" class="rounded-[12px] border border-slate-200 p-2 text-slate-500" @click="closeModal">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Tên gói</span>
                                    <input v-model="form.name" type="text" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Slug</span>
                                    <input v-model="form.slug" type="text" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Giá gói</span>
                                    <input v-model="form.price" type="number" min="0" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Số ngày sử dụng</span>
                                    <input v-model="form.duration_days" type="number" min="1" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1 md:col-span-2">
                                    <span class="text-sm font-semibold text-slate-700">Mô tả gói</span>
                                    <textarea v-model="form.description" rows="4" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                            </div>

                            <label class="space-y-1">
                                <span class="text-sm font-semibold text-slate-700">Danh sách quyền lợi hiển thị</span>
                                <textarea v-model="featureInput" rows="5" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" placeholder="Mỗi dòng là một quyền lợi..." />
                            </label>
                        </div>

                        <div class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">API key tối đa</span>
                                    <input v-model="form.package_limits.max_api_keys" type="number" min="1" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Request / phút</span>
                                    <input v-model="form.package_limits.requests_per_minute" type="number" min="1" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Task đồng thời tối đa</span>
                                    <input v-model="form.package_limits.max_concurrent_tasks" type="number" min="1" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">IP whitelist tối đa</span>
                                    <input v-model="form.package_limits.max_whitelisted_ips" type="number" min="1" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" />
                                </label>
                                <label class="space-y-1 md:col-span-2">
                                    <span class="text-sm font-semibold text-slate-700">Số lượt giải trong gói</span>
                                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_170px]">
                                        <input
                                            :value="form.package_limits.monthly_captcha_quota ?? ''"
                                            type="number"
                                            min="0"
                                            :disabled="form.package_limits.monthly_captcha_quota === null"
                                            class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400 disabled:bg-slate-50"
                                            @input="form.package_limits.monthly_captcha_quota = Number(($event.target as HTMLInputElement).value)"
                                        />
                                        <label class="flex items-center justify-between rounded-[12px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                            <span>Không giới hạn</span>
                                            <input
                                                :checked="form.package_limits.monthly_captcha_quota === null"
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-slate-300"
                                                @change="form.package_limits.monthly_captcha_quota = ($event.target as HTMLInputElement).checked ? null : 1000"
                                            />
                                        </label>
                                    </div>
                                </label>
                            </div>

                            <label class="space-y-1">
                                <span class="text-sm font-semibold text-slate-700">Service code áp dụng trong gói</span>
                                <textarea v-model="serviceWhitelistInput" rows="6" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400" placeholder="Mỗi dòng là một service code..." />
                                <div class="flex flex-wrap gap-2 pt-2">
                                    <button
                                        v-for="service in services"
                                        :key="service.code"
                                        type="button"
                                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition"
                                        :class="form.package_limits.service_whitelist.includes(service.code) ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600'"
                                        @click="
                                            form.package_limits.service_whitelist.includes(service.code)
                                                ? (form.package_limits.service_whitelist = form.package_limits.service_whitelist.filter((item) => item !== service.code))
                                                : form.package_limits.service_whitelist.push(service.code)
                                        "
                                    >
                                        {{ service.code }}
                                    </button>
                                </div>
                            </label>

                            <div class="grid gap-3 md:grid-cols-2">
                                <label class="flex items-center justify-between rounded-[12px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <span>Cho phép callback</span>
                                    <input v-model="form.package_limits.supports_callback" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                </label>
                                <label class="flex items-center justify-between rounded-[12px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <span>Ưu tiên queue</span>
                                    <input v-model="form.package_limits.supports_priority_queue" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                </label>
                                <label class="flex items-center justify-between rounded-[12px] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <span>Manual review</span>
                                    <input v-model="form.package_limits.supports_manual_review" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                                </label>
                                <label class="space-y-1">
                                    <span class="text-sm font-semibold text-slate-700">Trạng thái</span>
                                    <select v-model="form.status" class="w-full rounded-[12px] border border-slate-200 px-4 py-3 text-sm outline-none focus:border-teal-400">
                                        <option value="active">Đang bán</option>
                                        <option value="inactive">Tạm dừng</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" class="rounded-[12px] border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700" @click="closeModal">Đóng</button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-[12px] bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white" :disabled="saving" @click="savePackage">
                            <Save class="h-4 w-4" />
                            {{ saving ? "Đang lưu..." : "Lưu gói" }}
                        </button>
                    </div>
                </div>
            </div>
        </teleport>
    </div>
</template>
