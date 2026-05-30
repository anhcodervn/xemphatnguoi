<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="rounded border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
            <div class="mb-6 flex flex-col gap-3 border-b border-gray-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ isEditing ? "Cập nhật gói thuê" : "Tạo gói thuê" }}</h2>
                    <p class="text-sm text-slate-500">Nhập đúng cấu hình gói để đồng bộ với hệ thống subscription hiện tại.</p>
                </div>

                <RouterLink
                    to="/admin/packages"
                    class="inline-flex items-center justify-center rounded border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    Quay lại danh sách
                </RouterLink>
            </div>

            <form class="space-y-6" @submit.prevent="submitForm">
                <div class="grid gap-4 md:grid-cols-2">
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

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-account-limit">Giới hạn account</label>
                        <input id="package-account-limit" v-model.number="form.account_limit" type="number" min="0" step="1" :class="inputClass" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-concurrent-limit">Giới hạn đồng thời</label>
                        <input id="package-concurrent-limit" v-model.number="form.concurrent_limit" type="number" min="1" step="1" :class="inputClass" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-request-limit">Tổng request</label>
                        <input id="package-request-limit" v-model.number="form.request_limit" type="number" min="0" step="1" :class="inputClass" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-request-per-minute">Request / phút</label>
                        <input
                            id="package-request-per-minute"
                            v-model.number="form.request_per_minute"
                            type="number"
                            min="1"
                            step="1"
                            :class="inputClass"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label
                            class="flex items-start gap-3 rounded border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-slate-700"
                            for="package-buy-extra-account"
                        >
                            <input
                                id="package-buy-extra-account"
                                v-model="form.can_buy_extra_account"
                                type="checkbox"
                                class="mt-1 h-4 w-4 rounded border-gray-300"
                            />
                            <span>Cho phép mua thêm account</span>
                        </label>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700" for="package-extra-account-price">Giá account thêm</label>
                        <input
                            id="package-extra-account-price"
                            v-model.number="form.extra_account_price"
                            type="number"
                            min="0"
                            step="0.01"
                            :disabled="!form.can_buy_extra_account"
                            :class="disabledInputClass"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-slate-700" for="package-features">Tính năng</label>
                        <textarea
                            id="package-features"
                            v-model="featuresInput"
                            rows="6"
                            :class="textareaClass"
                            placeholder="Mỗi dòng là một tính năng"
                        />
                    </div>
                </div>

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
                        {{ processing ? "Đang lưu..." : isEditing ? "Cập nhật gói" : "Tạo gói" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminPackageService, type PackagePayload } from "@/services/admin-package.service";
import { handleErrorResponse } from "@/utils/response";
import Swal from "sweetalert2";
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

const inputClass =
    "block h-11 w-full rounded border border-gray-300 px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500";
const disabledInputClass = `${inputClass} disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400`;
const textareaClass =
    "block w-full rounded border border-gray-300 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500";

const processing = ref(false);
const isSlugEdited = ref(false);

const form = ref<PackagePayload>({
    name: "",
    slug: "",
    description: "",
    price: 0,
    duration_days: 30,
    account_limit: 0,
    can_buy_extra_account: false,
    extra_account_price: 0,
    request_limit: 0,
    request_per_minute: 60,
    concurrent_limit: 1,
    features: [],
    status: "active",
});

const packageId = computed(() => route.params.package_id as string | undefined);
const isEditing = computed(() => Boolean(packageId.value));

const featuresInput = computed({
    get: () => form.value.features.join("\n"),
    set: (value: string) => {
        form.value.features = value
            .split("\n")
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

async function loadPackage(): Promise<void> {
    if (!packageId.value) {
        return;
    }

    try {
        const data = await adminPackageService.get(packageId.value);

        form.value = {
            name: data.name,
            slug: data.slug,
            description: data.description ?? "",
            price: Number(data.price),
            duration_days: data.duration_days,
            account_limit: data.account_limit,
            can_buy_extra_account: data.can_buy_extra_account,
            extra_account_price: Number(data.extra_account_price),
            request_limit: data.request_limit,
            request_per_minute: data.request_per_minute,
            concurrent_limit: data.concurrent_limit,
            features: Array.isArray(data.features) ? data.features : [],
            status: data.status,
        };

        isSlugEdited.value = true;
    } catch (error) {
        handleErrorResponse(error);
        await router.push("/admin/packages");
    }
}

async function submitForm(): Promise<void> {
    try {
        processing.value = true;

        const response = isEditing.value
            ? await adminPackageService.update(packageId.value!, form.value)
            : await adminPackageService.create(form.value);

        await Swal.fire("Thành công", response.data.message, "success");
        await router.push("/admin/packages");
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        processing.value = false;
    }
}

function slugify(value: string): string {
    return value
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
}

onMounted(async () => {
    await loadPackage();
});
</script>
