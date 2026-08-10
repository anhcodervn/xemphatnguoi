<template>
    <div>
        <!-- ITEM -->
        <div class="flex cursor-pointer items-center justify-between px-5 py-2 transition" :class="itemClass" @click="handleClick">
            <!-- LEFT -->
            <div class="flex items-center gap-2">
                <!-- ICON -->
                <component v-if="item.icon" :is="item.icon" class="h-5 w-5 font-bold" :class="iconClass" />

                <!-- LABEL -->
                <span :class="labelClass">
                    {{ item.label }}
                </span>
            </div>

            <!-- TOGGLE -->
            <span v-if="hasChildren">
                {{ open ? '-' : '+' }}
            </span>
        </div>

        <!-- CHILDREN -->
        <div v-if="hasChildren && open" class="mb-3 ml-[1.5rem] rounded-md border border-t-0 border-gray-300">
            <SidebarItem v-for="(child, index) in item.children" :key="child.label + index" :item="child" />
        </div>
    </div>
</template>

<script setup lang="ts">
import type { SidebarItemType } from '@/types/sidebar.type';
import { computed, ref, watchEffect } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import SidebarItem from './SidebarItem.vue';

const props = defineProps<{
    item: SidebarItemType;
}>();

const router = useRouter();
const route = useRoute();

const open = ref(false);

const hasChildren = computed(() => props.item.children?.length);

// ✅ child active (exact match)
const isExactActive = (path?: string) => {
    return path && route.path === path;
};

// ✅ parent active (có child match)
const isParentActive = (item: SidebarItemType): boolean => {
    if (!item.children) return false;

    return item.children.some((child) => {
        // nếu child có path
        if (child.path && route.path.startsWith(child.path)) {
            return true;
        }

        // nếu child có children → đệ quy
        if (child.children) {
            return isParentActive(child);
        }

        return false;
    });
};

// ✅ class tổng
const itemClass = computed(() => {
    if (isParentActive(props.item)) {
        return 'bg-blue-600 text-white font-bold';
    }

    if (isExactActive(props.item.path)) {
        return 'text-blue-600 font-semibold';
    }

    return 'hover:bg-blue-600 hover:text-white';
});

// ✅ icon class
const iconClass = computed(() => {
    if (isParentActive(props.item)) return 'text-white';
    if (isExactActive(props.item.path)) return 'text-blue-600';
    return 'text-gray-500';
});

// ✅ label class
const labelClass = computed(() => {
    if (isExactActive(props.item.path)) return 'text-blue-600';
    return '';
});

// ✅ auto mở nếu có child active
watchEffect(() => {
    if (isParentActive(props.item)) {
        open.value = true;
    }
});

// ✅ click
const handleClick = () => {
    if (hasChildren.value) {
        open.value = !open.value;
    } else if (props.item.path) {
        router.push(props.item.path);
    }
};
</script>

<style></style>
