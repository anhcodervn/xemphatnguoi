<script setup lang="ts">
type TabKey = 'deposit' | 'history';

defineProps<{
    modelValue: TabKey;
}>();

const emit = defineEmits<{
    (event: 'update:modelValue', value: TabKey): void;
}>();

const tabs: Array<{ key: TabKey; label: string }> = [
    { key: 'deposit', label: 'Nạp tiền' },
    { key: 'history', label: 'Lịch sử nạp tiền' },
];
</script>

<template>
    <div class="border-b border-slate-100 px-4 pt-2.5">
        <div class="flex overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="relative mr-2 min-w-fit rounded-t-[10px] px-3 py-2 text-sm font-semibold transition"
                :class="modelValue === tab.key ? 'text-indigo-600' : 'text-slate-500 hover:text-slate-900'"
                @click="emit('update:modelValue', tab.key)"
            >
                {{ tab.label }}
                <span class="absolute inset-x-2 bottom-0 h-0.5 rounded-full" :class="modelValue === tab.key ? 'bg-indigo-500' : 'bg-transparent'" />
            </button>
        </div>
    </div>
</template>
