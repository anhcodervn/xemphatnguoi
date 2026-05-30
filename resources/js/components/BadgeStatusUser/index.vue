<template>
    <span
        :class="[
            'px-2 py-1 rounded text-xs font-medium',
            badgeClass.class
        ]"
    >
        {{ badgeClass.text }}
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    status: number;
}>();

const status = computed(() => props.status);

// màu theo role
const badgeClass = computed(() => {
    switch (status.value) {
        case -1:
            return {
                text: "Bị khóa",
                class: "bg-red-100 text-red-600"
            };
        case 0:
            return {
                text: "Chưa kích hoạt",
                class: "bg-gray-500 text-white"
            };
        case 1:
            return {
                text: "Đã kích hoạt",
                class: "bg-green-100 text-green-600"
            };
        default:
            return {
                text: `Không rõ trạng thái (${status.value})`,
                class: "bg-gray-500 text-white"
            };
    }
});


</script>