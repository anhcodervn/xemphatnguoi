<script setup lang="ts">
import { clientProfileService } from '@/services/client-profile.service';
import { useUserStore } from '@/stores/user.store';
import type {
    ClientProfilePaginationMeta,
    ClientProfileType,
    UserLogItem,
    WalletTransactionItem,
} from '@/types/client-profile.type';
import type { UserType } from '@/types/user.type';
import { formatTime } from '@/utils/helpers/format';
import type { AxiosError } from 'axios';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import TabChangePass from './components/TabChangePass.vue';
import TabProfile from './components/TabProfile.vue';
import TabsComponent from './components/TabsComponent.vue';
import TabUserLog from './components/TabUserLog.vue';
import TabWalletTransaction from './components/TabWalletTransaction.vue';

type TabKey = 'profile' | 'password' | 'user-log' | 'wallet-log';
type ValidationErrors = Record<string, string[]>;

const DEFAULT_META: ClientProfilePaginationMeta = {
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
};

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();

const tabKeys: TabKey[] = ['profile', 'password', 'user-log', 'wallet-log'];
const activeTab = ref<TabKey>('profile');
const profile = ref<ClientProfileType | null>(null);
const profileLoaded = ref(false);
const userLogsLoaded = ref(false);
const walletTransactionsLoaded = ref(false);
const savingProfile = ref(false);
const savingPassword = ref(false);
const loggingOutDevices = ref(false);
const loadingUserLogs = ref(false);
const loadingWalletTransactions = ref(false);

const profileForm = reactive({
    avatar: '',
    full_name: '',
    email: '',
    phone: '',
    username: '',
});

const passwordForm = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const profileErrors = reactive<Partial<Record<'avatar' | 'full_name' | 'email' | 'phone' | 'username', string>>>({});
const passwordErrors = reactive<Partial<Record<'current_password' | 'password' | 'password_confirmation', string>>>({});

const userLogFilters = reactive({
    search: '',
    action: 'all',
    page: 1,
});

const walletLogFilters = reactive({
    search: '',
    type: 'all',
    page: 1,
});

const userLogs = ref<UserLogItem[]>([]);
const userLogsMeta = ref<ClientProfilePaginationMeta>({ ...DEFAULT_META });
const walletTransactions = ref<WalletTransactionItem[]>([]);
const walletTransactionsMeta = ref<ClientProfilePaginationMeta>({ ...DEFAULT_META });

let userLogSearchTimer: ReturnType<typeof setTimeout> | null = null;
let walletSearchTimer: ReturnType<typeof setTimeout> | null = null;

const accountMeta = computed(() => [
    { label: 'User ID', value: profile.value?.id ? `#${profile.value.id}` : '--' },
    { label: 'Trạng thái', value: normalizeStatus(profile.value?.status) },
    { label: 'Ngày tạo tài khoản', value: formatTime(profile.value?.created_at ?? null, 'H:i d/m/Y') || '--' },
    { label: 'Email xác thực', value: profile.value?.security.email_verified ? 'Đã xác thực' : 'Chưa xác thực' },
    { label: '2FA', value: profile.value?.security.has_2fa ? 'Đã bật' : 'Chưa bật' },
    {
        label: 'Phiên gần nhất',
        value: profile.value?.last_login_at
            ? `${formatTime(profile.value.last_login_at, 'H:i d/m/Y')} - ${profile.value.last_login_ip ?? 'IP ẩn'}`
            : 'Chưa có dữ liệu',
    },
]);

const syncRouteTab = (): void => {
    const routeTab = route.query.tab;
    const nextTab = typeof routeTab === 'string' && tabKeys.includes(routeTab as TabKey) ? (routeTab as TabKey) : 'profile';

    activeTab.value = nextTab;
};

const syncProfileForm = (): void => {
    profileForm.avatar = profile.value?.avatar ?? '';
    profileForm.full_name = profile.value?.full_name ?? '';
    profileForm.email = profile.value?.email ?? '';
    profileForm.phone = profile.value?.phone ?? '';
    profileForm.username = profile.value?.username ?? '';
};

const clearProfileErrors = (): void => {
    profileErrors.avatar = '';
    profileErrors.full_name = '';
    profileErrors.email = '';
    profileErrors.phone = '';
    profileErrors.username = '';
};

const clearPasswordErrors = (): void => {
    passwordErrors.current_password = '';
    passwordErrors.password = '';
    passwordErrors.password_confirmation = '';
};

const applyValidationErrors = (
    errors: ValidationErrors | undefined,
    target: Record<string, string>,
    mapping: Record<string, string>,
): void => {
    Object.keys(target).forEach((key) => {
        target[key] = '';
    });

    if (!errors) {
        return;
    }

    Object.entries(mapping).forEach(([source, destination]) => {
        target[destination] = errors[source]?.[0] ?? '';
    });
};

