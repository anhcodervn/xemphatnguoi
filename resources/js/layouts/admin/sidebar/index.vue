<script setup lang="ts">
import { ChevronRight, ShieldCheck, X } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { adminMenuGroups, type AdminMenuGroup } from './navigation';

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const route = useRoute();

const findBestMatchingChildHref = (hrefs: string[], currentPath: string): string | null => {
    return (
        [...hrefs].sort((left, right) => right.length - left.length).find((href) => currentPath === href || currentPath.startsWith(`${href}/`)) ??
        null
    );
};

const expandedGroups = reactive<Record<string, boolean>>({
    users: false,
    products: false,
    discounts: false,
    settings: false,
});

const isGroupActive = (group: AdminMenuGroup): boolean => {
    if (group.href) {
        return route.path === group.href;
    }

    const childHrefs = group.children?.map((child) => child.href) ?? [];

    return findBestMatchingChildHref(childHrefs, route.path) !== null;
};

const isChildActive = (href: string): boolean => {
    const packageChildHrefs = adminMenuGroups.flatMap((group) => group.children?.map((child) => child.href) ?? []);

    return findBestMatchingChildHref(packageChildHrefs, route.path) === href;
};

const toggleGroup = (groupKey: string): void => {
    expandedGroups[groupKey] = !expandedGroups[groupKey];
};

const closeSidebarOnMobile = (): void => {
    if (window.innerWidth < 1024) {
        emit('close');
    }
};

watch(
    () => route.path,
    () => {
        for (const group of adminMenuGroups) {
            if (group.children) {
                expandedGroups[group.key] = isGroupActive(group);
            }
        }
    },
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="-translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="-translate-x-full opacity-0"
    >
        <aside
            v-if="props.isOpen"
            class="fixed inset-y-0 left-0 z-50 flex w-[290px] flex-col border-r border-teal-100 bg-white/95 shadow-[0_24px_80px_rgba(14,116,144,0.14)] backdrop-blur-xl lg:sticky lg:top-0 lg:shadow-none"
        >
            <div class="flex items-center justify-between border-b border-teal-100 px-6 py-5">
                <RouterLink to="/admin" class="flex items-center gap-3" @click="closeSidebarOnMobile">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[linear-gradient(135deg,_#0f766e_0%,_#06b6d4_100%)] text-white shadow-[0_12px_28px_rgba(6,182,212,0.28)]">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-600">Dashboard</p>
                        <h1 class="text-lg font-black tracking-tight text-teal-950">Admin Board</h1>
                    </div>
                </RouterLink>

                <button
                    type="button"
                    class="rounded-2xl border border-teal-100 p-2 text-teal-700 transition hover:bg-teal-50 lg:hidden"
                    @click="emit('close')"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-5">
                <div class="mb-4 px-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-teal-500/80">Navigation</p>
                </div>

                <div class="space-y-2">
                    <template v-for="group in adminMenuGroups" :key="group.key">
                        <RouterLink
                            v-if="group.href"
                            :to="group.href"
                            class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition"
                            :class="
                                isGroupActive(group)
                                    ? 'bg-[linear-gradient(135deg,_#0f766e_0%,_#0891b2_100%)] text-white shadow-[0_14px_30px_rgba(6,182,212,0.22)]'
                                    : 'text-slate-600 hover:bg-teal-50 hover:text-teal-950'
                            "
                            @click="closeSidebarOnMobile"
                        >
                            <component :is="group.icon" class="h-5 w-5" />
                            <span>{{ group.label }}</span>
                        </RouterLink>

                        <div v-else class="rounded-[1.35rem] border border-teal-100 bg-teal-50/70">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm font-semibold transition"
                                :class="isGroupActive(group) ? 'text-teal-950' : 'text-slate-600 hover:text-teal-950'"
                                @click="toggleGroup(group.key)"
                            >
                                <component :is="group.icon" class="h-5 w-5" />
                                <span class="flex-1">{{ group.label }}</span>
                                <ChevronRight
                                    class="h-4 w-4 transition"
                                    :class="expandedGroups[group.key] ? 'rotate-90 text-teal-600' : 'text-teal-300'"
                                />
                            </button>

                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="max-h-0 opacity-0"
                                enter-to-class="max-h-96 opacity-100"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="max-h-96 opacity-100"
                                leave-to-class="max-h-0 opacity-0"
                            >
                                <div v-if="expandedGroups[group.key]" class="overflow-hidden px-3 pb-3">
                                    <RouterLink
                                        v-for="child in group.children"
                                        :key="child.href"
                                        :to="child.href"
                                        class="mt-1 flex items-center gap-3 rounded-2xl px-4 py-2.5 text-sm transition"
                                        :class="
                                            isChildActive(child.href)
                                                ? 'bg-white font-semibold text-teal-700 shadow-sm ring-1 ring-teal-100'
                                                : 'text-slate-500 hover:bg-white hover:text-teal-900'
                                        "
                                        @click="closeSidebarOnMobile"
                                    >
                                        <span class="bg-current/70 h-1.5 w-1.5 rounded-full" />
                                        <span>{{ child.label }}</span>
                                    </RouterLink>
                                </div>
                            </Transition>
                        </div>
                    </template>
                </div>
            </nav>
        </aside>
    </Transition>
</template>

