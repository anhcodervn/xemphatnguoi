<template>
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Nhận diện thương hiệu</h2>
                        <p class="text-sm text-slate-500">
                            Thiết lập logo, favicon và hình ảnh đại diện cho website.
                        </p>
                    </div>

                    <button
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                        :disabled="isSaving"
                        @click="submitForm"
                    >
                        {{ isSaving ? "Đang lưu..." : "Lưu thay đổi" }}
                    </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-dashed border-slate-300 p-4">
                        <p class="text-sm font-medium text-slate-700">Logo header</p>

                        <div class="mt-3 flex h-32 items-center justify-center overflow-hidden rounded-xl bg-slate-50 text-sm text-slate-400">
                            <img
                                v-if="formData.logo"
                                :src="formData.logo"
                                alt="logo"
                                class="h-full w-full object-contain"
                            />
                            <span v-else>320 × 96</span>
                        </div>

                        <div class="mt-3">
                            <UploadImage
                                :accept="['image/png','image/jpeg','image/webp','image/svg+xml']"
                                :compress="true"
                                @uploaded="(url: string) => (formData.logo = url)"
                            />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-slate-300 p-4">
                        <p class="text-sm font-medium text-slate-700">Favicon</p>

                        <div class="mt-3 flex h-32 items-center justify-center overflow-hidden rounded-xl bg-slate-50 text-sm text-slate-400">
                            <img
                                v-if="formData.favicon"
                                :src="formData.favicon"
                                alt="favicon"
                                class="h-16 w-16 object-contain"
                            />
                            <span v-else>64 × 64</span>
                        </div>

                        <div class="mt-3">
                            <UploadImage
                                :accept="['image/png','image/jpeg','image/webp','image/svg+xml','image/x-icon']"
                                :compress="true"
                                @uploaded="(url: string) => (formData.favicon = url)"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-900">Màu sắc giao diện</h2>
                    <p class="text-sm text-slate-500">
                        Người dùng có thể chọn màu trực tiếp hoặc nhập mã màu HEX thủ công.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div
                        v-for="item in colorFields"
                        :key="item.key"
                        class="space-y-2 rounded-2xl border border-slate-200 p-4"
                    >
                        <label class="text-sm font-medium text-slate-700">{{ item.label }}</label>

                        <div class="flex items-center gap-3">
                            <input
                                :value="formData[item.key]"
                                type="color"
                                class="h-11 w-14 cursor-pointer rounded-lg border border-slate-300 bg-white p-1"
                                @input="updateColor(item.key, ($event.target as HTMLInputElement).value)"
                            />

                            <div
                                class="h-11 flex-1 rounded-xl border border-slate-200"
                                :style="{ backgroundColor: formData[item.key] }"
                            ></div>
                        </div>

                        <input
                            :value="formData[item.key]"
                            type="text"
                            maxlength="7"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm uppercase outline-none transition focus:border-slate-900"
                            placeholder="#FFFFFF"
                            @input="updateHexInput(item.key, ($event.target as HTMLInputElement).value)"
                            @blur="normalizeColor(item.key)"
                        />

                        <p class="text-xs text-slate-500">
                            Hỗ trợ mã HEX dạng <code>#RRGGBB</code>.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Xem trước nhanh</h3>

            <div
                class="mt-4 rounded-[28px] border border-slate-200 p-4"
                :style="{ backgroundColor: safeColor(formData.color_surface, '#F8FAFC') }"
            >
                <div class="rounded-2xl bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex h-8 w-24 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                            <img
                                v-if="formData.logo"
                                :src="formData.logo"
                                alt="logo-preview"
                                class="h-full w-full object-contain"
                            />
                        </div>

                        <div
                            class="h-8 w-20 rounded-full"
                            :style="{ backgroundColor: safeColor(formData.color_accent, '#0EA5E9') }"
                        ></div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div class="h-4 w-1/2 rounded bg-slate-200"></div>

                        <div
                            class="h-10 rounded-xl"
                            :style="{ backgroundColor: safeColor(formData.color_primary, '#0F172A') }"
                        ></div>

                        <div
                            class="h-10 rounded-xl border border-slate-200"
                            :style="{ backgroundColor: safeColor(formData.color_surface, '#F8FAFC') }"
                        ></div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import UploadImage from "@/components/shared/UpladImage/index.vue";
import { adminSettingService } from "@/services/admin-setting.service";
import { BrandingSettingType } from "@/types/setting.type";
import { handleErrorResponse, handleSuccessResponse } from "@/utils/response";

type ColorFieldKey = "color_primary" | "color_accent" | "color_surface";

const colorFields: Array<{ key: ColorFieldKey; label: string }> = [
    { key: "color_primary", label: "Màu chính" },
    { key: "color_accent", label: "Màu nhấn" },
    { key: "color_surface", label: "Màu nền" },
];

const isSaving = ref(false);
const formData = ref<BrandingSettingType>({
    logo: "",
    favicon: "",
    color_primary: "#0F172A",
    color_accent: "#0EA5E9",
    color_surface: "#F8FAFC",
});

const isValidHexColor = (value: string) => /^#(?:[0-9A-F]{6})$/i.test(value);

const safeColor = (value: string, fallback: string) => {
    return isValidHexColor(value) ? value : fallback;
};

const normalizeHexValue = (value: string) => {
    let normalized = value.trim().toUpperCase();

    if (normalized && !normalized.startsWith("#")) {
        normalized = `#${normalized}`;
    }

    return normalized.slice(0, 7);
};

const updateColor = (field: ColorFieldKey, value: string) => {
    formData.value = {
        ...formData.value,
        [field]: value.toUpperCase(),
    };
};

const updateHexInput = (field: ColorFieldKey, value: string) => {
    formData.value = {
        ...formData.value,
        [field]: normalizeHexValue(value),
    };
};

const normalizeColor = (field: ColorFieldKey) => {
    const defaults: Record<ColorFieldKey, string> = {
        color_primary: "#0F172A",
        color_accent: "#0EA5E9",
        color_surface: "#F8FAFC",
    };

    const value = normalizeHexValue(formData.value[field]);

    formData.value = {
        ...formData.value,
        [field]: isValidHexColor(value) ? value : defaults[field],
    };
};

const loadData = async () => {
    try {
        const data = await adminSettingService.getBranding();
        formData.value = {
            ...data.settings,
            color_primary: safeColor(data.settings.color_primary, "#0F172A"),
            color_accent: safeColor(data.settings.color_accent, "#0EA5E9"),
            color_surface: safeColor(data.settings.color_surface, "#F8FAFC"),
        };
    } catch (err) {
        handleErrorResponse(err);
    }
};

const submitForm = async () => {
    try {
        isSaving.value = true;

        normalizeColor("color_primary");
        normalizeColor("color_accent");
        normalizeColor("color_surface");

        const res = await adminSettingService.updateBranding(formData.value);
        formData.value = {
            ...res.settings,
            color_primary: safeColor(res.settings.color_primary, "#0F172A"),
            color_accent: safeColor(res.settings.color_accent, "#0EA5E9"),
            color_surface: safeColor(res.settings.color_surface, "#F8FAFC"),
        };
        handleSuccessResponse({ data: { status: true, message: "Cập nhật cài đặt nhận diện thành công" } });
    } catch (err) {
        handleErrorResponse(err);
    } finally {
        isSaving.value = false;
    }
};

onMounted(loadData);
</script>