const extractErrorMessage = (error: unknown, fallback: string): string => {
    const axiosError = error as AxiosError<{ message?: string }>;

    return axiosError.response?.data?.message || fallback;
};

const mergeProfileIntoStore = (currentProfile: ClientProfileType): void => {
    const currentUser = userStore.user;

    if (!currentUser) {
        return;
    }

    userStore.setUser({
        ...currentUser,
        avatar: currentProfile.avatar,
        username: currentProfile.username,
        email: currentProfile.email,
        phone: currentProfile.phone,
        full_name: currentProfile.full_name,
        status: typeof currentProfile.status === 'number' ? currentProfile.status : currentUser.status,
        last_login_at: currentProfile.last_login_at,
        last_login_ip: currentProfile.last_login_ip,
        email_verified_at: currentProfile.email_verified_at,
        created_at: currentProfile.created_at ?? currentUser.created_at,
        updated_at: currentProfile.updated_at ?? currentUser.updated_at,
    } as UserType);
};

const loadProfile = async (): Promise<void> => {
    const response = await clientProfileService.getProfile();

    profile.value = response;
    profileLoaded.value = true;
    syncProfileForm();
    mergeProfileIntoStore(response);
};

const loadUserLogs = async (page = userLogFilters.page): Promise<void> => {
    loadingUserLogs.value = true;

    try {
        const response = await clientProfileService.getUserLogs({
            page,
            search: userLogFilters.search.trim() || undefined,
            action: userLogFilters.action,
        });

        userLogs.value = response.data;
        userLogsMeta.value = response.meta;
        userLogFilters.page = response.meta.current_page;
        userLogsLoaded.value = true;
    } finally {
        loadingUserLogs.value = false;
    }
};

const loadWalletTransactions = async (page = walletLogFilters.page): Promise<void> => {
    loadingWalletTransactions.value = true;

    try {
        const response = await clientProfileService.getWalletTransactions({
            page,
            search: walletLogFilters.search.trim() || undefined,
            type: walletLogFilters.type,
        });

        walletTransactions.value = response.data;
        walletTransactionsMeta.value = response.meta;
        walletLogFilters.page = response.meta.current_page;
        walletTransactionsLoaded.value = true;
    } finally {
        loadingWalletTransactions.value = false;
    }
};

const loadTabData = async (tab: TabKey): Promise<void> => {
    if (tab === 'user-log' && !userLogsLoaded.value) {
        await loadUserLogs(1);
    }

    if (tab === 'wallet-log' && !walletTransactionsLoaded.value) {
        await loadWalletTransactions(1);
    }
};

const saveProfile = async (): Promise<void> => {
    clearProfileErrors();
    savingProfile.value = true;

    try {
        const response = await clientProfileService.updateProfile({
            avatar: profileForm.avatar.trim() || null,
            full_name: profileForm.full_name.trim() || null,
            email: profileForm.email.trim() || null,
            phone: profileForm.phone.trim() || null,
            username: profileForm.username.trim(),
        });

        profile.value = response.data;
        syncProfileForm();
        mergeProfileIntoStore(response.data);

        await Swal.fire('Đã lưu', response.message, 'success');
    } catch (error) {
        const axiosError = error as AxiosError<{ errors?: ValidationErrors; message?: string }>;

        applyValidationErrors(axiosError.response?.data?.errors, profileErrors as Record<string, string>, {
            avatar: 'avatar',
            full_name: 'full_name',
            email: 'email',
            phone: 'phone',
            username: 'username',
        });

        await Swal.fire('Không thể cập nhật', extractErrorMessage(error, 'Cập nhật thông tin thất bại.'), 'error');
    } finally {
        savingProfile.value = false;
    }
};

const updatePassword = async (): Promise<void> => {
    clearPasswordErrors();
    savingPassword.value = true;

    try {
        const message = await clientProfileService.updatePassword({
            current_password: passwordForm.current_password,
            password: passwordForm.new_password,
            password_confirmation: passwordForm.new_password_confirmation,
        });

        passwordForm.current_password = '';
        passwordForm.new_password = '';
        passwordForm.new_password_confirmation = '';

        await Swal.fire('Đã cập nhật', message, 'success');
    } catch (error) {
        const axiosError = error as AxiosError<{ errors?: ValidationErrors; message?: string }>;

        applyValidationErrors(axiosError.response?.data?.errors, passwordErrors as Record<string, string>, {
            current_password: 'current_password',
            password: 'password',
            password_confirmation: 'password_confirmation',
        });

        await Swal.fire('Không thể cập nhật', extractErrorMessage(error, 'Đổi mật khẩu thất bại.'), 'error');
    } finally {
        savingPassword.value = false;
    }
};

