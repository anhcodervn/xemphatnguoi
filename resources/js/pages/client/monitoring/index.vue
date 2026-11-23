<script setup lang="ts">
import DataTable from '@/components/shared/DataTable/index.vue';
import { trafficFineService } from '@/services/traffic-fine.service';
import type { MonitoringSubscription } from '@/types/monitoring-plan.type';
import type { UserVehicle } from '@/types/traffic-fine.type';
import { BellRing, CarFront, Mail, RefreshCw, Search } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import VehicleMonitoringControls from './VehicleMonitoringControls.vue';

const vehicles = ref<UserVehicle[]>([]);
const intervalHours = ref(24);
const subscription = ref<MonitoringSubscription | null>(null);
const loading = ref(true);
const savingByVehicle = ref<Record<number, boolean>>({});
const loadError = ref('');
const search = ref('');
const status = ref('all');
const currentPage = ref(1);
const pageSize = ref(10);
const columns = [
    { accessorKey: 'vehicle', header: 'Xe / biển số' },
    { accessorKey: 'checked', header: 'Kiểm tra thành công gần nhất' },
    { accessorKey: 'violations', header: 'Kết quả gần nhất' },
    { accessorKey: 'controls', header: 'Cài đặt theo dõi' },
];
const vehicleTypes = { car: 'Ô tô', motorbike: 'Xe máy', electric_motorbike: 'Xe máy điện' };
const filteredVehicles = computed(() => {
    const keyword = search.value.trim().toLocaleLowerCase('vi-VN');
    return vehicles.value.filter((vehicle) => {
        const matchesStatus = status.value === 'all' || Boolean(vehicle.monitoring?.enabled) === (status.value === 'enabled');
        return matchesStatus && (!keyword || `${vehicle.name} ${vehicle.plate}`.toLocaleLowerCase('vi-VN').includes(keyword));
    });
});
const totalPages = computed(() => Math.max(1, Math.ceil(filteredVehicles.value.length / pageSize.value)));
const pagedVehicles = computed(() => filteredVehicles.value.slice((currentPage.value - 1) * pageSize.value, currentPage.value * pageSize.value));
const goToPage = async (page: number): Promise<void> => {
    currentPage.value = Math.max(1, Math.min(page, totalPages.value));
};
watch([search, status, pageSize], () => {
    currentPage.value = 1;
});
watch(totalPages, (pages) => {
    currentPage.value = Math.min(currentPage.value, pages);
});

const enabledCount = computed(() => vehicles.value.filter((vehicle) => vehicle.monitoring?.enabled).length);
const emailCount = computed(() => vehicles.value.filter((vehicle) => vehicle.monitoring?.enabled && vehicle.monitoring.email_notifications).length);

const loadVehicles = async (): Promise<void> => {
    loading.value = true;
    loadError.value = '';

    try {
        const monitoring = await trafficFineService.monitoring();
        vehicles.value = monitoring.vehicles;
        intervalHours.value = monitoring.interval_hours;
        subscription.value = monitoring.subscription;
    } catch {
        loadError.value = 'Không thể tải dữ liệu mới. Thông tin bên dưới có thể chưa được cập nhật.';
        void Swal.fire({ icon: 'error', title: 'Không thể tải dữ liệu', text: 'Không thể tải danh sách biển số đang theo dõi.' });
    } finally {
        loading.value = false;
    }
};

const updateMonitoring = async (vehicle: UserVehicle, payload: { enabled: boolean; email_notifications: boolean }): Promise<void> => {
    savingByVehicle.value[vehicle.id] = true;
    const wasEnabled = Boolean(vehicle.monitoring?.enabled);

    try {
        vehicle.monitoring = await trafficFineService.updateVehicleMonitoring(vehicle.id, payload);
        if (subscription.value && wasEnabled !== payload.enabled) {
            subscription.value.enabled_vehicle_count += payload.enabled ? 1 : -1;
            subscription.value.remaining_vehicle_count = Math.max(0, subscription.value.vehicle_limit - subscription.value.enabled_vehicle_count);
        }
        void Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Đã cập nhật theo dõi biển số',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true,
        });
    } catch (error) {
        const message = (error as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Không thể cập nhật theo dõi biển số.';
        void Swal.fire({ icon: 'error', title: 'Cập nhật không thành công', text: message });
    } finally {
        savingByVehicle.value[vehicle.id] = false;
    }
};

const toggleMonitoring = (vehicle: UserVehicle, enabled: boolean): void => {
    void updateMonitoring(vehicle, {
        enabled,
        email_notifications: enabled && Boolean(vehicle.monitoring?.email_notifications),
    });
};

const toggleEmail = (vehicle: UserVehicle, emailNotifications: boolean): void => {
    void updateMonitoring(vehicle, {
        enabled: true,
        email_notifications: emailNotifications,
    });
};

