<script setup lang="ts">
import Editor from '@/components/shared/Editor/index.vue';
import Modal from '@/components/shared/Modal/index.vue';
import { adminProxyCategoryService } from '@/services/admin-proxy-taxonomy.service';
import { FolderTree, LoaderCircle, Pencil, Plus, Power, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref } from 'vue';

type Category = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    icon: string | null;
    sort_order: number;
    is_active: boolean;
    products_count?: number;
};

const categories = ref<Category[]>([]);
const loading = ref(true);
const saving = ref(false);
const editorOpen = ref(false);
const editingId = ref<number | null>(null);
const form = reactive({ code: '', name: '', description: '', icon: '', sort_order: 0, is_active: true });

const isEditing = computed(() => editingId.value !== null);

const load = async () => {
    loading.value = true;
    try {
        const categoryData = await adminProxyCategoryService.list({ per_page: 100 });
        categories.value = categoryData.categories.data;
    } finally {
        loading.value = false;
    }
};

const resetForm = () => Object.assign(form, { code: '', name: '', description: '', icon: '', sort_order: 0, is_active: true });

const openCreate = () => {
    editingId.value = null;
    resetForm();
    editorOpen.value = true;
};

const openEdit = (item: Category) => {
    editingId.value = item.id;
    Object.assign(form, {
        code: item.code,
        name: item.name,
        description: item.description || '',
        icon: item.icon || '',
        sort_order: item.sort_order,
        is_active: item.is_active,
    });
    editorOpen.value = true;
};

const save = async () => {
    saving.value = true;
    const payload: Record<string, unknown> = {
        code: form.code.trim(),
        name: form.name.trim(),
        description: form.description.trim() || null,
        icon: form.icon.trim() || null,
        sort_order: form.sort_order,
        is_active: form.is_active,
    };
    try {
        if (editingId.value) await adminProxyCategoryService.update(editingId.value, payload);
        else await adminProxyCategoryService.create(payload);
        editorOpen.value = false;
        await load();
        await Swal.fire('Đã lưu', 'Chuyên mục đã được cập nhật.', 'success');
    } catch (error: any) {
        await Swal.fire('Không thể lưu', error?.response?.data?.message || 'Kiểm tra lại dữ liệu.', 'error');
    } finally {
        saving.value = false;
    }
};

const toggle = async (item: Category) => {
    await adminProxyCategoryService.update(item.id, { is_active: !item.is_active });
    await load();
};

