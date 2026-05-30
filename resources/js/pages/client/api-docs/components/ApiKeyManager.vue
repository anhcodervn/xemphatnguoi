<script setup lang="ts">
import type { ApiKeyPermissionType, ClientApiKeyType } from '@/types/api-key.type';
import { Check, Copy, KeyRound, RefreshCw, Sparkles } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    permissions: ApiKeyPermissionType[];
    apiKeys: ClientApiKeyType[];
    loading: boolean;
    creating: boolean;
    generatedSecret: { api_key: string; api_secret: string; name: string } | null;
    copiedKey: string | null;
}>();

const emit = defineEmits<{
    create: [payload: { name: string; permissions: string[] }];
    refresh: [];
    copy: [value: string, key: string];
}>();

const formName = ref('');
const selectedPermissions = ref<string[]>([]);

watch(
    () => props.permissions,
    (permissions) => {
        if (selectedPermissions.value.length > 0 || permissions.length === 0) {
            return;
        }

        selectedPermissions.value = permissions.map((permission) => permission.key);
    },
    { immediate: true },
);

watch(
    () => props.generatedSecret,
    (value) => {
        if (! value) {
            return;
        }

        formName.value = '';
    },
);

const groupedPermissions = computed(() => {
    const map = new Map<string, ApiKeyPermissionType[]>();

    props.permissions.forEach((permission) => {
        const group = permission.group;
        map.set(group, [...(map.get(group) ?? []), permission]);
    });

    return Array.from(map.entries()).map(([group, permissions]) => ({
        group,
        permissions,
    }));
});

const canSubmit = computed(() => formName.value.trim() !== '' && selectedPermissions.value.length > 0 && ! props.creating);

const selectedPermissionLabels = computed(() =>
    props.permissions
        .filter((permission) => selectedPermissions.value.includes(permission.key))
        .map((permission) => permission.label),
);

const togglePermission = (key: string): void => {
    if (selectedPermissions.value.includes(key)) {
        if (selectedPermissions.value.length === 1) {
            return;
        }

        selectedPermissions.value = selectedPermissions.value.filter((permission) => permission !== key);
        return;
    }

    selectedPermissions.value = [...selectedPermissions.value, key];
};

const submit = (): void => {
    if (! canSubmit.value) {
        return;
    }

    emit('create', {
        name: formName.value.trim(),
        permissions: selectedPermissions.value,
    });
};

const statusClass = (status: ClientApiKeyType['status']): string => {
    switch (status) {
        case 'active':
            return 'bg-emerald-100 text-emerald-700';
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

const formatDateTime = (value: string | null): string => {
    if (! value) {
        return 'Chưa có';
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
    if (value.length <= 16) {
        return value;
    }

    return `${value.slice(0, 8)}...${value.slice(-6)}`;
};
</script>

<template>
    <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm md:p-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Access Key</p>
                <h2 class="mt-1 text-lg font-bold tracking-[-0.03em] text-slate-950">Tạo API key</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Tạo một lần để dùng cho toàn bộ endpoint trên trang này. Chọn các chức năng mà key được phép gọi.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white"
                :disabled="loading"
                @click="$emit('refresh')"
            >
                <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
                Làm mới
            </button>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                    <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Tên API key</label>
                    <input
                        v-model="formName"
                        type="text"
                        placeholder="Ví dụ: Website chính"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                    />

                    <div class="mt-4">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Khả năng API</p>
                            <span class="rounded-full bg-slate-900 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-white">
                                {{ selectedPermissions.length }} đã chọn
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div v-for="item in groupedPermissions" :key="item.group" class="space-y-2">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ item.group }}</p>

                                <label
                                    v-for="permission in item.permissions"
                                    :key="permission.key"
                                    class="flex cursor-pointer gap-3 rounded-2xl border border-slate-200 bg-white px-3.5 py-3 transition hover:border-slate-300"
                                >
                                    <input
                                        type="checkbox"
                                        class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        :checked="selectedPermissions.includes(permission.key)"
                                        @change="togglePermission(permission.key)"
                                    />

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-semibold text-slate-950">{{ permission.label }}</span>
                                        </div>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ permission.description }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <code
                                                v-for="endpoint in permission.endpoints"
                                                :key="endpoint"
                                                class="rounded-lg bg-slate-950 px-2.5 py-1 text-[11px] text-slate-100"
                                            >
                                                {{ endpoint }}
                                            </code>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedPermissionLabels.length > 0" class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="label in selectedPermissionLabels"
                            :key="label"
                            class="rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700"
                        >
                            {{ label }}
                        </span>
                    </div>

                    <button
                        type="button"
                        class="mt-4 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="!canSubmit"
                        @click="submit"
                    >
                        <Sparkles class="h-4 w-4" />
                        {{ creating ? 'Đang tạo...' : 'Tạo API key' }}
                    </button>
                </div>

                <div
                    v-if="generatedSecret"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <KeyRound class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-emerald-800">Tạo thành công</p>
                            <p class="text-xs text-emerald-700">Lưu lại key và secret này để dùng cho website hoặc hệ thống của bạn.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-xl border border-emerald-200 bg-white px-3 py-3">
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

                        <div class="rounded-xl border border-emerald-200 bg-white px-3 py-3">
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
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Issued Keys</p>
                        <h3 class="mt-1 text-base font-bold tracking-[-0.02em] text-slate-950">Key đã tạo</h3>
                    </div>
                    <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">{{ apiKeys.length }}</span>
                </div>

                <div v-if="loading" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    Đang tải danh sách API key...
                </div>

                <div v-else-if="apiKeys.length === 0" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                    Chưa có API key nào được tạo.
                </div>

                <div v-else class="mt-4 space-y-3">
                    <article v-for="apiKey in apiKeys" :key="apiKey.id" class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-slate-950">{{ apiKey.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ maskedApiKey(apiKey.api_key) }}</p>
                            </div>

                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize" :class="statusClass(apiKey.status)">
                                {{ apiKey.status }}
                            </span>
                        </div>

                        <div class="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                            <div>
                                <span class="font-semibold text-slate-700">Khả năng:</span>
                                {{ apiKey.permissions.length }}
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Logs:</span>
                                {{ apiKey.logs_count ?? 0 }}
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Dùng lần cuối:</span>
                                {{ formatDateTime(apiKey.last_used_at) }}
                            </div>
                            <div>
                                <span class="font-semibold text-slate-700">Tạo lúc:</span>
                                {{ formatDateTime(apiKey.created_at) }}
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="permission in apiKey.permission_details"
                                :key="permission.key"
                                class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-600"
                            >
                                {{ permission.label }}
                            </span>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
