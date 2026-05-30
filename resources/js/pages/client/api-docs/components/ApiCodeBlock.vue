<script setup lang="ts">
import { Check, Copy } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    title: string;
    description: string;
    tabs: Record<string, string>;
    copiedKey: string | null;
    copyKeyPrefix: string;
}>();

const emit = defineEmits<{
    copy: [value: string, key: string];
}>();

const selectedTab = ref('curl');

const labels: Record<string, string> = {
    curl: 'cURL',
    javascript: 'JavaScript',
    php: 'PHP',
};

const normalizedTabs = computed(() =>
    Object.entries(props.tabs).map(([key, value]) => ({
        key,
        label: labels[key] ?? key,
        value,
    })),
);

const activeSnippet = computed(() => normalizedTabs.value.find((tab) => tab.key === selectedTab.value)?.value ?? '');
const snippetLines = computed(() => activeSnippet.value.split('\n'));
</script>

<template>
    <section class="rounded-[10px] border border-slate-200/80 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-[-0.03em] text-slate-950">{{ title }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ description }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in normalizedTabs"
                        :key="tab.key"
                        type="button"
                        class="rounded-[10px] border px-3 py-1.5 text-xs font-semibold transition"
                        :class="
                            selectedTab === tab.key
                                ? 'border-indigo-600 bg-indigo-50 text-indigo-600'
                                : 'border-transparent bg-transparent text-slate-500 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900'
                        "
                        @click="selectedTab = tab.key"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-[10px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    @click="emit('copy', activeSnippet, `${copyKeyPrefix}-${selectedTab}`)"
                >
                    <Check v-if="copiedKey === `${copyKeyPrefix}-${selectedTab}`" class="h-3.5 w-3.5 text-emerald-600" />
                    <Copy v-else class="h-3.5 w-3.5" />
                    {{ copiedKey === `${copyKeyPrefix}-${selectedTab}` ? 'Copied' : 'Copy' }}
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-b-[10px] border-t border-slate-100 bg-[#0b1220]">
            <div class="flex overflow-x-auto font-mono text-[12px] leading-6 text-slate-200">
                <div class="select-none border-r border-white/10 bg-[#09101d] px-3 py-4 text-right text-slate-500">
                    <div v-for="(line, index) in snippetLines" :key="`${selectedTab}-line-${index}`" class="h-6">
                        {{ index + 1 }}
                    </div>
                </div>
                <pre class="min-w-0 flex-1 px-4 py-4"><code><template v-for="(line, index) in snippetLines" :key="`${selectedTab}-code-${index}`">{{ line }}<br v-if="index < snippetLines.length - 1" /></template></code></pre>
            </div>
        </div>
    </section>
</template>
