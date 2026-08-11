<script setup lang="ts">
import FloatingSupportButton from '@/components/support/FloatingSupportButton.vue';
import { useSupportStore } from '@/stores/support.store';
import { useUserStore } from '@/stores/user.store';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import Footer from './client/Footer.vue';
import Header from './client/Header.vue';
import Sidebar from './client/Sidebar.vue';

const isSidebarOpen = ref(false);
const route = useRoute();
const userStore = useUserStore();
const supportStore = useSupportStore();
const isSupportRoute = computed(() => route.path === '/support');

watch(
    () => userStore.user?.id ?? null,
    (userId) => {
        if (userId !== null) {
            void supportStore.start('client', userId);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (supportStore.context === 'client') {
        supportStore.stop();
    }
});
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top_right,_#e0f2fe_0%,_#f5f8fc_28%,_#f8fafc_72%)] text-slate-800">
        <div class="flex min-h-screen overflow-x-hidden">
            <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

            <div class="flex min-h-screen min-w-0 flex-1 flex-col overflow-x-hidden">
                <Header @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

                <main
                    class="mx-auto w-full min-w-0 flex-1 px-4 pt-[8.5rem] sm:px-6"
                    :class="isSupportRoute ? 'max-w-[1440px] pb-4' : 'max-w-[1200px] pb-8'"
                >
                    <router-view />
                </main>

                <Footer v-if="!isSupportRoute" />
                <FloatingSupportButton v-if="!isSupportRoute" />
            </div>
        </div>
    </div>
</template>
