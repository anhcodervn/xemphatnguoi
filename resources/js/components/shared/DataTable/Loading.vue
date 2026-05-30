<template>
    <div
        v-if="props.loading"
        class="absolute inset-0 z-10 flex items-center justify-center
               bg-white/60 backdrop-blur-sm rounded"
    >
        <div class="flex flex-col items-center gap-2">
            <!-- spinner -->
            <div
                class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"
            ></div>

            <span class="text-sm text-gray-600">Đang tải dữ liệu...</span>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    loading: boolean
}>();

</script>

<style scoped>

</style>