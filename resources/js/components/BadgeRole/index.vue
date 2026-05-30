<template>
    <span
        :class="[
            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize',
            badgeClass
        ]"
    >
        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="dotClass"></span>
        {{ role }}
        <span v-if="text">: {{ text }}</span>
    </span>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
    role: string;
    text?: string;
}>();

const role = computed(() => props.role?.toLowerCase());

// màu theo role
const badgeClass = computed(() => {
    switch (role.value) {
        case "admin":
            return "bg-red-100 text-red-600";
        case "user":
            return "bg-blue-100 text-blue-600";
        case "editor":
            return "bg-purple-100 text-purple-600";
        default:
            return "bg-gray-100 text-gray-600";
    }
});

const dotClass = computed(() => {
    switch (role.value) {
        case "admin":
            return "bg-red-500";
        case "user":
            return "bg-blue-500";
        case "editor":
            return "bg-purple-500";
        default:
            return "bg-gray-400";
    }
});
</script>