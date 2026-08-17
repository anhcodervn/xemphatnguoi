<script setup lang="ts">
import { adminTrafficFineService, type AdminAdSlot } from '@/services/admin-traffic-fine.service';
import type { AxiosError } from 'axios';
import { Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';

const slots = ref<AdminAdSlot[]>([]);
const editingId = ref<number | undefined>();
const saving = ref(false);
const errorMessage = ref('');
const form = reactive<Omit<AdminAdSlot, 'id'>>({ name: '', code: '', enabled: false, device: 'all', start_at: null, end_at: null });
const load = async (): Promise<void> => {
    slots.value = await adminTrafficFineService.adSlots();
};
const reset = (): void => {
    editingId.value = undefined;
    form.name = '';
    form.code = '';
    form.enabled = false;
    form.device = 'all';
    form.start_at = null;
    form.end_at = null;
    errorMessage.value = '';
};
const edit = (slot: AdminAdSlot): void => {
    editingId.value = slot.id;
    Object.assign(form, {
        name: slot.name,
        code: slot.code,
        enabled: slot.enabled,
        device: slot.device,
        start_at: slot.start_at?.slice(0, 16) ?? null,
        end_at: slot.end_at?.slice(0, 16) ?? null,
    });
};
const save = async (): Promise<void> => {
    saving.value = true;
    errorMessage.value = '';
    try {
        await adminTrafficFineService.saveAdSlot(form, editingId.value);
        reset();
        await load();
    } catch (error) {
        errorMessage.value = (error as AxiosError<{ message?: string }>).response?.data?.message ?? 'Không thể lưu vị trí quảng cáo.';
    } finally {
        saving.value = false;
    }
};
const remove = async (slot: AdminAdSlot): Promise<void> => {
    if (!window.confirm(`Xóa vị trí ${slot.name}?`)) return;
    await adminTrafficFineService.deleteAdSlot(slot.id);
    await load();
};
onMounted(load);
</script>
<template>
    <div class="grid gap-7 xl:grid-cols-[380px_1fr]">
        <section>
            <header>
                <p class="text-sm font-bold text-sky-700">Monetization</p>
                <h1 class="mt-1 text-3xl font-black text-slate-950">Ad Slots</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Mã quảng cáo được quản lý tập trung, không rải trong Blade.</p>
            </header>
            <form class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
                <div class="flex justify-between">
                    <h2 class="font-bold">{{ editingId ? 'Sửa vị trí' : 'Tạo vị trí' }}</h2>
                    <button v-if="editingId" type="button" aria-label="Hủy" class="p-2" @click="reset"><X class="h-4 w-4" /></button>
                </div>
                <div>
                    <label for="slot-name" class="mb-2 block text-sm font-bold">Tên</label
                    ><input
                        id="slot-name"
                        v-model="form.name"
                        list="ad-slot-names"
                        required
                        pattern="[a-z0-9_]+"
                        placeholder="home_after_lookup"
                        class="app-focus h-11 w-full rounded-lg border border-slate-300 px-3"
                    /><datalist id="ad-slot-names">
                        <option value="home_after_lookup" />
                        <option value="home_content" />
                        <option value="article_top" />
                        <option value="article_middle" />
                        <option value="article_bottom" />
                        <option value="lookup_result_bottom" />
                    </datalist>
                </div>
                <div>
                    <label for="slot-device" class="mb-2 block text-sm font-bold">Thiết bị</label
                    ><select id="slot-device" v-model="form.device" class="app-focus h-11 w-full rounded-lg border border-slate-300 bg-white px-3">
                        <option value="all">Tất cả</option>
                        <option value="desktop">Desktop</option>
                        <option value="mobile">Mobile</option>
                    </select>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="slot-start" class="mb-2 block text-sm font-bold">Bắt đầu</label
                        ><input
                            id="slot-start"
                            v-model="form.start_at"
                            type="datetime-local"
                            class="app-focus h-11 w-full rounded-lg border border-slate-300 px-3 text-sm"
                        />
                    </div>
                    <div>
                        <label for="slot-end" class="mb-2 block text-sm font-bold">Kết thúc</label
                        ><input
                            id="slot-end"
                            v-model="form.end_at"
                            type="datetime-local"
                            class="app-focus h-11 w-full rounded-lg border border-slate-300 px-3 text-sm"
                        />
                    </div>
                </div>
                <div>
                    <label for="slot-code" class="mb-2 block text-sm font-bold">Mã quảng cáo</label
                    ><textarea
                        id="slot-code"
                        v-model="form.code"
                        rows="8"
                        class="app-focus w-full rounded-lg border border-slate-300 p-3 font-mono text-xs"
                    ></textarea>
                </div>
                <label class="flex min-h-11 items-center gap-3 text-sm font-semibold"
                    ><input v-model="form.enabled" type="checkbox" class="h-5 w-5 rounded border-slate-300 text-sky-700" />Kích hoạt</label
                ><button
                    :disabled="saving"
                    class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-slate-950 px-4 text-sm font-bold text-white"
                >
                    <Plus class="h-4 w-4" />{{ saving ? 'Đang lưu' : 'Lưu vị trí' }}
                </button>
                <p v-if="errorMessage" class="text-sm text-red-700">{{ errorMessage }}</p>
            </form>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-bold text-slate-950">Các vị trí đã cấu hình</h2>
            <div class="mt-5 grid gap-3">
                <article
                    v-for="slot in slots"
                    :key="slot.id"
                    class="flex flex-col gap-4 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3 class="font-mono text-sm font-bold">{{ slot.name }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ slot.device }} · {{ slot.enabled ? 'Đang bật' : 'Đang tắt' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            aria-label="Sửa"
                            class="app-focus flex min-h-10 min-w-10 items-center justify-center rounded-lg border border-slate-300"
                            @click="edit(slot)"
                        >
                            <Pencil class="h-4 w-4" /></button
                        ><button
                            type="button"
                            aria-label="Xóa"
                            class="app-focus flex min-h-10 min-w-10 items-center justify-center rounded-lg border border-red-200 text-red-600"
                            @click="remove(slot)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </article>
                <p v-if="!slots.length" class="rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">Chưa có vị trí quảng cáo.</p>
            </div>
        </section>
    </div>
</template>
