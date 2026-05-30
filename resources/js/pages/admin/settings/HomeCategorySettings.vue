<template>
    <div class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:p-5">
            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Danh mục ưu tiên trang chủ</h2>
                    <p class="text-sm text-slate-500">
                        Chọn các danh mục muốn đưa lên đầu trang chủ của site hiện tại. Danh sách được lưu theo từng domain dưới dạng JSON trong
                        <code>domain_config</code>.
                    </p>
                </div>

                <button
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                    :disabled="saving"
                    @click="submit"
                >
                    {{ saving ? "Đang lưu..." : "Lưu danh mục trang chủ" }}
                </button>
            </div>

            <div v-if="loading" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                Đang tải danh mục...
            </div>

            <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">Tất cả danh mục của site</h3>
                        <p class="text-sm text-slate-500">
                            Đánh dấu những danh mục muốn hiển thị ở đầu trang chủ.
                        </p>
                    </div>

                    <div v-if="!categories.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        Site hiện tại chưa có danh mục nào để cấu hình.
                    </div>

                    <div v-else class="space-y-2">
                        <label
                            v-for="category in categories"
                            :key="category.id"
                            class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 transition hover:border-slate-300 hover:bg-white"
                        >
                            <input
                                :checked="isSelected(category.id)"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300"
                                @change="toggleCategory(category.id)"
                            />

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <i v-if="category.icon" :class="category.icon" class="text-base text-slate-700"></i>
                                    <span class="truncate text-sm font-medium text-slate-900">{{ category.name }}</span>
                                </div>
                                <p class="truncate text-xs text-slate-500">/{{ category.slug }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">Thứ tự hiển thị</h3>
                        <p class="text-sm text-slate-500">
                            Danh mục nào nằm trên cùng sẽ được ưu tiên hiển thị trước.
                        </p>
                    </div>

                    <div v-if="!selectedItems.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                        Chưa chọn danh mục nào cho trang chủ.
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="(category, index) in selectedItems"
                            :key="category.id"
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                        #{{ index + 1 }}
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <i v-if="category.icon" :class="category.icon" class="text-base text-slate-700"></i>
                                            <p class="truncate text-sm font-medium text-slate-900">{{ category.name }}</p>
                                        </div>
                                        <p class="truncate text-xs text-slate-500">/{{ category.slug }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 sm:justify-end">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-slate-400 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="index === 0"
                                        @click="moveCategory(index, 'up')"
                                    >
                                        Lên
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-slate-400 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50"
                                        :disabled="index === selectedIds.length - 1"
                                        @click="moveCategory(index, 'down')"
                                    >
                                        Xuống
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 hover:border-rose-300 hover:bg-rose-50"
                                        @click="removeCategory(category.id)"
                                    >
                                        Bỏ chọn
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import api from "@/config/axios";
import { adminSettingService } from "@/services/admin-setting.service";
import { ProductCategoryType } from "@/types/product-category.type";
import { handleErrorResponse, handleSuccessResponse } from "@/utils/response";

const loading = ref(false);
const saving = ref(false);
const categories = ref<ProductCategoryType[]>([]);
const selectedIds = ref<number[]>([]);

const selectedItems = computed(() => {
    const categoryMap = new Map(categories.value.map((item) => [item.id, item]));

    return selectedIds.value
        .map((id) => categoryMap.get(id))
        .filter((item): item is ProductCategoryType => Boolean(item));
});

const isSelected = (categoryId: number) => selectedIds.value.includes(categoryId);

const toggleCategory = (categoryId: number) => {
    if (isSelected(categoryId)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== categoryId);
        return;
    }

    selectedIds.value = [...selectedIds.value, categoryId];
};

const moveCategory = (index: number, direction: "up" | "down") => {
    const nextIndex = direction === "up" ? index - 1 : index + 1;

    if (nextIndex < 0 || nextIndex >= selectedIds.value.length) {
        return;
    }

    const nextIds = [...selectedIds.value];
    [nextIds[index], nextIds[nextIndex]] = [nextIds[nextIndex], nextIds[index]];
    selectedIds.value = nextIds;
};

const removeCategory = (categoryId: number) => {
    selectedIds.value = selectedIds.value.filter((id) => id !== categoryId);
};

const loadData = async () => {
    try {
        loading.value = true;

        const [categoryRes, settingRes] = await Promise.all([
            api.get("/admin-api/product-categories", { params: { limit: 200 } }),
            adminSettingService.getHomeCategory(),
        ]);

        categories.value = categoryRes.data.data.data || [];

        const validCategoryIds = new Set(categories.value.map((item) => item.id));
        const savedIds = Array.isArray(settingRes.settings.category_ids) ? settingRes.settings.category_ids : [];

        selectedIds.value = savedIds.filter((id: number) => validCategoryIds.has(id));
    } catch (err) {
        handleErrorResponse(err);
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    try {
        saving.value = true;
        const res = await adminSettingService.updateHomeCategory({
            category_ids: selectedIds.value,
        });

        selectedIds.value = Array.isArray(res.settings.category_ids) ? res.settings.category_ids : [];

        handleSuccessResponse({
            data: {
                status: true,
                message: "Cập nhật danh mục trang chủ thành công",
            },
        });
    } catch (err) {
        handleErrorResponse(err);
    } finally {
        saving.value = false;
    }
};

onMounted(loadData);
</script>
