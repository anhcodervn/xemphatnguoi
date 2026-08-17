<template>
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">SEO mặc định</h2>
                        <p class="text-sm text-slate-500">Áp dụng cho trang chủ và các trang chưa có metadata riêng.</p>
                    </div>

                    <button
                        class="rounded-xl bg-slate-900 px-4 py-2 text-sm text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                        :disabled="isSaving"
                        @click="submitForm"
                    >
                        {{ isSaving ? 'Đang lưu...' : 'Lưu thay đổi' }}
                    </button>
                </div>

                <div class="grid gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Meta title</label>
                        <input
                            v-model="formData.meta_title"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                            placeholder="Tiêu đề SEO (50-60 ký tự)"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Meta description</label>
                        <textarea
                            v-model="formData.meta_description"
                            rows="4"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                            placeholder="Mô tả SEO (120-160 ký tự)"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Meta robots</label>
                        <input
                            v-model="formData.robots"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                            placeholder="index, follow"
                        />
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-800">Tệp robots.txt & ads.txt</h3>
                            <div class="flex items-center gap-3 text-xs font-semibold text-slate-700">
                                <a href="/robots.txt" target="_blank" rel="noopener noreferrer" class="hover:text-slate-950">Xem robots.txt</a>
                                <a href="/ads.txt" target="_blank" rel="noopener noreferrer" class="hover:text-slate-950">Xem ads.txt</a>
                            </div>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Nội dung được xuất công khai ở thư mục gốc website. Để trống robots.txt để dùng cấu hình mặc định an toàn; dòng Sitemap do
                            hệ thống tự thêm.
                        </p>

                        <div class="mt-3 grid gap-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700">robots.txt</label>
                                <textarea
                                    v-model="formData.robots_txt"
                                    rows="9"
                                    maxlength="20000"
                                    spellcheck="false"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs leading-5 outline-none focus:border-slate-900"
                                    placeholder="User-agent: *&#10;Allow: /&#10;Sitemap: https://example.com/sitemap.xml"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-slate-700">ads.txt</label>
                                <textarea
                                    v-model="formData.ads_txt"
                                    rows="6"
                                    maxlength="20000"
                                    spellcheck="false"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs leading-5 outline-none focus:border-slate-900"
                                    placeholder="google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0"
                                />
                                <p class="text-xs leading-5 text-slate-500">
                                    Mỗi nhà bán quảng cáo một dòng theo định dạng do mạng quảng cáo cung cấp.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-slate-900">Tracking & Pixel</h2>
                    <p class="text-sm text-slate-500">Tách riêng mã đo lường để tránh ảnh hưởng logic các phần khác.</p>
                </div>

                <div class="grid gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Google Tag Manager ID</label>
                        <input
                            v-model="formData.gtm_id"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                            placeholder="GTM-XXXXXXX"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Meta Pixel ID</label>
                        <input
                            v-model="formData.meta_pixel_id"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-900"
                            placeholder="1234567890"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Custom header / meta tags</label>
                        <textarea
                            v-model="formData.custom_header"
                            rows="5"
                            maxlength="10000"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs outline-none focus:border-slate-900"
                            placeholder='<meta name="google-site-verification" content="...">'
                        />
                        <p class="text-xs leading-5 text-slate-500">
                            Tối đa 20 thẻ meta với thuộc tính name hoặc property và content. Không chấp nhận script, link hay http-equiv.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-700">Script tùy biến</label>
                        <textarea
                            v-model="formData.custom_script"
                            rows="5"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs outline-none focus:border-slate-900"
                            placeholder="<script>...</script>"
                        />
                    </div>
                </div>
            </div>
        </section>

        <aside class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Checklist xuất bản</h3>

            <div class="mt-4 space-y-3">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                    Meta title và description đã được cấu hình riêng theo domain.
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Kiểm tra lại script tracking trước khi deploy production.
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                    Giá trị tại đây chỉ là mặc định, mỗi page có thể cấu hình SEO riêng.
                </div>
            </div>
        </aside>
    </div>
</template>

<script setup lang="ts">
import { adminSettingService } from '@/services/admin-setting.service';
import type { SeoSettingType } from '@/types/setting.type';
import { handleErrorResponse, handleSuccessResponse } from '@/utils/response';
import { onMounted, ref } from 'vue';

const isSaving = ref(false);
const formData = ref<SeoSettingType>({
    meta_title: '',
    meta_description: '',
    robots: 'index,follow',
    gtm_id: '',
    meta_pixel_id: '',
    custom_header: '',
    custom_script: '',
    robots_txt: '',
    ads_txt: '',
});

const loadData = async (): Promise<void> => {
    try {
        const data = await adminSettingService.getSeo();
        formData.value = { ...data.settings };
    } catch (err) {
        handleErrorResponse(err);
    }
};

const submitForm = async (): Promise<void> => {
    try {
        isSaving.value = true;
        const res = await adminSettingService.updateSeo(formData.value);
        formData.value = { ...res.settings };
        handleSuccessResponse({ data: { status: true, message: 'Cập nhật cài đặt SEO và tracking thành công' } });
    } catch (err) {
        handleErrorResponse(err);
    } finally {
        isSaving.value = false;
    }
};

onMounted(loadData);
</script>
