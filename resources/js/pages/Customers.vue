<template>
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Customers</h1>

        <DataTable
            :columns="columns"
            :data="customers"
            :loading="loading"
            :pagination="pagination"
            :actions="['view']"
            @view="openDetailModal"
            @page-change="handlePageChange"
        />

        <Modal :show="showDetailModal" title="Customer Details" @close="showDetailModal = false">
            <div v-if="selectedCustomer" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedCustomer.name || 'N/A' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedCustomer.phone_number }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Visits</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedCustomer.total_visits }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Visit</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedCustomer.last_visit_at ? new Date(selectedCustomer.last_visit_at).toLocaleString() : 'Never' }}</p>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import Modal from '../components/Modal.vue';
import axios from '../config/axios';
import { useAppStore } from '../stores/app';

const appStore = useAppStore();

const customers = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showDetailModal = ref(false);
const selectedCustomer = ref(null);

const columns = [
    { key: 'phone_number', label: 'Phone' },
    { key: 'name', label: 'Name' },
    { key: 'total_visits', label: 'Visits' },
    { key: 'last_visit_at', label: 'Last Visit', format: 'date' },
];

const fetchCustomers = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/customers', { params: { page } });
        customers.value = response.data.data || response.data;
        pagination.value = response.data;
    } catch (error) {
        appStore.error('Failed to fetch customers');
    } finally {
        loading.value = false;
    }
};

const openDetailModal = (customer) => {
    selectedCustomer.value = customer;
    showDetailModal.value = true;
};

const handlePageChange = (page) => {
    fetchCustomers(page);
};

onMounted(() => {
    fetchCustomers();
});
</script>

