<template>
    <div class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex items-center gap-2 text-sm text-slate-500">
            <template v-for="(item, index) in resolvedItems" :key="`${item.label}-${index}`">
                <template v-if="index > 0">
                    <span class="mr-2 text-slate-300">/</span>
                </template>

                <router-link
                    v-if="item.to && !item.active"
                    :to="item.to"
                    class="transition-colors hover:text-blue-600"
                >
                    {{ item.label }}
                </router-link>

                <span v-else class="font-medium text-slate-900">
                    {{ item.label }}
                </span>
            </template>
        </div>

        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    {{ resolvedTitle }}
                </h1>
                <p v-if="description" class="text-sm text-slate-500">
                    {{ description }}
                </p>
            </div>

            <slot name="actions" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";

interface BreadcrumbItem {
    label: string;
    to?: string;
    active?: boolean;
}

const route = useRoute();

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items?: BreadcrumbItem[];
        homeLabel?: string;
    }>(),
    {
        title: "",
        description: "",
        items: () => [],
        homeLabel: "Dashboard",
    },
);

const routeLabelMap: Record<string, string> = {
    "master.home": "Dashboard",
    "master.users": "Người dùng",
    "master.users.lists": "Danh sách người dùng",
    "master.users.info": "Thông tin người dùng",
    "master.products": "Sản phẩm",
    "master.products.lists": "Danh sách sản phẩm",
    "master.products.info-product": "Thông tin sản phẩm",
    "master.products.item-product": "Gói sản phẩm",
    "master.product-categories": "Danh mục sản phẩm",
    "master.product-categories.lists": "Danh sách danh mục",
    "master.product-categories.info": "Thông tin danh mục",
    "master.discounts": "Giảm giá",
    "master.discounts.flash-sales": "Flash sale",
    "master.discounts.flash-sales.lists": "Danh sách flash sale",
    "master.discounts.flash-sales.info": "Thông tin flash sale",
    "master.discounts.coupons": "Mã giảm giá",
    "master.discounts.coupons.lists": "Danh sách mã giảm giá",
    "master.discounts.coupons.info": "Thông tin mã giảm giá",
    "master.settings": "Cài đặt website",
    "master.settings.common": "Cấu hình chung",
    "master.settings.common.general": "Tổng quan",
    "master.settings.common.branding": "Nhận diện",
    "master.settings.common.home-category": "Danh mục trang chủ",
    "master.settings.common.slider-images": "Slider nổi bật",
    "master.settings.common.contact": "Liên hệ",
    "master.settings.common.seo": "SEO và tracking",
    "master.settings.options": "Cấu hình option",
};

const autoItems = computed<BreadcrumbItem[]>(() => {
    const matchedRoutes = route.matched.filter((item) => item.name && item.name !== "master");

    const items = matchedRoutes.map((item, index) => {
        const name = item.name?.toString() || "";
        const label =
            routeLabelMap[name] ||
            item.meta?.title?.toString() ||
            name
                .split(".")
                .pop()
                ?.replace(/-/g, " ")
                .replace(/\b\w/g, (char) => char.toUpperCase()) ||
            "Trang";

        return {
            label,
            to: item.path.includes(":") ? undefined : item.path,
            active: index === matchedRoutes.length - 1,
        };
    });

    return [
        {
            label: props.homeLabel,
            to: "/master",
            active: items.length === 0,
        },
        ...items,
    ];
});

const resolvedItems = computed(() => {
    if (props.items.length) {
        return props.items.map((item, index) => ({
            ...item,
            active: item.active ?? index === props.items.length - 1,
        }));
    }

    return autoItems.value;
});

const resolvedTitle = computed(() => {
    if (props.title) {
        return props.title;
    }

    return resolvedItems.value[resolvedItems.value.length - 1]?.label || props.homeLabel;
});

const description = computed(() => props.description);
</script>
