<script setup lang="ts">
import { trafficFineService } from '@/services/traffic-fine.service';
import type { MonitoringSubscription } from '@/types/monitoring-plan.type';
import type { UserVehicle } from '@/types/traffic-fine.type';
import { BellRing, CarFront, Clock3, Mail, RefreshCw } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const vehicles = ref<UserVehicle[]>([]);
const intervalHours = ref(6);
const subscription = ref<MonitoringSubscription | null>(null);
const loading = ref(true);
const savingByVehicle = ref<Record<number, boolean>>({});

const enabledCount = computed(() => vehicles.value.filter((vehicle) => vehicle.monitoring?.enabled).length);
const emailCount = computed(() => vehicles.value.filter((vehicle) => vehicle.monitoring?.enabled && vehicle.monitoring.email_notifications).length);

const loadVehicles = async (): Promise<void> => {
    loading.value = true;

    try {
        const monitoring = await trafficFineService.monitoring();
        vehicles.value = monitoring.vehicles;
        intervalHours.value = monitoring.interval_hours;
        subscription.value = monitoring.subscription;
    } catch {
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
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

onMounted(loadVehicles);
</script>

<template>
    <div class="mx-auto grid max-w-5xl gap-7">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold text-sky-700">Monitoring</p>
                <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Theo dõi biển số</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Hệ thống tự động kiểm tra mỗi xe sau mỗi {{ intervalHours }} giờ. Email chỉ được gửi khi lần đầu phát hiện lỗi hoặc khi số lỗi
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
                Làm mới
            </button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <CarFront class="h-5 w-5 text-sky-700" />
                <p class="mt-4 text-2xl font-black text-slate-950">{{ vehicles.length }}</p>
                <p class="mt-1 text-sm text-slate-500">Xe đã lưu</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <BellRing class="h-5 w-5 text-emerald-700" />
                <p class="mt-4 text-2xl font-black text-slate-950">{{ enabledCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Đang theo dõi định kỳ</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-5">
                <Mail class="h-5 w-5 text-indigo-700" />
                <p class="mt-4 text-2xl font-black text-slate-950">{{ emailCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Đã bật thông báo email</p>
            </article>
        </section>

        <section v-if="subscription" class="rounded-xl border border-sky-200 bg-sky-50 p-5 text-sm text-slate-700">
            <strong class="text-slate-950">{{ subscription.plan_name }}</strong>
            · Đang dùng {{ subscription.enabled_vehicle_count }}/{{ subscription.vehicle_limit }} xe · Còn
            {{ subscription.remaining_vehicle_count }} lượt theo dõi
        </section>
        <section v-else class="rounded-xl border border-amber-200 bg-amber-50 p-5">
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
                <h2 class="text-lg font-bold text-slate-950">Cấu hình theo từng xe</h2>
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

            <div v-else class="divide-y divide-slate-200">
                <article v-for="vehicle in vehicles" :key="vehicle.id" class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-950">{{ vehicle.name }}</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-700">{{ vehicle.plate }}</span>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1.5">
                                <Clock3 class="h-4 w-4" />
                                {{ formatCheckedAt(vehicle.monitoring?.last_checked_at) }}
                            </span>
                            <span>
                                Số lỗi gần nhất:
                                <strong class="text-slate-700">{{ vehicle.monitoring?.last_violation_count ?? 'Chưa có dữ liệu' }}</strong>
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:min-w-80">
                        <label class="flex min-h-11 cursor-pointer items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-2.5">
                            <span>
                                <span class="block text-sm font-bold text-slate-800">Theo dõi định kỳ</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Tự động kiểm tra mỗi {{ intervalHours }} giờ</span>
                            </span>
                            <input
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                                :checked="Boolean(vehicle.monitoring?.enabled)"
                                :disabled="
                                    savingByVehicle[vehicle.id] ||
                                    (!vehicle.monitoring?.enabled && (!subscription || subscription.remaining_vehicle_count <= 0))
                                "
                                @change="toggleMonitoring(vehicle, ($event.target as HTMLInputElement).checked)"
                            />
                        </label>

                        <label
                            class="flex min-h-11 items-center justify-between gap-4 rounded-lg border border-slate-200 px-4 py-2.5"
                            :class="vehicle.monitoring?.enabled ? 'cursor-pointer' : 'cursor-not-allowed bg-slate-50 opacity-60'"
                        >
                            <span>
                                <span class="block text-sm font-bold text-slate-800">Gửi thông báo về email</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Chỉ gửi khi số lỗi thay đổi</span>
                            </span>
                            <input
                                type="checkbox"
                                class="h-5 w-5 rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                                :checked="Boolean(vehicle.monitoring?.email_notifications)"
                                :disabled="!vehicle.monitoring?.enabled || savingByVehicle[vehicle.id]"
                                @change="toggleEmail(vehicle, ($event.target as HTMLInputElement).checked)"
                            />
                        </label>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
