<script setup lang="ts">
import type { DepositTabKey } from '../types';

const props = defineProps<{
    modelValue: DepositTabKey;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: DepositTabKey];
}>();

const tabs: Array<{ key: DepositTabKey; label: string }> = [
    { key: 'deposit', label: 'Nạp tiền' },
    { key: 'history', label: 'Lịch sử nạp tiền' },
];
</script>

<template>
    <div class="border-b border-slate-200 px-3 sm:px-4 lg:px-5">
        <div class="flex gap-5 overflow-x-auto sm:gap-6">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="relative whitespace-nowrap px-1 py-3 text-sm font-semibold transition sm:py-4 sm:text-[15px]"
                :class="props.modelValue === tab.key ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                @click="emit('update:modelValue', tab.key)"
            >
                {{ tab.label }}
                <span
                    v-if="props.modelValue === tab.key"
                    class="absolute inset-x-0 bottom-0 h-0.5 rounded-full bg-indigo-500"
                />
            </button>
        </div>
    </div>
</template>
