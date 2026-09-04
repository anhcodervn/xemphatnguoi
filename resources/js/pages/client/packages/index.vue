<script setup lang="ts">
import { clientMonitoringPlanService } from '@/services/client-monitoring-plan.service';
import type { MonitoringPlan, MonitoringSubscription } from '@/types/monitoring-plan.type';
import { sanitizeRichText } from '@/utils/rich-text';
import { CalendarDays, CarFront, Check, PackageOpen } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';

const plans = ref<MonitoringPlan[]>([]);
const subscription = ref<MonitoringSubscription | null>(null);
const quantities = ref<Record<number, number>>({});
const loading = ref(true);
const subscribingId = ref<number | null>(null);
const updatingAutoRenew = ref(false);

const money = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });
const formatMoney = (value: string | number | null): string => money.format(Number(value ?? 0));
const renderedDescription = (plan: MonitoringPlan): string =>
    sanitizeRichText(plan.description, '<p>Theo dõi tự động và gửi email khi dữ liệu vi phạm thay đổi.</p>');
const quantityFor = (plan: MonitoringPlan): number => quantities.value[plan.id] ?? plan.min_vehicle_count;
const vehicleLimitFor = (plan: MonitoringPlan): number => (plan.is_custom ? quantityFor(plan) : Number(plan.vehicle_limit ?? 0));
const totalFor = (plan: MonitoringPlan): number => (plan.is_custom ? Number(plan.unit_price) * quantityFor(plan) : Number(plan.price));
const isUpgradeAvailable = (plan: MonitoringPlan): boolean =>
    Boolean(subscription.value?.is_active) && vehicleLimitFor(plan) > Number(subscription.value?.vehicle_limit ?? 0);
const expiresAt = computed(() =>
    subscription.value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'long' }).format(new Date(subscription.value.expires_at)) : '',
);

const load = async (): Promise<void> => {
    loading.value = true;
    try {
        const data = await clientMonitoringPlanService.index();
        plans.value = data.plans;
        subscription.value = data.subscription;
        data.plans.forEach((plan) => {
            if (plan.is_custom) {
                quantities.value[plan.id] = Math.max(plan.min_vehicle_count, Number(data.subscription?.vehicle_limit ?? 0) + 1);
            }
        });
    } catch {
        void Swal.fire({ icon: 'error', title: 'Không thể tải dữ liệu', text: 'Không thể tải danh sách gói dịch vụ.' });
    } finally {
        loading.value = false;
    }
};

