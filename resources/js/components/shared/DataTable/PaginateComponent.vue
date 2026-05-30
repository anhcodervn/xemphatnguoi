<template>
    <div
        class="py-3 border-t-1 border-gray-300 flex gap-2 
        flex-wrap items-center justify-between"
    >
        <div >
            hehe
        </div>
        <div class="flex gap-2 flex-wrap items-center">
            <!-- PREV -->
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="px-2 py-2 border border-gray-300 rounded cursor-pointer hover:outline-blue-500 hover:bg-blue-500 hover:text-white"
            >
                <ChevronLeft class="w-3 h-3" />
            </button>

            <!-- FIRST -->
            <button
                v-if="pages[0] !== 1"
                @click="goToPage(1)"
                class="px-3 py-1 text-[14px] border border-gray-300 rounded cursor-pointer hover:outline-blue-500 hover:bg-blue-500 hover:text-white"
            >
                1
            </button>

            <!-- LEFT ... -->
            <span v-if="pages[0] > 2" class="px-2">...</span>

            <!-- MAIN -->
            <button
                v-for="p in pages"
                :key="p"
                @click="goToPage(p)"
                class="px-3 py-1 rounded border text-[14px] border-gray-300 cursor-pointer"
                :class="
                    p === currentPage
                        ? 'bg-blue-500 text-white'
                        : 'hover:bg-gray-100'
                "
            >
                {{ p }}
            </button>

            <!-- RIGHT ... -->
            <span v-if="pages[pages.length - 1] < totalPages - 1" class="px-2">
                ...
            </span>

            <!-- LAST -->
            <button
                v-if="pages[pages.length - 1] !== totalPages"
                @click="goToPage(totalPages)"
                class="px-3 py-1 text-[14px] border border-gray-300 rounded cursor-pointer hover:outline-blue-500 hover:bg-blue-500 hover:text-white"
            >
                {{ totalPages }}
            </button>

            <!-- NEXT -->
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-2 py-2 border border-gray-300 rounded cursor-pointer hover:outline-blue-500 hover:bg-blue-500 hover:text-white"
            >
                <ChevronRight class="w-3 h-3" />
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const props = defineProps<{
    currentPage: number;
    totalPages: number;
    goToPage: (p: number) => void;
}>();

console.log({
    currentpage: props.currentPage,
    totalPage: props.totalPages,
});

// tạo list page hiển thị
const pages = computed(() => {
    const delta = 2; // số page mỗi bên
    const range = [];

    const start = Math.max(1, props.currentPage - delta);
    const end = Math.min(props.totalPages, props.currentPage + delta);

    for (let i = start; i <= end; i++) {
        range.push(i);
    }

    return range;
});
</script>
