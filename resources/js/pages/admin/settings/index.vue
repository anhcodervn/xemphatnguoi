<script setup lang="ts">
import Breadcrumb from '@/components/MasterLayouts/Breadcrumb/index.vue';
import { adminSettingService } from '@/services/admin-setting.service';
import type { SystemSettingType } from '@/types/setting.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { computed, onMounted, ref } from 'vue';

const saving = ref(false);
const form = ref<SystemSettingType>({
    site_name: '',
    site_domain: '',
    site_description: '',
    site_active: true,
    allow_register: false,
    support_email: '',
    hotline: '',
    address: '',
    facebook: '',
    zalo: '',
    youtube: '',
    meta_title: '',
    meta_description: '',
    robots: 'index,follow',
    gtm_id: '',
    meta_pixel_id: '',
    custom_script: '',
    recharge_syntax: 'NAP',
    terms_of_use: [],
    privacy_policy: [],
    refund_policy: [],
});

const rechargePreview = computed(() => `${(form.value.recharge_syntax || 'NAP').trim() || 'NAP'}1801`);

const loadSettings = async (): Promise<void> => {
    try {
        const response = await adminSettingService.getSystem();
        form.value = { ...form.value, ...response.settings };
    } catch (error) {
        handleErrorResponse(error);
    }
};

const submitSettings = async (): Promise<void> => {
    try {
        saving.value = true;
        const payload: SystemSettingType = {
            ...form.value,
            recharge_syntax: (form.value.recharge_syntax || 'NAP').trim() || 'NAP',
        };
        const response = await adminSettingService.updateSystem(payload);
        form.value = { ...form.value, ...response.settings };
        handleSuccessResponse({ data: { status: true, message: 'Cập nhật cấu hình hệ thống thành công.' } });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <div class="space-y-4">
        <Breadcrumb title="Cấu hình hệ thống" description="Một màn hình duy nhất để quản lý thông tin vận hành, liên hệ, SEO và cú pháp nạp tiền." />

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
            <div class="space-y-4">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Thông tin vận hành</h2>
                            <p class="text-sm text-slate-500">Thông tin cơ bản cho toàn hệ thống.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-[10px] bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
                            :disabled="saving"
                            @click="submitSettings"
                        >
                            {{ saving ? 'Đang lưu...' : 'Lưu cấu hình' }}
                        </button>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1 md:col-span-2">
                            <span class="text-xs font-semibold text-slate-600">Tên hệ thống</span>
                            <input v-model="form.site_name" type="text" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Domain</span>
                            <input v-model="form.site_domain" type="text" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Email hỗ trợ</span>
                            <input v-model="form.support_email" type="email" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Hotline</span>
                            <input v-model="form.hotline" type="text" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Robots</span>
                            <input v-model="form.robots" type="text" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                        <label class="space-y-1 md:col-span-2">
                            <span class="text-xs font-semibold text-slate-600">Mô tả hệ thống</span>
                            <textarea v-model="form.site_description" rows="3" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                        <label class="space-y-1 md:col-span-2">
                            <span class="text-xs font-semibold text-slate-600">Địa chỉ</span>
                            <textarea v-model="form.address" rows="2" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        </label>
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-3 text-base font-semibold text-slate-900">Nạp tiền & chính sách</h2>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold text-slate-600">Cú pháp nạp</span>
                            <input v-model="form.recharge_syntax" type="text" maxlength="50" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                            <p class="text-xs text-slate-500">Preview: <span class="font-semibold text-indigo-600">{{ rechargePreview }}</span></p>
                        </label>
                        <div class="rounded-[10px] border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            <p class="font-semibold text-slate-800">Trạng thái hệ thống</p>
                            <label class="mt-2 flex items-center justify-between gap-2">
                                <span>Website hoạt động</span>
                                <input v-model="form.site_active" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                            </label>
                            <label class="mt-2 flex items-center justify-between gap-2">
                                <span>Cho phép đăng ký</span>
                                <input v-model="form.allow_register" type="checkbox" class="h-4 w-4 rounded border-slate-300" />
                            </label>
                        </div>
                    </div>
                </article>
            </div>

            <aside class="space-y-4">
                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Mạng xã hội</h3>
                    <div class="mt-3 space-y-2">
                        <input v-model="form.facebook" type="text" placeholder="Facebook URL" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        <input v-model="form.zalo" type="text" placeholder="Zalo URL" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        <input v-model="form.youtube" type="text" placeholder="YouTube URL" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                    </div>
                </article>

                <article class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">SEO / Tracking</h3>
                    <div class="mt-3 space-y-2">
                        <input v-model="form.meta_title" type="text" placeholder="Meta title" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        <textarea v-model="form.meta_description" rows="3" placeholder="Meta description" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        <input v-model="form.gtm_id" type="text" placeholder="GTM ID" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                        <input v-model="form.meta_pixel_id" type="text" placeholder="Meta pixel ID" class="w-full rounded-[10px] border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-400" />
                    </div>
                </article>
            </aside>
        </section>
    </div>
</template>
