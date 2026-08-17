<script setup lang="ts">
import type { ApiKeyPermissionType, ClientApiKeyType } from '@/types/api-key.type';
import type { ClientProfileType } from '@/types/client-profile.type';
import { Check, Copy, KeyRound, PencilLine, RefreshCw, Save, Sparkles, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    profile: ClientProfileType | null;
    permissions: ApiKeyPermissionType[];
    apiKeys: ClientApiKeyType[];
    loading: boolean;
    creating: boolean;
    updatingApiKeyId: number | null;
    copiedKey: string | null;
    generatedSecret: { api_key: string; api_secret: string; name: string } | null;
    formName: string;
    formIpWhitelist: string;
}>();

const emit = defineEmits<{
    updateName: [value: string];
    updateIpWhitelist: [value: string];
    create: [];
    refresh: [];
    updateIpList: [apiKeyId: number, value: string];
    rotate: [apiKeyId: number];
    copy: [value: string, key: string];
}>();

const editingApiKeyId = ref<number | null>(null);
const ipWhitelistDrafts = ref<Record<number, string>>({});

watch(
    () => props.apiKeys,
    (apiKeys) => {
        ipWhitelistDrafts.value = apiKeys.reduce<Record<number, string>>((carry, apiKey) => {
            carry[apiKey.id] = apiKey.ip_whitelist.join('\n');

            return carry;
        }, {});
    },
    { immediate: true },
);