const subscribe = async (plan: MonitoringPlan): Promise<void> => {
    const upgrading = isUpgradeAvailable(plan);
    const confirmation = await Swal.fire({
        icon: 'question',
        title: upgrading ? 'Xác nhận nâng cấp gói' : 'Xác nhận đăng ký gói',
        text: upgrading
            ? `Bạn sẽ thanh toán đủ ${formatMoney(totalFor(plan))}; gói “${plan.name}” bắt đầu chu kỳ mới ngay sau khi nâng cấp.`
            : `Bạn sẽ thanh toán ${formatMoney(totalFor(plan))} để đăng ký gói “${plan.name}”.`,
        showCancelButton: true,
        confirmButtonText: 'Thanh toán',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#0369a1',
        focusCancel: true,
    });

    if (!confirmation.isConfirmed) return;

    subscribingId.value = plan.id;
    try {
        subscription.value = upgrading
            ? await clientMonitoringPlanService.upgrade(plan.id, plan.is_custom ? quantityFor(plan) : undefined)
            : await clientMonitoringPlanService.subscribe(plan.id, plan.is_custom ? quantityFor(plan) : undefined);
        void Swal.fire({
            icon: 'success',
            title: upgrading ? 'Nâng cấp thành công' : 'Đăng ký thành công',
            text: upgrading ? 'Hạn mức xe mới đã được áp dụng.' : 'Gói theo dõi đã được kích hoạt.',
        });
    } catch (error) {
        const message = (error as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Không thể đăng ký gói dịch vụ.';
        void Swal.fire({ icon: 'error', title: 'Đăng ký không thành công', text: message });
    } finally {
        subscribingId.value = null;
    }
};

const updateAutoRenew = async (): Promise<void> => {
    if (!subscription.value || updatingAutoRenew.value) return;

    const enabled = !subscription.value.auto_renew;
    updatingAutoRenew.value = true;

    try {
        subscription.value = await clientMonitoringPlanService.updateAutoRenew(enabled);
        void Swal.fire({
            icon: 'success',
            title: enabled ? 'Đã bật tự gia hạn' : 'Đã tắt tự gia hạn',
            text: enabled ? 'Gói sẽ tự động gia hạn khi hết hạn nếu số dư ví đủ.' : 'Gói sẽ không tự động trừ tiền khi hết hạn.',
            timer: 2200,
            showConfirmButton: false,
        });
    } catch (error) {
        const message = (error as { response?: { data?: { message?: string } } }).response?.data?.message ?? 'Không thể cập nhật tự gia hạn.';
        void Swal.fire({ icon: 'error', title: 'Cập nhật không thành công', text: message });
    } finally {
        updatingAutoRenew.value = false;
    }
};

onMounted(load);
</script>

<template>
    <div class="grid gap-7">
        <header>
            <p class="text-sm font-bold text-sky-700">Gói dịch vụ</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Đăng ký theo dõi xe</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Mọi gói đều có đầy đủ tính năng theo dõi và email. Bạn chỉ chọn số xe cần sử dụng.
            </p>
        </header>

        <section
            v-if="subscription"
            class="rounded-xl border p-5 sm:p-6"
            :class="subscription.is_active ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-bold" :class="subscription.is_active ? 'text-emerald-700' : 'text-amber-700'">
                        {{ subscription.is_active ? 'Gói đang hoạt động' : 'Đang chờ tự gia hạn' }}
                    </p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">{{ subscription.plan_name }}</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Đang theo dõi {{ subscription.enabled_vehicle_count }}/{{ subscription.vehicle_limit }} xe · hết hạn {{ expiresAt }}
                    </p>
                    <p v-if="!subscription.is_active" class="mt-2 text-xs font-semibold text-amber-700">
                        Hệ thống sẽ gia hạn sau khi số dư ví đủ {{ formatMoney(subscription.total_price) }}.
                    </p>
                </div>
                <div class="flex flex-col gap-3 sm:items-end">
                    <div class="flex min-h-11 items-center gap-3 rounded-lg border border-white/80 bg-white px-3 shadow-sm">
                        <span class="text-sm font-bold text-slate-700">Tự gia hạn</span>
                        <button
                            type="button"
                            role="switch"
                            aria-label="Bật hoặc tắt tự gia hạn gói dịch vụ"
                            :aria-checked="subscription.auto_renew"
                            :disabled="updatingAutoRenew"
                            class="app-focus relative h-7 w-12 rounded-full transition disabled:cursor-wait disabled:opacity-60"
                            :class="subscription.auto_renew ? 'bg-emerald-600' : 'bg-slate-300'"
                            @click="updateAutoRenew"
                        >
                            <span
                                class="absolute top-1 h-5 w-5 rounded-full bg-white shadow-sm transition"
                                :class="subscription.auto_renew ? 'left-6' : 'left-1'"
                            />
                        </button>
                    </div>
                    <p class="max-w-xs text-xs leading-5 text-slate-500 sm:text-right">
                        Khi bật, hệ thống tự trừ {{ formatMoney(subscription.total_price) }} từ ví và giữ nguyên số lượng xe của gói.
                    </p>
                    <RouterLink
                        v-if="subscription.is_active"
                        to="/dashboard/monitoring"
                        class="app-focus inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-700 px-5 text-sm font-bold text-white"
                    >
                        Quản lý xe theo dõi
                    </RouterLink>
                </div>
            </div>
        </section>

        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Đang tải các gói...</div>
        <section v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="plan in plans" :key="plan.id" class="flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <PackageOpen class="h-6 w-6 text-sky-700" />
                <h2 class="mt-5 text-xl font-black text-slate-950">{{ plan.name }}</h2>
                <div
                    class="mt-2 min-h-12 text-sm leading-6 text-slate-500 [&_a]:font-semibold [&_a]:text-sky-700 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-sky-200 [&_blockquote]:pl-3 [&_h2]:text-base [&_h2]:font-black [&_h2]:text-slate-900 [&_h3]:font-bold [&_h3]:text-slate-800 [&_img]:mt-3 [&_img]:max-h-48 [&_img]:w-full [&_img]:rounded-lg [&_img]:object-cover [&_li]:ml-5 [&_ol]:list-decimal [&_p+p]:mt-2 [&_ul]:list-disc"
                    v-html="renderedDescription(plan)"
                ></div>

                <label v-if="plan.is_custom" class="mt-5 block text-sm font-bold text-slate-700">
                    Số lượng xe
                    <input
                        v-model.number="quantities[plan.id]"
                        type="number"
                        :min="plan.min_vehicle_count"
                        step="1"
                        class="app-focus mt-2 min-h-11 w-full rounded-lg border border-slate-300 px-3"
                    />
                    <span class="mt-1 block text-xs font-normal text-slate-500"
                        >Tối thiểu {{ plan.min_vehicle_count }} xe, không giới hạn tối đa.</span
                    >
                </label>
                <div v-else class="mt-5 flex items-center gap-2 text-sm font-bold text-slate-700">
                    <CarFront class="h-4 w-4" /> Tối đa {{ plan.vehicle_limit }} xe
                </div>

                <div class="mt-5 border-t border-slate-100 pt-5">
                    <p class="text-2xl font-black text-slate-950">{{ formatMoney(totalFor(plan)) }}</p>
                    <p v-if="plan.is_custom" class="mt-1 text-xs text-slate-500">{{ formatMoney(plan.unit_price) }} / xe</p>
                    <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                        <CalendarDays class="h-4 w-4" /> {{ plan.duration_days }} ngày
                    </p>
                </div>

                <ul class="mt-5 grid gap-2 text-sm text-slate-600">
                    <li class="flex gap-2"><Check class="h-4 w-4 text-emerald-600" /> Kiểm tra tự động theo chu kỳ</li>
                    <li class="flex gap-2"><Check class="h-4 w-4 text-emerald-600" /> Email khi số lỗi thay đổi</li>
                </ul>
                <button
                    type="button"
                    :disabled="
                        subscribingId !== null ||
                        (plan.is_custom && quantityFor(plan) < plan.min_vehicle_count) ||
                        (Boolean(subscription) && !isUpgradeAvailable(plan))
                    "
                    class="app-focus mt-6 min-h-11 rounded-lg bg-sky-700 px-4 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    @click="subscribe(plan)"
                >
                    {{
                        subscribingId === plan.id
                            ? 'Đang thanh toán...'
                            : isUpgradeAvailable(plan)
                              ? `Nâng cấp lên ${vehicleLimitFor(plan)} xe`
                              : subscription
                                ? plan.is_custom
                                    ? 'Chọn số xe cao hơn để nâng cấp'
                                    : 'Hạn mức không cao hơn gói hiện tại'
                                : 'Đăng ký bằng ví'
                    }}
                </button>
            </article>
        </section>
    </div>
</template>
