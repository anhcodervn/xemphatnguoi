<template>
    <section class="rounded-[10px] border border-slate-200 bg-white p-5 shadow-[0_14px_34px_-28px_rgba(15,23,42,0.16)]">
        <div class="mx-auto max-w-xl text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                <ShieldAlert class="h-6 w-6" />
            </div>

            <h1 class="mt-4 text-2xl font-black tracking-[-0.04em] text-slate-900">
                Vui lòng đăng ký gói để truy cập vào trang này
            </h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Khu vực quản lý thẻ chỉ khả dụng khi tài khoản của bạn có gói đang hoạt động. Sau khi đăng ký hoặc gia hạn,
                bạn có thể thêm bank account, đồng bộ giao dịch và cấu hình webhook.
            </p>

            <div class="mt-5 flex flex-col justify-center gap-3 sm:flex-row">
                <RouterLink
                    :to="{ name: 'client.package' }"
                    class="inline-flex items-center justify-center rounded-[8px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                >
                    Đăng ký gói ngay
                </RouterLink>
                <RouterLink
                    :to="{ name: 'client.home' }"
                    class="inline-flex items-center justify-center rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Về trang chủ
                </RouterLink>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { ShieldAlert } from 'lucide-vue-next';
import { RouterLink } from 'vue-router';
</script>
