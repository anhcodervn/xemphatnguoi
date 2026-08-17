<script setup lang="ts">
import { Eye } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
</script>

<template>
    <div class="mx-auto max-w-3xl">
        <header>
            <p class="text-sm font-bold text-sky-700">Monitoring</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-950">Theo dõi biển số</h1>
        </header>
        <section class="mt-7 rounded-xl border border-slate-200 bg-white p-7 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-50 text-sky-700"><Eye class="h-6 w-6" /></span>
            <h2 class="mt-4 text-xl font-bold text-slate-950">Kiến trúc đã sẵn sàng</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-slate-600">
                Schema monitoring đã được chuẩn bị, nhưng scheduler và thông báo định kỳ chưa được kích hoạt trong phase này. Không có số liệu theo
                dõi giả.
            </p>
            <RouterLink
                to="/dashboard/vehicles"
                class="app-focus mt-6 inline-flex min-h-11 items-center rounded-lg bg-slate-950 px-5 text-sm font-bold text-white"
                >Quản lý xe của tôi</RouterLink
            >
        </section>
    </div>
</template>
