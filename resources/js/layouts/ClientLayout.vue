<script setup lang="ts">
import { ref } from 'vue';
import Footer from './client/Footer.vue';
import Header from './client/Header.vue';
import Sidebar from './client/Sidebar.vue';

const isSidebarOpen = ref(false);
</script>

<template>
    <div
        class="min-h-screen overflow-x-hidden text-slate-800"
        style="
            background:
                radial-gradient(circle at top right, rgba(6, 182, 212, 0.12), transparent 30%),
                linear-gradient(180deg, #f7fbff 0%, #ffffff 100%);
        "
    >
        <div class="flex min-h-screen overflow-x-hidden">
            <Sidebar :is-open="isSidebarOpen" @close="isSidebarOpen = false" />

            <div class="flex min-h-screen min-w-0 flex-1 flex-col overflow-x-hidden">
                <Header @toggle-sidebar="isSidebarOpen = !isSidebarOpen" />

                <main class="mx-auto w-full max-w-[1240px] min-w-0 px-3 pb-8 pt-[6rem] sm:px-5">
                    <router-view />
                </main>

                <Footer />
            </div>
        </div>
    </div>
</template>
