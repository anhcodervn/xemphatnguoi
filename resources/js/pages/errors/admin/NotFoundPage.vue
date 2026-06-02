<script setup lang="ts">
import ErrorStateCard from '../shared/ErrorStateCard.vue';
</script>

<template>
    <div class="mx-auto grid min-h-[calc(100vh-10rem)] w-full max-w-6xl place-items-center">
        <ErrorStateCard
            code="404"
            eyebrow="Admin Panel"
            title="Không tìm thấy trang quản trị"
            description="Đường dẫn quản trị bạn vừa truy cập không tồn tại hoặc đã được chuyển sang vị trí khác trong hệ thống."
            :primary-action="{ href: '/admin', label: 'Về dashboard admin' }"
            :secondary-action="{ href: '/admin/settings/general', label: 'Mở cấu hình hệ thống' }"
            theme="admin"
        />
    </div>
</template>
