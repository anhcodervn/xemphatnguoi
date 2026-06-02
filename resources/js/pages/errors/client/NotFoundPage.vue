<script setup lang="ts">
import ErrorStateCard from '../shared/ErrorStateCard.vue';
</script>

<template>
    <div class="mx-auto grid min-h-[calc(100vh-9rem)] w-full max-w-6xl place-items-center">
        <ErrorStateCard
            code="404"
            eyebrow="Client Panel"
            title="Không tìm thấy trang trong khu vực khách hàng"
            description="Trang bạn cần có thể đã được đổi đường dẫn, bị gỡ khỏi menu hoặc URL hiện tại không còn hợp lệ."
            :primary-action="{ href: '/', label: 'Về tổng quan' }"
            :secondary-action="{ href: '/contact', label: 'Liên hệ & góp ý' }"
            theme="client"
        />
    </div>
</template>
