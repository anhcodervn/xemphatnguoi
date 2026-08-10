<script setup lang="ts">
type FooterLink = {
    label: string;
    href: string;
};

const footerLinks: FooterLink[] = [
    { label: 'Điều khoản sử dụng', href: '/dieu-khoan-su-dung' },
    { label: 'Chính sách bảo mật', href: '/chinh-sach-bao-mat' },
    { label: 'Liên hệ hỗ trợ', href: '/lien-he' },
];
</script>

<template>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex w-full max-w-[1200px] flex-col gap-3 px-4 py-5 text-sm text-slate-500 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
            <p>© {{ new Date().getFullYear() }} GiaProxy.vn</p>

            <div class="flex flex-wrap gap-4">
                <a
                    v-for="link in footerLinks"
                    :key="link.href"
                    :href="link.href"
                    class="transition hover:text-slate-900"
                >
                    {{ link.label }}
                </a>
            </div>
        </div>
    </footer>
</template>
