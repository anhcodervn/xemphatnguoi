<script setup lang="ts">
import { ref } from 'vue';
import Footer from './client/Footer.vue';
import Header from './client/Header.vue';
import Sidebar from './client/Sidebar.vue';

const isSidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-[radial-gradient(circle_at_top_right,_#e0f2fe_0%,_#f5f8fc_28%,_#f8fafc_72%)] text-slate-800">
        <div class="flex min-h-screen overflow-x-hidden">
            <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

            <div class="flex min-h-screen min-w-0 flex-1 flex-col overflow-x-hidden">
                <Header @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

                <main class="mx-auto w-full min-w-0 max-w-[1200px] flex-1 px-4 pb-8 pt-[8.5rem] sm:px-6">
                    <router-view />
                </main>

                <Footer />
            </div>
        </div>
    </div>
</template>
