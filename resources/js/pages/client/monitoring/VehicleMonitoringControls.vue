<script setup lang="ts">
import type { MonitoringSubscription } from '@/types/monitoring-plan.type';
import type { UserVehicle } from '@/types/traffic-fine.type';

defineProps<{
    vehicle: UserVehicle;
    subscription: MonitoringSubscription | null;
    intervalHours: number;
    saving: boolean;
    loading: boolean;
}>();
defineEmits<{ monitoring: [enabled: boolean]; email: [enabled: boolean] }>();
</script>

<template>
    <div class="grid gap-2">
        <label class="flex min-h-11 items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Theo dõi định kỳ</span>
                <span class="block text-xs text-slate-500">{{
                    vehicle.monitoring?.enabled ? `Đang bật · Mỗi ${intervalHours} giờ` : 'Đang tắt'
                }}</span>
            </span>
            <input
                type="checkbox"
                class="h-5 w-5 shrink-0 rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                :aria-label="`Theo dõi định kỳ cho xe ${vehicle.plate}`"
                :checked="Boolean(vehicle.monitoring?.enabled)"
                :disabled="loading || saving || (!vehicle.monitoring?.enabled && (!subscription || subscription.remaining_vehicle_count <= 0))"
                @change="$emit('monitoring', ($event.target as HTMLInputElement).checked)"
            />
        </label>
        <label
            class="flex min-h-11 items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-2"
            :class="{ 'bg-slate-50 opacity-60': !vehicle.monitoring?.enabled }"
        >
            <span>
                <span class="block text-sm font-semibold text-slate-800">Gửi thông báo về email</span>
                <span class="block text-xs text-slate-500">Khi phát hiện hoặc thay đổi số vi phạm</span>
            </span>
            <input
                type="checkbox"
                class="h-5 w-5 shrink-0 rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                :aria-label="`Thông báo email cho xe ${vehicle.plate}`"
                :checked="Boolean(vehicle.monitoring?.email_notifications)"
                :disabled="loading || saving || !vehicle.monitoring?.enabled"
                @change="$emit('email', ($event.target as HTMLInputElement).checked)"
            />
        </label>
        <p v-if="saving" role="status" class="text-xs text-sky-700">Đang lưu cài đặt...</p>
    </div>
</template>