const remove = async (item: Category) => {
    const confirmed = await Swal.fire({
        title: `Xóa ${item.name}?`,
        text: 'Chỉ xóa được khi chuyên mục không còn sản phẩm proxy.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xóa chuyên mục',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc2626',
    });
    if (!confirmed.isConfirmed) return;

    try {
        await adminProxyCategoryService.delete(item.id);
        await load();
    } catch (error: any) {
        await Swal.fire('Không thể xóa', error?.response?.data?.message || 'Mục này vẫn còn dữ liệu con.', 'error');
    }
};

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <header>
            <p class="text-sm font-semibold text-blue-600">Cấu trúc kho proxy</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">Chuyên mục proxy</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Tạo các nhóm lớn như Proxy v4 và Proxy v6. Mỗi sản phẩm proxy sẽ được gắn trực tiếp vào một chuyên mục.
            </p>
        </header>

        <div v-if="loading" class="proxy-panel py-20 text-center text-slate-500">
            <LoaderCircle class="mx-auto mb-3 h-6 w-6 animate-spin text-blue-600" />Đang tải cấu trúc...
        </div>

        <div v-else>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-blue-50 p-3 text-blue-700"><FolderTree class="h-5 w-5" /></div>
                        <div>
                            <h2 class="font-bold text-slate-950">Category proxy</h2>
                            <p class="text-xs text-slate-500">Chuyên mục lớn · {{ categories.length }} mục</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="proxy-focus inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                        @click="openCreate"
                    >
                        <Plus class="h-4 w-4" />Thêm
                    </button>
                </div>
                <div v-if="categories.length === 0" class="py-14 text-center text-sm text-slate-500">Chưa có chuyên mục proxy.</div>
                <div v-else class="divide-y divide-slate-100">
                    <article
                        v-for="category in categories"
                        :key="category.id"
                        class="flex items-center gap-3 px-5 py-4 transition hover:bg-blue-50/30"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-900">{{ category.name }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                    >{{ category.is_active ? 'Hiển thị' : 'Đang ẩn' }}</span
                                >
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-400">
                                {{ category.code }} · {{ category.products_count || 0 }} sản phẩm · thứ tự {{ category.sort_order }}
                            </p>
                        </div>
                        <div class="flex gap-1">
                            <button
                                type="button"
                                class="proxy-focus rounded-lg p-2 text-blue-600 hover:bg-blue-50"
                                title="Chỉnh sửa"
                                @click="openEdit(category)"
                            >
                                <Pencil class="h-4 w-4" /></button
                            ><button
                                type="button"
                                class="proxy-focus rounded-lg p-2 hover:bg-slate-100"
                                :class="category.is_active ? 'text-emerald-600' : 'text-slate-400'"
                                :title="category.is_active ? 'Ẩn chuyên mục' : 'Hiện chuyên mục'"
                                @click="toggle(category)"
                            >
                                <Power class="h-4 w-4" /></button
                            ><button
                                type="button"
                                class="proxy-focus rounded-lg p-2 text-rose-600 hover:bg-rose-50"
                                title="Xóa"
                                @click="remove(category)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        <Modal v-model="editorOpen" panel-class="max-w-2xl">
            <template #header
                ><div class="border-b border-slate-200 px-6 py-5 pr-14">
                    <h2 class="text-xl font-black text-slate-950">{{ isEditing ? 'Chỉnh sửa' : 'Thêm' }} chuyên mục</h2>
                    <p class="mt-1 text-sm text-slate-500">Mã dùng cho hệ thống; tên và mô tả dùng để hiển thị cho người quản trị hoặc khách hàng.</p>
                </div></template
            >
            <form class="space-y-5 p-6" @submit.prevent="save">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="space-y-2"
                        ><span class="text-sm font-semibold text-slate-700">Mã chuyên mục <b class="text-rose-500">*</b></span
                        ><input
                            v-model="form.code"
                            required
                            class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 font-mono shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="proxy-v4"
                        /><span class="block text-xs text-slate-400">Dùng chữ thường, không dấu và dấu gạch ngang.</span></label
                    ><label class="space-y-2"
                        ><span class="text-sm font-semibold text-slate-700">Tên hiển thị <b class="text-rose-500">*</b></span
                        ><input
                            v-model="form.name"
                            required
                            class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Tên hiển thị" /></label
                    ><label class="space-y-2"
                        ><span class="text-sm font-semibold text-slate-700">Icon hoặc URL ảnh</span
                        ><input
                            v-model="form.icon"
                            class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Tùy chọn" /></label
                    ><label class="space-y-2"
                        ><span class="text-sm font-semibold text-slate-700">Thứ tự hiển thị</span
                        ><input
                            v-model.number="form.sort_order"
                            type="number"
                            min="0"
                            class="proxy-focus min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-4 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    /></label>
                </div>
                <div class="block space-y-2">
                    <span class="text-sm font-semibold text-slate-700">Mô tả</span
                    ><Editor v-model:value="form.description" format="html" :height="280" :debounce="0" />
                </div>
                <label
                    class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border-2 border-slate-300 bg-slate-50 px-4 py-4 shadow-sm transition hover:border-blue-400 hover:bg-blue-50/50"
                    ><span
                        ><b class="block text-sm text-slate-800">Hiển thị mục này</b
                        ><small class="text-slate-500">Mục bị ẩn sẽ không xuất hiện trong catalog.</small></span
                    ><input
                        v-model="form.is_active"
                        type="checkbox"
                        class="h-6 w-6 cursor-pointer rounded-md border-2 border-slate-400 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                /></label>
                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="proxy-focus rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                        :disabled="saving"
                        @click="editorOpen = false"
                    >
                        Hủy</button
                    ><button
                        type="submit"
                        class="proxy-focus inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        :disabled="saving"
                    >
                        <LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" />{{ isEditing ? 'Lưu thay đổi' : 'Tạo chuyên mục' }}
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
