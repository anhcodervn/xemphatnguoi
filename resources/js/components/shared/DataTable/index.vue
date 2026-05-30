<template>
    <div class="flex flex-col gap-3 ">
        <!-- table -->
        <div
            class="w-full overflow-x-auto rounded border 
            border-gray-200 shadow-sm"
        >
            <table class="text-sm"
                :class="props.classCustom">
                <!-- HEADER -->
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            class="px-5 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider"
                        >
                            {{ header.column.columnDef.header }}
                        </th>
                    </tr>
                </thead>
    
                <!-- BODY -->
                <tbody class="divide-y divide-gray-300 relative">
                     <!-- loading -->
                    <Loading :loading="props.loading"/>
                    <tr
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="hover:bg-gray-200 transition cursor-pointer"
                    >
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            class="px-5 py-3 text-gray-700"
                        >
                            <!-- custom slot -->
                            <slot
                                :name="cell.column.id"
                                :row="row.original"
                                :value="cell.getValue()"
                            >
                                {{ cell.getValue() }}
                            </slot>
                        </td>
                    </tr>
    
                    <!-- EMPTY -->
                    <tr v-if="!table.getRowModel().rows.length">
                        <td
                            :colspan="columns.length"
                            class="text-center py-6 text-gray-400"
                        >
                            No data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- paginate -->
        <div>
            <Paginate :currentPage="props.currentPage" :totalPages="props.totalPages"
                :goToPage="props.goToPage"/>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useVueTable, getCoreRowModel } from "@tanstack/vue-table";
import Paginate from "./PaginateComponent.vue";
import Loading from "./Loading.vue";

const props = defineProps<{
    data: any[];
    columns: any[];
    goToPage: (page: number) => Promise<void>;
    totalPages: number;
    currentPage: number;
    loading: boolean;
    classCustom?: string;
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
