<script setup lang="ts">
defineProps<{ title: string; description: string }>();
</script>
<template>
    <div class="mx-auto max-w-3xl">
        <header>
            <p class="text-sm font-bold text-sky-700">Module chuẩn bị</p>
            <h1 class="mt-1 text-3xl font-black text-slate-950">{{ title }}</h1>
        </header>
        <section class="mt-7 rounded-xl border border-slate-200 bg-white p-7">
            <h2 class="text-lg font-bold text-slate-950">Chưa kích hoạt trong phase này</h2>
            <p class="mt-3 text-sm leading-7 text-slate-600">{{ description }}</p>
            <p class="mt-4 text-xs text-slate-500">Trang được khai báo rõ ràng để điều hướng admin không dẫn tới 404 và không hiển thị metric giả.</p>
        </section>
    </div>
</template>
