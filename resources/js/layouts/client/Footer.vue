<script setup lang="ts">
import { useSystemSetting } from "@/composables/useSystemSetting";
import { computed, onMounted } from "vue";

type FooterLink = {
    label: string;
    href: string;
};

type FooterLinkGroup = {
    title: string;
    links: FooterLink[];
};

const { settings, fetchSettings } = useSystemSetting();

const footerLinkGroups = computed<FooterLinkGroup[]>(() => [
    {
        title: "Giới thiệu",
        links: [
            { label: "Về AutoCron", href: "/gioi-thieu" },
            { label: "Blog", href: "/blog" },
            { label: "Liên hệ", href: "/lien-he" },
            { label: "Câu hỏi thường gặp", href: "/cau-hoi-thuong-gap" },
        ],
    },
    {
        title: "Chính sách",
        links: [
            { label: "Điều khoản sử dụng", href: "/dieu-khoan-su-dung" },
            { label: "Chính sách bảo mật", href: "/chinh-sach-bao-mat" },
            { label: "Chính sách hoàn tiền", href: "/chinh-sach-hoan-tien" },
            { label: "Chính sách thanh toán", href: "/chinh-sach-thanh-toan" },
            { label: "Chính sách sử dụng dịch vụ", href: "/chinh-sach-su-dung-api" },
            { label: "Miễn trừ trách nhiệm", href: "/mien-tru-trach-nhiem" },
        ],
    },
    {
        title: "Hệ thống",
        links: [
            { label: "Trạng thái hệ thống", href: "/trang-thai-he-thong" },
            { label: "Cập nhật hệ thống", href: "/cap-nhat-he-thong" },
        ],
    },
]);

onMounted(async () => {
    await fetchSettings();
});
</script>

<template>
    <footer class="mt-8 border-t border-slate-200/80 bg-white/80 backdrop-blur-sm">
        <div class="mx-auto grid w-full max-w-[1200px] gap-8 px-3 py-8 lg:grid-cols-[minmax(0,1.2fr)_repeat(3,minmax(0,1fr))]">
            <div class="space-y-3">
                <h2 class="text-lg font-bold tracking-tight text-slate-950">
                    {{ settings.site_name || "AutoCron" }}
                </h2>
                <p class="max-w-md text-sm leading-6 text-slate-600">
                    {{
                        settings.site_description ||
                        "Nền tảng SaaS giúp tạo, chạy và giám sát HTTP Cron Jobs theo lịch với quota, log và cảnh báo rõ ràng."
                    }}
                </p>
                <div class="flex flex-wrap gap-3 text-sm text-slate-500">
                    <span v-if="settings.hotline">Hotline: {{ settings.hotline }}</span>
                    <span v-if="settings.support_email">Email: {{ settings.support_email }}</span>
                </div>
            </div>

            <div v-for="group in footerLinkGroups" :key="group.title" class="space-y-3">
                <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-900">
                    {{ group.title }}
                </h3>
                <ul class="space-y-2 text-sm text-slate-600">
                    <li v-for="link in group.links" :key="link.href">
                        <a :href="link.href" class="transition hover:text-sky-700">
                            {{ link.label }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </footer>
</template>
