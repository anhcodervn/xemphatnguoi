<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Liên hệ & CSKH</h2>
                    <p class="text-sm text-slate-500">
                        Thông tin dùng cho footer, trang liên hệ và các điểm chạm với khách hàng.
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

            <div class="grid gap-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Hotline</label>
                    <input
                        v-model="formData.hotline"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                        placeholder="Nhập số điện thoại hỗ trợ"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Email hỗ trợ</label>
                    <input
                        v-model="formData.support_email"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                        placeholder="support@email.com"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Địa chỉ</label>
                    <textarea
                        v-model="formData.address"
                        rows="4"
                        class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                        placeholder="Nhập địa chỉ công ty / cửa hàng"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900">Mạng xã hội</h2>
                <p class="text-sm text-slate-500">
                    Mỗi kênh được tách riêng để dễ bổ sung icon, liên kết và cấu hình hiển thị sau này.
                </p>
            </div>

            <div class="grid gap-3">
                <div class="rounded-xl border border-slate-200 p-3">
                    <label class="text-sm font-medium text-slate-700">Facebook</label>
                    <input
                        v-model="formData.facebook"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                        placeholder="https://facebook.com/..."
                    />
                </div>

                <div class="rounded-xl border border-slate-200 p-3">
                    <label class="text-sm font-medium text-slate-700">Zalo OA</label>
                    <input
                        v-model="formData.zalo"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                        placeholder="Link Zalo Official Account"
                    />
                </div>

                <div class="rounded-xl border border-slate-200 p-3">
                    <label class="text-sm font-medium text-slate-700">YouTube</label>
                    <input
                        v-model="formData.youtube"
                        class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                        placeholder="https://youtube.com/..."
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { adminSettingService } from "@/services/admin-setting.service";
import type { ContactSettingType } from "@/types/setting.type";
import { handleErrorResponse, handleSuccessResponse } from "@/utils/response";

const isSaving = ref(false);
const formData = ref<ContactSettingType>({
    hotline: "",
    support_email: "",
    address: "",
    facebook: "",
    zalo: "",
    youtube: "",
});

const loadData = async (): Promise<void> => {
    try {
        const data = await adminSettingService.getContact();
        formData.value = { ...data.settings };
    } catch (err) {
        handleErrorResponse(err);
    }
};

const submitForm = async (): Promise<void> => {
    try {
        isSaving.value = true;
        const res = await adminSettingService.updateContact(formData.value);
        formData.value = { ...res.settings };
        handleSuccessResponse({ data: { status: true, message: "Cập nhật cài đặt liên hệ thành công" } });
    } catch (err) {
        handleErrorResponse(err);
    } finally {
        isSaving.value = false;
    }
};

onMounted(loadData);
</script>