const formatCheckedAt = (value: string | null | undefined): string => {
    if (!value) {
        return 'Chưa kiểm tra';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
        timeZone: 'Asia/Ho_Chi_Minh',
    }).format(new Date(value));
};

const violationLabel = (vehicle: UserVehicle): string => {
    const count = vehicle.monitoring?.last_violation_count;
    if (count === null || count === undefined) return 'Chưa có kết quả kiểm tra';
    return count === 0 ? 'Không phát hiện vi phạm' : `Phát hiện ${count} vi phạm`;
};
const violationClass = (vehicle: UserVehicle): string => {
    const count = vehicle.monitoring?.last_violation_count;
    if (count === null || count === undefined) return 'bg-slate-100 text-slate-600';
    return count === 0 ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800';
};

onMounted(loadVehicles);
</script>

<template>
    <div class="mx-auto grid min-w-0 max-w-5xl gap-5 sm:gap-7">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Theo dõi tự động</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Theo dõi biển số</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Hệ thống tự động kiểm tra mỗi xe sau mỗi {{ intervalHours }} giờ. Email được gửi khi lần đầu phát hiện vi phạm hoặc khi số vi phạm
                    thay đổi.
                </p>
            </div>
            <button
                type="button"
                :disabled="loading"
                class="app-focus inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-60"
                @click="loadVehicles"
            >
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                Tải lại danh sách
            </button>
        </header>

        <p v-if="loadError" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ loadError }}</p>
        <section class="grid grid-cols-3 gap-2 sm:gap-4">
            <article class="rounded-xl border border-slate-200 bg-white p-3 sm:p-5">
                <CarFront class="h-5 w-5 text-sky-700" />
                <p class="mt-4 text-2xl font-black text-slate-950">{{ vehicles.length }}</p>
                <p class="mt-1 text-sm text-slate-500">Xe đã lưu</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-3 sm:p-5">
                <BellRing class="h-5 w-5 text-emerald-700" />
                <p class="mt-4 text-2xl font-black text-slate-950">{{ enabledCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Đang theo dõi định kỳ</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-3 sm:p-5">
                <Mail class="h-5 w-5 text-indigo-700" />
                <p class="mt-4 text-2xl font-black text-slate-950">{{ emailCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Đã bật thông báo email</p>
            </article>
        </section>

        <section v-if="subscription" class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-slate-700">
            <strong class="text-slate-950">{{ subscription.plan_name }}</strong>
            · Đang dùng {{ subscription.enabled_vehicle_count }}/{{ subscription.vehicle_limit }} xe · Còn
            {{ subscription.remaining_vehicle_count }} lượt theo dõi
        </section>
        <section v-else-if="!loading && !loadError" class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="font-bold text-slate-950">Bạn chưa có gói theo dõi</h2>
            <p class="mt-1 text-sm text-slate-600">Đăng ký gói dịch vụ để bật theo dõi tự động cho xe.</p>
            <RouterLink
                to="/dashboard/packages"
                class="app-focus mt-4 inline-flex min-h-11 items-center rounded-lg bg-amber-700 px-5 text-sm font-bold text-white"
                >Xem gói dịch vụ</RouterLink
            >
        </section>

        <section class="rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-lg font-bold text-slate-950">Danh sách xe theo dõi</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Kết quả phản ánh lần kiểm tra thành công gần nhất, có thể thay đổi ở lần kiểm tra tiếp theo. Tải lại danh sách không chạy kiểm tra
                    mới.
                </p>
                <p class="mt-1 text-sm text-slate-500">Tắt theo dõi cũng sẽ tắt gửi email cho xe đó.</p>
            </div>

            <div v-if="loading" class="p-10 text-center text-sm text-slate-500">Đang tải cấu hình theo dõi...</div>

            <div v-else-if="!vehicles.length" class="p-8 text-center">
                <CarFront class="mx-auto h-10 w-10 text-slate-300" />
                <h3 class="mt-4 font-bold text-slate-950">Bạn chưa có xe nào</h3>
                <p class="mt-2 text-sm text-slate-500">Hãy thêm xe trước khi bật theo dõi biển số.</p>
                <RouterLink
                    to="/dashboard/vehicles"
                    class="app-focus mt-5 inline-flex min-h-11 items-center rounded-lg bg-sky-700 px-5 text-sm font-bold text-white"
                >
                    Thêm xe
                </RouterLink>
            </div>

            <div v-else class="grid gap-4 p-4 sm:p-5">
                <div class="grid gap-3 sm:grid-cols-[1fr_180px]">
                    <label class="relative min-w-0">
                        <span class="sr-only">Tìm theo biển số hoặc tên xe</span>
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Tìm biển số hoặc tên xe"
                            class="app-focus min-h-11 w-full rounded-lg border border-slate-300 pl-9 pr-3 text-sm"
                        />
                    </label>
                    <select
                        v-model="status"
                        aria-label="Lọc trạng thái theo dõi"
                        class="app-focus min-h-11 rounded-lg border border-slate-300 px-3 text-sm"
                    >
                        <option value="all">Tất cả xe</option>
                        <option value="enabled">Đang bật theo dõi</option>
                        <option value="disabled">Đã tắt theo dõi</option>
                    </select>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                    <p aria-live="polite">{{ filteredVehicles.length }} xe phù hợp · Trang {{ currentPage }}/{{ totalPages }}</p>
                    <label class="flex items-center gap-2"
                        >Hiển thị
                        <select v-model.number="pageSize" class="app-focus min-h-11 rounded-lg border border-slate-300 px-3">
                            <option :value="10">10 xe</option>
                            <option :value="20">20 xe</option>
                            <option :value="50">50 xe</option>
                        </select>
                    </label>
                </div>
                <DataTable
                    :data="pagedVehicles"
                    :columns="columns"
                    :go-to-page="goToPage"
                    :total-pages="totalPages"
                    :current-page="currentPage"
                    :loading="loading"
                    class-custom="w-full min-w-[880px]"
                    empty-message="Không tìm thấy xe phù hợp. Thử đổi từ khóa hoặc bộ lọc."
                >
                    <template #vehicle="{ row }">
                        <p class="font-mono text-base font-bold text-slate-950">{{ row.plate }}</p>
                        <p class="mt-1 font-semibold">{{ row.name }}</p>
                        <p class="text-xs text-slate-500">{{ vehicleTypes[row.vehicle_type as keyof typeof vehicleTypes] }}</p>
                    </template>
                    <template #checked="{ row }">
                        <time
                            v-if="row.monitoring?.last_checked_at"
                            :datetime="row.monitoring.last_checked_at"
                            class="whitespace-nowrap font-semibold text-slate-800"
                            >{{ formatCheckedAt(row.monitoring.last_checked_at) }}</time
                        >
                        <span v-else class="text-slate-500">Chưa kiểm tra</span>
                        <p class="mt-1 text-xs text-slate-500">Giờ Việt Nam (UTC+7)</p>
                    </template>
                    <template #violations="{ row }">
                        <span class="inline-flex rounded-lg px-3 py-2 text-sm font-semibold" :class="violationClass(row)">{{
                            violationLabel(row)
                        }}</span>
                        <p class="mt-2 max-w-48 text-xs leading-5 text-slate-500">
                            {{
                                row.monitoring?.last_violation_count == null
                                    ? 'Kết quả sẽ hiển thị sau lần kiểm tra thành công đầu tiên.'
                                    : 'Theo dữ liệu của lần kiểm tra gần nhất.'
                            }}
                        </p>
                    </template>
                    <template #controls="{ row }">
                        <VehicleMonitoringControls
                            class="min-w-60"
                            :vehicle="row"
                            :subscription="subscription"
                            :interval-hours="intervalHours"
                            :saving="Boolean(savingByVehicle[row.id])"
                            :loading="loading"
                            @monitoring="toggleMonitoring(row, $event)"
                            @email="toggleEmail(row, $event)"
                        />
                    </template>
                    <template #mobile-row="{ row }">
                        <article class="min-w-0 rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="font-mono text-lg font-black text-slate-950">{{ row.plate }}</h3>
                                    <p class="break-words text-sm text-slate-600">
                                        {{ row.name }} · {{ vehicleTypes[row.vehicle_type as keyof typeof vehicleTypes] }}
                                    </p>
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="row.monitoring?.enabled ? 'bg-sky-50 text-sky-800' : 'bg-slate-100 text-slate-600'"
                                    >{{ row.monitoring?.enabled ? 'Đang theo dõi' : 'Đã tắt' }}</span
                                >
                            </div>
                            <dl class="my-4 grid gap-3 rounded-lg bg-slate-50 p-3">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Kiểm tra thành công gần nhất</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-900">
                                        <time v-if="row.monitoring?.last_checked_at" :datetime="row.monitoring.last_checked_at">{{
                                            formatCheckedAt(row.monitoring.last_checked_at)
                                        }}</time>
                                        <span v-else>Chưa kiểm tra</span>
                                    </dd>
                                    <dd class="mt-0.5 text-xs text-slate-500">Giờ Việt Nam (UTC+7)</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Vi phạm ghi nhận ở lần kiểm tra gần nhất</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex rounded-lg px-2.5 py-1.5 text-sm font-bold" :class="violationClass(row)">{{
                                            violationLabel(row)
                                        }}</span>
                                    </dd>
                                </div>
                            </dl>
                            <VehicleMonitoringControls
                                :vehicle="row"
                                :subscription="subscription"
                                :interval-hours="intervalHours"
                                :saving="Boolean(savingByVehicle[row.id])"
                                :loading="loading"
                                @monitoring="toggleMonitoring(row, $event)"
                                @email="toggleEmail(row, $event)"
                            />
                        </article>
                    </template>
                </DataTable>
            </div>
        </section>
    </div>
</template>