const logoutOtherDevices = async (): Promise<void> => {
    clearPasswordErrors();
    loggingOutDevices.value = true;

    try {
        const message = await clientProfileService.logoutOtherDevices({
            current_password: passwordForm.current_password,
        });

        passwordForm.current_password = '';
        await Swal.fire('Đã xử lý', message, 'success');
    } catch (error) {
        const axiosError = error as AxiosError<{ errors?: ValidationErrors; message?: string }>;

        applyValidationErrors(axiosError.response?.data?.errors, passwordErrors as Record<string, string>, {
            current_password: 'current_password',
        });

        await Swal.fire('Không thể thực hiện', extractErrorMessage(error, 'Đăng xuất thiết bị khác thất bại.'), 'error');
    } finally {
        loggingOutDevices.value = false;
    }
};

watch(
    () => route.query.tab,
    () => {
        syncRouteTab();
    },
);

watch(
    () => activeTab.value,
    async (tab) => {
        if (route.query.tab !== tab) {
            await router.replace({
                query: {
                    ...route.query,
                    tab,
                },
            });
        }

        await loadTabData(tab);
    },
);

watch(
    () => userLogFilters.action,
    async () => {
        userLogFilters.page = 1;

        if (activeTab.value === 'user-log') {
            await loadUserLogs(1);
        }
    },
);

watch(
    () => userLogFilters.search,
    () => {
        userLogFilters.page = 1;

        if (activeTab.value !== 'user-log') {
            return;
        }

        if (userLogSearchTimer) {
            clearTimeout(userLogSearchTimer);
        }

        userLogSearchTimer = setTimeout(() => {
            void loadUserLogs(1);
        }, 350);
    },
);

watch(
    () => walletLogFilters.type,
    async () => {
        walletLogFilters.page = 1;

        if (activeTab.value === 'wallet-log') {
            await loadWalletTransactions(1);
        }
    },
);

watch(
    () => walletLogFilters.search,
    () => {
        walletLogFilters.page = 1;

        if (activeTab.value !== 'wallet-log') {
            return;
        }

        if (walletSearchTimer) {
            clearTimeout(walletSearchTimer);
        }

        walletSearchTimer = setTimeout(() => {
            void loadWalletTransactions(1);
        }, 350);
    },
);

onMounted(async () => {
    if (!userStore.user) {
        await userStore.bootstrap({ silent: true });
    }

    syncRouteTab();

    if (!profileLoaded.value) {
        await loadProfile();
    }

    await loadTabData(activeTab.value);
});

function normalizeStatus(status: unknown): string {
    if (status === 'active' || status === 1 || status === '1') {
        return 'Đang hoạt động';
    }

    if (status === 'inactive' || status === 0 || status === '0') {
        return 'Tạm khóa';
    }

    if (status === 'banned') {
        return 'Bị khóa';
    }

    return 'Chưa rõ';
}
</script>

<template>
    <div class="space-y-3 pb-4">
        <section class="overflow-hidden rounded-[10px] border border-slate-200/80 bg-white shadow-[0_12px_30px_-28px_rgba(15,23,42,0.18)]">
            <TabsComponent v-model="activeTab" />

            <div class="p-3.5 md:p-4">
                <TabProfile
                    v-if="activeTab === 'profile'"
                    :form="profileForm"
                    :user="profile"
                    :account-meta="accountMeta"
                    :errors="profileErrors"
                    :saving="savingProfile"
                    @update:avatar="profileForm.avatar = $event"
                    @update:full-name="profileForm.full_name = $event"
                    @update:email="profileForm.email = $event"
                    @update:phone="profileForm.phone = $event"
                    @update:username="profileForm.username = $event"
                    @submit="saveProfile"
                />

                <TabChangePass
                    v-else-if="activeTab === 'password'"
                    :form="passwordForm"
                    :errors="passwordErrors"
                    :saving="savingPassword"
                    :logging-out-devices="loggingOutDevices"
                    @update:current-password="passwordForm.current_password = $event"
                    @update:new-password="passwordForm.new_password = $event"
                    @update:new-password-confirmation="passwordForm.new_password_confirmation = $event"
                    @submit="updatePassword"
                    @logout-all-devices="logoutOtherDevices"
                />

                <TabUserLog
                    v-else-if="activeTab === 'user-log'"
                    :filters="userLogFilters"
                    :logs="userLogs"
                    :loading="loadingUserLogs"
                    :meta="userLogsMeta"
                    @update:search="userLogFilters.search = $event"
                    @update:action="userLogFilters.action = $event"
                    @change-page="loadUserLogs"
                />

                <TabWalletTransaction
                    v-else
                    :filters="walletLogFilters"
                    :transactions="walletTransactions"
                    :loading="loadingWalletTransactions"
                    :meta="walletTransactionsMeta"
                    @update:search="walletLogFilters.search = $event"
                    @update:type="walletLogFilters.type = $event"
                    @change-page="loadWalletTransactions"
                />
            </div>
        </section>
    </div>
</template>
