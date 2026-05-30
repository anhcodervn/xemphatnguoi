<script setup lang="ts">
import { Check, ChevronDown, ClipboardCopy } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type EndpointParam = {
    name: string;
    type: string;
    required: boolean;
    description: string;
};

type EndpointItem = {
    id: string;
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    path: string;
    title: string;
    description: string;
    tags: string[];
    params: EndpointParam[];
    requestExample: string;
    responseExample: string;
    statusLabel?: string;
};

const props = defineProps<{
    endpoint: EndpointItem;
    copiedKey: string | null;
}>();

const emit = defineEmits<{
    copy: [value: string, key: string];
}>();

const isOpen = ref(false);

const methodClass = computed(() => {
    switch (props.endpoint.method) {
        case 'GET':
            return 'bg-emerald-50 text-emerald-700';
        case 'POST':
            return 'bg-blue-50 text-blue-700';
        case 'PUT':
        case 'PATCH':
            return 'bg-amber-50 text-amber-700';
        case 'DELETE':
            return 'bg-rose-50 text-rose-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
});
</script>

<template>
    <article class="overflow-hidden rounded-[10px] border border-slate-200 bg-white shadow-sm">
        <button type="button" class="flex w-full items-start justify-between gap-3 px-4 py-3.5 text-left" @click="isOpen = !isOpen">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em]" :class="methodClass">
                        {{ endpoint.method }}
                    </span>
                    <code class="rounded-[8px] bg-slate-50 px-2.5 py-1 text-sm font-semibold text-slate-950">{{ endpoint.path }}</code>
                    <span
                        v-for="tag in endpoint.tags"
                        :key="tag"
                        class="rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-indigo-500"
                    >
                        {{ tag }}
                    </span>
                </div>

                <div class="mt-2 space-y-1">
                    <p class="text-sm font-semibold text-slate-950">{{ endpoint.title }}</p>
                    <p class="text-sm leading-6 text-slate-500">{{ endpoint.description }}</p>
                </div>

                <p class="mt-3 text-xs font-medium text-slate-500">
                    {{ endpoint.statusLabel ?? 'Xác thực bắt buộc' }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                    @click.stop="emit('copy', endpoint.requestExample, `endpoint-${endpoint.id}`)"
                >
                    <Check v-if="copiedKey === `endpoint-${endpoint.id}`" class="h-3.5 w-3.5 text-emerald-600" />
                    <ClipboardCopy v-else class="h-3.5 w-3.5" />
                    {{ copiedKey === `endpoint-${endpoint.id}` ? 'Copied' : 'Copy cURL' }}
                </button>

                <ChevronDown class="h-4 w-4 text-slate-500 transition" :class="isOpen ? 'rotate-180' : ''" />
            </div>
        </button>

        <div v-if="isOpen" class="border-t border-slate-200 bg-slate-50/40 px-4 py-4">
            <div class="space-y-4">
                <div v-if="endpoint.params.length > 0" class="rounded-[8px] border border-slate-200 bg-white p-3">
                    <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Tham số</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="param in endpoint.params" :key="param.name" class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <code class="text-xs font-semibold text-slate-900">{{ param.name }}</code>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-500">
                                    {{ param.type }}
                                </span>
                                <span
                                    v-if="param.required"
                                    class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-rose-600"
                                >
                                    bắt buộc
                                </span>
                            </div>
                            <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ param.description }}</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-3 py-2">
                        <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Mẫu cURL</h3>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-[8px] border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100"
                            @click="emit('copy', endpoint.requestExample, `curl-${endpoint.id}`)"
                        >
                            <Check v-if="copiedKey === `curl-${endpoint.id}`" class="h-3.5 w-3.5 text-emerald-600" />
                            <ClipboardCopy v-else class="h-3.5 w-3.5" />
                            {{ copiedKey === `curl-${endpoint.id}` ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                    <pre class="overflow-x-auto bg-[#020617] px-4 py-4 text-[12px] leading-6 text-slate-200"><code>{{ endpoint.requestExample }}</code></pre>
                </div>

                <div class="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                        Response
                    </div>
                    <pre class="overflow-x-auto bg-[#020617] px-4 py-4 text-[12px] leading-6 text-slate-200"><code>{{ endpoint.responseExample }}</code></pre>
                </div>
            </div>
        </div>
    </article>
</template>
