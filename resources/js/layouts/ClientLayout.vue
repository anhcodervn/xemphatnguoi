<script setup lang="ts">
import { ref } from 'vue';
import Footer from './client/Footer.vue';
import Header from './client/Header.vue';
import Sidebar from './client/Sidebar.vue';

const isSidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_#f8fbff_0%,_#eef4f8_42%,_#e6edf3_100%)] text-slate-800">
        <div class="flex min-h-screen">
            <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

            <div class="flex min-h-screen min-w-0 flex-1 flex-col">
                <Header @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

                <main class="mx-auto w-full max-w-[1200px] px-3 pb-6 pt-[6rem]">
                    <router-view />
                </main>

                <Footer />
            </div>
        </div>
    </div>
</template>
