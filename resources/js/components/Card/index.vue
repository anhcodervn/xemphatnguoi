<template>
    <div
        class="w-full rounded bg-white shadow-lg overflow-hidden flex flex-col"
        :class="[
            customClass
        ]"
    >
        <!-- HEADER -->
        <div v-if="$slots.header">
            <slot name="header" />
        </div>

        <!-- BODY -->
        <div>
            <slot name="body" />
        </div>

        <!-- FOOTER -->
        <div v-if="$slots.footer">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    customClass?: string;
}>();
</script>
