<script setup lang="ts">
import { trafficFineService } from '@/services/traffic-fine.service';
import type { UserVehicle, VehicleType } from '@/types/traffic-fine.type';
import type { AxiosError } from 'axios';
import { Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { onMounted, reactive, ref } from 'vue';

const vehicles = ref<UserVehicle[]>([]);
const loading = ref(true);
const saving = ref(false);
const errorMessage = ref('');
const editingId = ref<number | null>(null);
const form = reactive<{ name: string; plate: string; vehicle_type: VehicleType }>({ name: '', plate: '', vehicle_type: 'car' });

const load = async (): Promise<void> => {
    loading.value = true;
    try {
        vehicles.value = await trafficFineService.vehicles();
    } catch {
        errorMessage.value = 'Không thể tải danh sách xe.';
    } finally {
        loading.value = false;
    }
};
const resetForm = (): void => {
    editingId.value = null;
    form.name = '';
    form.plate = '';
    form.vehicle_type = 'car';
    errorMessage.value = '';
};
const edit = (vehicle: UserVehicle): void => {
    editingId.value = vehicle.id;
    form.name = vehicle.name;
    form.plate = vehicle.plate;
    form.vehicle_type = vehicle.vehicle_type;
    errorMessage.value = '';
};
const save = async (): Promise<void> => {
    saving.value = true;
    errorMessage.value = '';
    try {
        if (editingId.value) {
            await trafficFineService.updateVehicle(editingId.value, form);
        } else {
            await trafficFineService.createVehicle(form);
        }
        resetForm();
        await load();
    } catch (error) {
        errorMessage.value = (error as AxiosError<{ message?: string }>).response?.data?.message ?? 'Không thể lưu xe.';
    } finally {
        saving.value = false;
    }
};
const remove = async (vehicle: UserVehicle): Promise<void> => {
    if (!window.confirm(`Xóa ${vehicle.name} khỏi garage?`)) return;
    await trafficFineService.deleteVehicle(vehicle.id);
    await load();
};
const publicLookupUrl = (vehicle: UserVehicle): string =>
    `/tra-cuu/${encodeURIComponent(vehicle.plate)}?${new URLSearchParams({ vehicle_type: vehicle.vehicle_type }).toString()}`;
onMounted(load);
</script>

<template>
    <div class="grid gap-7 xl:grid-cols-[360px_1fr]">
        <section>
            <header>
                <p class="text-sm font-bold text-sky-700">Garage</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Xe của tôi</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Lưu biển số để tra cứu nhanh và chuẩn bị cho monitoring.</p>
            </header>
            <form class="mt-6 grid gap-4 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-slate-950">{{ editingId ? 'Cập nhật xe' : 'Thêm xe' }}</h2>
                    <button
                        v-if="editingId"
                        type="button"
                        aria-label="Hủy chỉnh sửa"
                        class="app-focus rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                        @click="resetForm"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div>
                    <label for="vehicle-name" class="mb-2 block text-sm font-bold">Tên gợi nhớ</label
                    ><input
                        id="vehicle-name"
                        v-model="form.name"
                        required
                        maxlength="100"
                        placeholder="Ví dụ: Mazda 3"
                        class="app-focus h-11 w-full rounded-lg border border-slate-300 px-3"
                    />
                </div>
                <div>
                    <label for="vehicle-plate" class="mb-2 block text-sm font-bold">Biển số</label
                    ><input
                        id="vehicle-plate"
                        v-model="form.plate"
                        required
                        maxlength="20"
                        placeholder="30A-123.45"
                        class="app-focus h-11 w-full rounded-lg border border-slate-300 px-3 uppercase"
                    />
                </div>
                <div>
                    <label for="vehicle-type" class="mb-2 block text-sm font-bold">Loại xe</label
                    ><select
                        id="vehicle-type"
                        v-model="form.vehicle_type"
                        class="app-focus h-11 w-full rounded-lg border border-slate-300 bg-white px-3"
                    >
                        <option value="car">Ô tô</option>
                        <option value="motorbike">Xe máy</option>
                        <option value="electric_motorbike">Xe máy điện</option>
                    </select>
                </div>
                <button
                    :disabled="saving"
                    class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-sky-700 px-4 text-sm font-bold text-white disabled:opacity-60"
                >
                    <Plus class="h-4 w-4" />{{ saving ? 'Đang lưu' : editingId ? 'Lưu thay đổi' : 'Thêm xe' }}
                </button>
                <p v-if="errorMessage" role="alert" class="text-sm leading-6 text-red-700">{{ errorMessage }}</p>
            </form>
        </section>
        <section class="min-w-0">
            <div class="rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                <h2 class="text-lg font-bold text-slate-950">Danh sách xe</h2>
                <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải...</div>
                <div v-else-if="!vehicles.length" class="mt-5 rounded-lg bg-slate-50 p-6 text-center text-sm text-slate-500">
                    Bạn chưa thêm xe nào.
                </div>
                <div v-else class="mt-5 grid gap-3">
                    <article
                        v-for="vehicle in vehicles"
                        :key="vehicle.id"
                        class="flex flex-col gap-4 rounded-lg border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h3 class="font-bold text-slate-950">{{ vehicle.name }}</h3>
                            <p class="mt-1 font-mono text-sm font-semibold text-slate-600">{{ vehicle.plate }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a
                                :href="publicLookupUrl(vehicle)"
                                class="app-focus inline-flex min-h-10 items-center gap-2 rounded-lg bg-sky-700 px-3 text-xs font-bold text-white disabled:opacity-60"
                            >
                                <Search class="h-4 w-4" />Tra cứu trên website</a
                            ><button
                                type="button"
                                class="app-focus inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50"
                                aria-label="Sửa xe"
                                @click="edit(vehicle)"
                            >
                                <Pencil class="h-4 w-4" /></button
                            ><button
                                type="button"
                                class="app-focus inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border border-red-200 text-red-600 hover:bg-red-50"
                                aria-label="Xóa xe"
                                @click="remove(vehicle)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>
