<template>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            {{ column.label }}
                        </th>
                        <th v-if="actions" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
                        <td v-for="column in columns" :key="column.key" class="px-6 py-4 whitespace-nowrap">
                            <div class="h-4 bg-gray-200 rounded"></div>
                        </td>
                        <td v-if="actions" class="px-6 py-4 whitespace-nowrap">
                            <div class="h-4 bg-gray-200 rounded"></div>
                        </td>
                    </tr>
                    <tr v-else-if="data.length === 0">
                        <td :colspan="columns.length + (actions ? 1 : 0)" class="px-6 py-12 text-center text-gray-500">
                            No data available
                        </td>
                    </tr>
                    <tr v-else v-for="(row, index) in data" :key="row.id || index" class="hover:bg-gray-50">
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ formatValue(row[column.key], column) }}
                            </slot>
                        </td>
                        <td v-if="actions" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <slot name="actions" :row="row">
                                <button
                                    v-if="actions.includes('view')"
                                    @click="$emit('view', row)"
                                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                                >
                                    View
                                </button>
                                <button
                                    v-if="actions.includes('edit')"
                                    @click="$emit('edit', row)"
                                    class="text-yellow-600 hover:text-yellow-900 mr-3"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="actions.includes('delete')"
                                    @click="$emit('delete', row)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    Delete
                                </button>
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="pagination" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
                <button
                    @click="$emit('page-change', pagination.current_page - 1)"
                    :disabled="!pagination.prev_page_url"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                >
                    Previous
                </button>
                <button
                    @click="$emit('page-change', pagination.current_page + 1)"
                    :disabled="!pagination.next_page_url"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                >
                    Next
                </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium">{{ pagination.from || 0 }}</span>
                        to
                        <span class="font-medium">{{ pagination.to || 0 }}</span>
                        of
                        <span class="font-medium">{{ pagination.total || 0 }}</span>
                        results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <button
                            @click="$emit('page-change', pagination.current_page - 1)"
                            :disabled="!pagination.prev_page_url"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Previous
                        </button>
                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            @click="$emit('page-change', page)"
                            :class="[
                                page === pagination.current_page
                                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                            ]"
                        >
                            {{ page }}
                        </button>
                        <button
                            @click="$emit('page-change', pagination.current_page + 1)"
                            :disabled="!pagination.next_page_url"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                        >
                            Next
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
    },
    data: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    actions: {
        type: Array,
        default: null,
    },
    pagination: {
        type: Object,
        default: null,
    },
});

defineEmits(['view', 'edit', 'delete', 'page-change']);

const formatValue = (value, column) => {
    if (value === null || value === undefined) return '-';
    
    if (column.format === 'date') {
        return new Date(value).toLocaleDateString();
    }
    
    if (column.format === 'currency') {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    }
    
    if (column.format === 'boolean') {
        return value ? 'Yes' : 'No';
    }
    
    return value;
};

const visiblePages = computed(() => {
    if (!props.pagination) return [];
    
    const current = props.pagination.current_page;
    const last = props.pagination.last_page;
    const pages = [];
    
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});
</script>

