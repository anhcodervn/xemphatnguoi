<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import Header from './admin/header/index.vue';
import { resolveAdminPageTitle } from './admin/sidebar/navigation';
import Sidebar from './admin/sidebar/index.vue';

const route = useRoute();
const isSidebarOpen = ref(false);

const currentPageTitle = computed(() => {
    return resolveAdminPageTitle(route.path);
});

const syncSidebarWithViewport = (): void => {
    isSidebarOpen.value = window.innerWidth >= 1024;
};

onMounted(() => {
    syncSidebarWithViewport();
    window.addEventListener('resize', syncSidebarWithViewport);
});

onUnmounted(() => {
    window.removeEventListener('resize', syncSidebarWithViewport);
});
</script>

<template>
    <div class="min-h-screen bg-[#f1f5f9] text-slate-800">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isSidebarOpen" class="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" @click="isSidebarOpen = false" />
        </Transition>

        <div class="flex min-h-screen">
            <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

            <div class="min-w-0 flex-1">
                <Header :page-title="currentPageTitle" @open-sidebar="isSidebarOpen = true" />

                <main class="px-4 pb-8 pt-6 sm:px-6 xl:px-8">
                    <div class="min-w-0">
                        <router-view />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
