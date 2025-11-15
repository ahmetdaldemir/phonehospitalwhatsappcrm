<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tickets</h1>
            <button
                @click="showCreateModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <PlusIcon class="h-5 w-5 mr-2" />
                New Ticket
            </button>
        </div>

        <div class="mb-4">
            <select v-model="filters.status" @change="fetchTickets" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="directed">Directed</option>
                <option value="completed">Completed</option>
                <option value="canceled">Canceled</option>
            </select>
        </div>

        <DataTable
            :columns="columns"
            :data="tickets"
            :loading="loading"
            :pagination="pagination"
            :actions="['view']"
            @view="openDetailModal"
            @page-change="handlePageChange"
        />

        <SideModal :show="showDetailModal" :title="selectedTicket ? `Ticket #${selectedTicket.id.substring(0, 8)}` : ''" @close="showDetailModal = false">
            <div v-if="selectedTicket" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <span :class="statusClasses[selectedTicket.status]" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ selectedTicket.status }}
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Customer</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTicket.customer?.name || 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Brand</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTicket.brand }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Model</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTicket.model }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Problem Type</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTicket.problem_type }}</p>
                </div>
                <div v-if="selectedTicket.price_min && selectedTicket.price_max">
                    <label class="block text-sm font-medium text-gray-700">Price Range</label>
                    <p class="mt-1 text-sm text-gray-900">${{ selectedTicket.price_min }} - ${{ selectedTicket.price_max }}</p>
                </div>
                <div v-if="selectedTicket.photos && selectedTicket.photos.length > 0">
                    <label class="block text-sm font-medium text-gray-700">Photos</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <img v-for="photo in selectedTicket.photos" :key="photo" :src="`/storage/${photo}`" alt="Ticket photo" class="rounded-lg" />
                    </div>
                </div>
            </div>
        </SideModal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import DataTable from '../components/DataTable.vue';
import SideModal from '../components/SideModal.vue';
import axios from '../config/axios';
import { useAppStore } from '../stores/app';

const appStore = useAppStore();

const tickets = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showDetailModal = ref(false);
const selectedTicket = ref(null);
const showCreateModal = ref(false);

const filters = ref({
    status: '',
});

const statusClasses = {
    new: 'bg-blue-100 text-blue-800',
    directed: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    canceled: 'bg-red-100 text-red-800',
};

const columns = [
    { key: 'id', label: 'ID', format: (value) => value.substring(0, 8) },
    { key: 'customer', label: 'Customer', format: (value) => value?.name || 'N/A' },
    { key: 'brand', label: 'Brand' },
    { key: 'model', label: 'Model' },
    { key: 'problem_type', label: 'Problem' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Created', format: 'date' },
];

const fetchTickets = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page, ...filters.value };
        const response = await axios.get('/api/tickets', { params });
        tickets.value = response.data.data || response.data;
        pagination.value = response.data;
    } catch (error) {
        appStore.error('Failed to fetch tickets');
    } finally {
        loading.value = false;
    }
};

const openDetailModal = async (ticket) => {
    try {
        const response = await axios.get(`/api/tickets/${ticket.id}`);
        selectedTicket.value = response.data;
        showDetailModal.value = true;
    } catch (error) {
        appStore.error('Failed to fetch ticket details');
    }
};

const handlePageChange = (page) => {
    fetchTickets(page);
};

onMounted(() => {
    fetchTickets();
});
</script>