const statusClass = (status: ClientApiKeyType['status']): string => {
    switch (status) {
        case 'active':
            return 'bg-blue-100 text-blue-700';
        case 'inactive':
            return 'bg-slate-100 text-slate-700';
        case 'expired':
            return 'bg-amber-100 text-amber-700';
        case 'revoked':
            return 'bg-rose-100 text-rose-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

const keyTypeLabel = (): string => 'Key ví';

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return '--';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('vi-VN', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(date);
};

const maskedApiKey = (value: string): string => {
    if (value.length <= 18) {
        return value;
    }

    return `${value.slice(0, 8)}...${value.slice(-6)}`;
};

const startEditing = (apiKey: ClientApiKeyType): void => {
    editingApiKeyId.value = apiKey.id;
    ipWhitelistDrafts.value[apiKey.id] = apiKey.ip_whitelist.join('\n');
};

const cancelEditing = (apiKey: ClientApiKeyType): void => {
    ipWhitelistDrafts.value[apiKey.id] = apiKey.ip_whitelist.join('\n');

    if (editingApiKeyId.value === apiKey.id) {
        editingApiKeyId.value = null;
    }
};

const saveIpWhitelist = (apiKey: ClientApiKeyType): void => {
    emit('updateIpList', apiKey.id, ipWhitelistDrafts.value[apiKey.id] ?? '');
    editingApiKeyId.value = null;
};
</script>

<template>
    <div class="space-y-3">
        <section class="rounded-[10px] border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">API Access</p>
                    <h2 class="mt-1 text-lg font-bold tracking-[-0.03em] text-slate-950">Tạo API key ví</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ profile?.api_access?.message ?? 'Tạo key ví để gọi API và trừ trực tiếp số dư tài khoản.' }}
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-[10px] border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-white"
                    :disabled="loading"
                    @click="$emit('refresh')"
                >
                    <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
                    Làm mới
                </button>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <div class="rounded-[10px] border border-blue-100 bg-blue-50/60 p-4">
                    <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Tên API key</label>
                    <input
                        :value="formName"
                        type="text"
                        placeholder="Ví dụ: Wallet API key"
                        class="mt-2 w-full rounded-[10px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                        @input="$emit('updateName', ($event.target as HTMLInputElement).value)"
                    />

                    <div class="mt-4">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">IP được phép sử dụng</label>
                        <textarea
                            :value="formIpWhitelist"
                            rows="5"
                            placeholder="1 dòng 1 IP&#10;103.10.10.1&#10;103.10.10.2&#10;*"
                            class="mt-2 w-full rounded-[10px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                            @input="$emit('updateIpWhitelist', ($event.target as HTMLTextAreaElement).value)"
                        />
                        <p class="mt-2 text-xs leading-5 text-slate-500">Mỗi dòng là một IP. Nhập <code>*</code> để cho phép tất cả IP truy cập.</p>
                    </div>

                    <div class="mt-4 rounded-[10px] border border-slate-200 bg-white p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Quyền mặc định</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="permission in permissions"
                                :key="permission.key"
                                class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700"
                            >
                                {{ permission.label }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">API key dùng để gọi API V1 và trừ trực tiếp vào số dư ví.</p>
                    </div>

                    <button
                        type="button"
                        class="mt-4 inline-flex items-center justify-center gap-2 rounded-[10px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="creating || !formName.trim() || permissions.length === 0 || apiKeys.length > 0"
                        @click="$emit('create')"
                    >
                        <Sparkles class="h-4 w-4" />
                        {{ creating ? 'Đang tạo...' : apiKeys.length > 0 ? 'Tài khoản đã có API key' : 'Tạo API key ví' }}
                    </button>
                </div>

                <div class="space-y-3">
                    <div v-if="generatedSecret" class="rounded-[10px] border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm">
                        <div class="flex items-center gap-2">
                            <div class="flex h-9 w-9 items-center justify-center rounded-[10px] bg-emerald-100 text-emerald-700">
                                <KeyRound class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-800">Tạo thành công</p>
                                <p class="text-xs text-emerald-700">Secret chỉ hiển thị một lần, hãy lưu lại ngay.</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3">
                            <div class="rounded-[10px] border border-emerald-200 bg-white px-3 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">API Key</p>
                                        <p class="mt-1 break-all text-sm font-semibold text-slate-950">{{ generatedSecret.api_key }}</p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700"
                                        @click="$emit('copy', generatedSecret.api_key, 'generated-api-key')"
                                    >
                                        <Check v-if="copiedKey === 'generated-api-key'" class="h-3.5 w-3.5 text-emerald-600" />
                                        <Copy v-else class="h-3.5 w-3.5" />
                                        {{ copiedKey === 'generated-api-key' ? 'Copied' : 'Copy' }}
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[10px] border border-emerald-200 bg-white px-3 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">API Secret</p>
                                        <p class="mt-1 break-all text-sm font-semibold text-slate-950">{{ generatedSecret.api_secret }}</p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-700"
                                        @click="$emit('copy', generatedSecret.api_secret, 'generated-api-secret')"
                                    >
                                        <Check v-if="copiedKey === 'generated-api-secret'" class="h-3.5 w-3.5 text-emerald-600" />
                                        <Copy v-else class="h-3.5 w-3.5" />
                                        {{ copiedKey === 'generated-api-secret' ? 'Copied' : 'Copy' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[10px] border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Issued keys</p>
                                <h3 class="mt-1 text-base font-bold tracking-[-0.02em] text-slate-950">Danh sách API key</h3>
                            </div>
                            <span class="rounded-full bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white">{{ apiKeys.length }}</span>
                        </div>

                        <div
                            v-if="loading"
                            class="mt-4 rounded-[10px] border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500"
                        >
                            Đang tải danh sách API key...
                        </div>

                        <div
                            v-else-if="apiKeys.length === 0"
                            class="mt-4 rounded-[10px] border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500"
                        >
                            Chưa có API key nào được tạo.
                        </div>

                        <div v-else class="mt-4 space-y-3">
                            <article v-for="apiKey in apiKeys" :key="apiKey.id" class="rounded-[10px] border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-slate-950">{{ apiKey.name }}</p>
                                            <span class="rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700">
                                                {{ keyTypeLabel() }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-500">{{ maskedApiKey(apiKey.api_key) }}</p>
                                    </div>

                                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize" :class="statusClass(apiKey.status)">
                                        {{ apiKey.status }}
                                    </span>
                                </div>

                                <div class="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                    <div><span class="font-semibold text-slate-700">Quyền:</span> {{ apiKey.permissions.length }}</div>
                                    <div><span class="font-semibold text-slate-700">Logs:</span> {{ apiKey.logs_count ?? 0 }}</div>
                                    <div>
                                        <span class="font-semibold text-slate-700">Dùng lần cuối:</span> {{ formatDateTime(apiKey.last_used_at) }}
                                    </div>
                                    <div><span class="font-semibold text-slate-700">Tạo lúc:</span> {{ formatDateTime(apiKey.created_at) }}</div>
                                </div>

                                <div class="mt-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">IP whitelist</p>
                                    <div v-if="editingApiKeyId !== apiKey.id" class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="ip in apiKey.ip_whitelist"
                                            :key="`${apiKey.id}-${ip}`"
                                            class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600"
                                        >
                                            {{ ip }}
                                        </span>
                                        <span
                                            v-if="apiKey.ip_whitelist.length === 0"
                                            class="rounded-full border border-blue-200 bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700"
                                        >
                                            Tất cả IP
                                        </span>
                                    </div>

                                    <div v-else class="mt-2 space-y-2">
                                        <textarea
                                            v-model="ipWhitelistDrafts[apiKey.id]"
                                            rows="4"
                                            placeholder="1 dòng 1 IP&#10;103.10.10.1&#10;103.10.10.2&#10;*"
                                            class="w-full rounded-[10px] border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
                                        />
                                        <p class="text-xs leading-5 text-slate-500">Để trống hoặc nhập <code>*</code> nếu muốn cho phép tất cả IP.</p>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap justify-end gap-2">
                                    <button
                                        v-if="editingApiKeyId !== apiKey.id"
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        @click="startEditing(apiKey)"
                                    >
                                        <PencilLine class="h-3.5 w-3.5" />
                                        Sửa IP
                                    </button>

                                    <template v-if="editingApiKeyId === apiKey.id">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            :disabled="updatingApiKeyId === apiKey.id"
                                            @click="cancelEditing(apiKey)"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                            Hủy
                                        </button>

                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-[10px] bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="updatingApiKeyId === apiKey.id"
                                            @click="saveIpWhitelist(apiKey)"
                                        >
                                            <Save class="h-3.5 w-3.5" />
                                            {{ updatingApiKeyId === apiKey.id ? 'Đang lưu...' : 'Lưu IP' }}
                                        </button>
                                    </template>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-[10px] border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-white"
                                        @click="$emit('rotate', apiKey.id)"
                                    >
                                        <RefreshCw class="h-3.5 w-3.5" />
                                        Đổi secret
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
