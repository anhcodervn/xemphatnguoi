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

const expandedGroups = reactive<Record<string, boolean>>(
    Object.fromEntries(adminMenuGroups.filter((group) => group.children).map((group) => [group.key, false])),
);

const isGroupActive = (group: AdminMenuGroup): boolean => {
    if (group.href) {
        return route.path === group.href;
    }

    const childHrefs = group.children?.map((child) => child.href) ?? [];

    return findBestMatchingChildHref(childHrefs, route.path) !== null;
};

const isChildActive = (href: string): boolean => {
    const menuChildHrefs = adminMenuGroups.flatMap((group) => group.children?.map((child) => child.href) ?? []);

    return findBestMatchingChildHref(menuChildHrefs, route.path) === href;
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
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white text-slate-700 shadow-[0_24px_80px_rgba(15,23,42,0.14)] lg:sticky lg:top-0 lg:shadow-none"
        >
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <RouterLink to="/admin" class="flex items-center gap-3" @click="closeSidebarOnMobile">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-400 text-white shadow-sm"
                    >
                        <ShieldCheck class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-blue-600">XemPhatNguoi</p>
                        <h1 class="text-base font-black tracking-tight text-slate-950">Admin Control</h1>
                    </div>
                </RouterLink>

                <button
                    type="button"
                    aria-label="Đóng menu quản trị"
                    class="rounded-xl border border-slate-200 p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 lg:hidden"
                    @click="emit('close')"
                >
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <div class="mb-3 px-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Điều hướng</p>
                </div>

                <div class="grid gap-1.5">
                    <template v-for="group in adminMenuGroups" :key="group.key">
                        <RouterLink
                            v-if="group.href"
                            :to="group.href"
                            class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            :class="isGroupActive(group) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
                            @click="closeSidebarOnMobile"
                        >
                            <component :is="group.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                            <span class="min-w-0 flex-1">{{ group.label }}</span>
                        </RouterLink>

                        <div v-else>
                            <button
                                type="button"
                                :aria-expanded="expandedGroups[group.key]"
                                :aria-controls="`admin-menu-${group.key}`"
                                class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                :class="isGroupActive(group) ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'"
                                @click="toggleGroup(group.key)"
                            >
                                <component :is="group.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                                <span class="min-w-0 flex-1">{{ group.label }}</span>
                                <ChevronRight
                                    class="h-4 w-4 shrink-0 transition-transform"
                                    :class="expandedGroups[group.key] ? 'rotate-90 text-blue-600' : 'text-slate-400'"
                                    aria-hidden="true"
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
                                <div
                                    v-if="expandedGroups[group.key]"
                                    :id="`admin-menu-${group.key}`"
                                    class="ml-5 grid gap-1 overflow-hidden border-l border-slate-200 py-1 pl-3"
                                >
                                    <RouterLink
                                        v-for="child in group.children"
                                        :key="child.href"
                                        :to="child.href"
                                        class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                                        :class="
                                            isChildActive(child.href)
                                                ? 'bg-blue-50 font-semibold text-blue-700'
                                                : 'text-slate-500 hover:bg-slate-50 hover:text-blue-700'
                                        "
                                        @click="closeSidebarOnMobile"
                                    >
                                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-current opacity-60" aria-hidden="true" />
                                        <span class="min-w-0 flex-1">{{ child.label }}</span>
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
