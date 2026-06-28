<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4" @click.self="close">
                <Transition name="scale">
                    <div
                        v-if="modelValue"
                        class="relative flex max-h-[calc(100vh-2rem)] w-full flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                        :class="panelClass ?? 'max-w-md'"
                    >
                        <div v-if="$slots.header" class="shrink-0 text-lg font-semibold">
                            <slot name="header" />
                        </div>

                        <button class="absolute right-3 top-3 text-gray-500 hover:text-black" :class="iconClass" @click="close">✕</button>

                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <slot />
                        </div>

                        <div v-if="$slots.footer" class="flex shrink-0 justify-end gap-2">
                            <slot name="footer" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';

defineProps<{
    modelValue: boolean;
    iconClass?: string;
    panelClass?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const close = () => {
    emit('update:modelValue', false);
};

const handleEsc = (e: KeyboardEvent) => {
    if (e.key === 'Escape') {
        close();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleEsc);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleEsc);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.scale-enter-active {
    transition: all 0.2s ease;
}

.scale-enter-from {
    transform: scale(0.96);
    opacity: 0;
}

.scale-leave-active {
    transition: all 0.15s ease;
}

.scale-leave-to {
    transform: scale(0.96);
    opacity: 0;
}
</style>
