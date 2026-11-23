<template>
    <div class="flex min-w-0 flex-col gap-3">
        <div v-if="$slots['mobile-row']" class="grid gap-3 md:hidden">
            <slot v-for="row in props.data" :key="row.id" name="mobile-row" :row="row" />
            <p v-if="!props.data.length && !props.loading" class="py-6 text-center text-sm text-slate-500">
                {{ props.emptyMessage || 'No data...' }}
            </p>
        </div>
        <!-- table -->
        <div class="w-full overflow-x-auto rounded border border-gray-200 shadow-sm" :class="{ 'hidden md:block': $slots['mobile-row'] }">
            <table class="text-sm" :class="props.classCustom">
                <!-- HEADER -->
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            class="px-5 py-3 text-left font-semibold uppercase tracking-wider text-gray-600"
                        >
                            {{ header.column.columnDef.header }}
                        </th>
                    </tr>
                </thead>

                <!-- BODY -->
                <tbody class="relative divide-y divide-gray-300">
                    <!-- loading -->
                    <Loading :loading="props.loading" />
                    <tr v-for="row in table.getRowModel().rows" :key="row.id" class="cursor-pointer transition hover:bg-gray-200">
                        <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-5 py-3 text-gray-700">
                            <!-- custom slot -->
                            <slot :name="cell.column.id" :row="row.original" :value="cell.getValue()">
                                {{ cell.getValue() }}
                            </slot>
                        </td>
                    </tr>

                    <!-- EMPTY -->
                    <tr v-if="!table.getRowModel().rows.length">
                        <td :colspan="columns.length" class="py-6 text-center text-gray-400">
                            {{ props.emptyMessage || 'No data...' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- paginate -->
        <div>
            <Paginate :currentPage="props.currentPage" :totalPages="props.totalPages" :goToPage="props.goToPage" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import Loading from './Loading.vue';
import Paginate from './PaginateComponent.vue';

const props = defineProps<{
    data: any[];
    columns: any[];
    goToPage: (page: number) => Promise<void>;
    totalPages: number;
    currentPage: number;
    loading: boolean;
    classCustom?: string;
    emptyMessage?: string;
}>();

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
});
</script>
