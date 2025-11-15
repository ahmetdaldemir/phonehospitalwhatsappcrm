<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Price Matrix</h1>
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <PlusIcon class="h-5 w-5 mr-2" />
                Add Price
            </button>
        </div>

        <DataTable
            :columns="columns"
            :data="priceMatrix"
            :loading="loading"
            :pagination="pagination"
            :actions="['edit', 'delete']"
            @edit="openEditModal"
            @delete="handleDelete"
            @page-change="handlePageChange"
        />

        <Modal :show="showModal" :title="modalTitle" @close="closeModal">
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Brand</label>
                    <input v-model="form.brand" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Model</label>
                    <input v-model="form.model" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Problem Type</label>
                    <input v-model="form.problem_type" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Min Price</label>
                    <input v-model.number="form.price_min" type="number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Max Price</label>
                    <input v-model.number="form.price_max" type="number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" @click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import DataTable from '../components/DataTable.vue';
import Modal from '../components/Modal.vue';
import axios from '../config/axios';
import { useAppStore } from '../stores/app';

const appStore = useAppStore();

const priceMatrix = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showModal = ref(false);
const editingId = ref(null);

const form = ref({
    brand: '',
    model: '',
    problem_type: '',
    price_min: 0,
    price_max: 0,
});

const modalTitle = computed(() => editingId.value ? 'Edit Price' : 'Add Price');

const columns = [
    { key: 'brand', label: 'Brand' },
    { key: 'model', label: 'Model' },
    { key: 'problem_type', label: 'Problem Type' },
    { key: 'price_min', label: 'Min Price', format: 'currency' },
    { key: 'price_max', label: 'Max Price', format: 'currency' },
];

const fetchPriceMatrix = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/price-matrix', { params: { page } });
        priceMatrix.value = response.data.data || response.data;
        pagination.value = response.data;
    } catch (error) {
        appStore.error('Failed to fetch price matrix');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingId.value = null;
    form.value = { brand: '', model: '', problem_type: '', price_min: 0, price_max: 0 };
    showModal.value = true;
};

const openEditModal = (item) => {
    editingId.value = item.id;
    form.value = { ...item };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
    form.value = { brand: '', model: '', problem_type: '', price_min: 0, price_max: 0 };
};

const handleSubmit = async () => {
    try {
        if (editingId.value) {
            await axios.put(`/api/price-matrix/${editingId.value}`, form.value);
            appStore.success('Price updated successfully');
        } else {
            await axios.post('/api/price-matrix', form.value);
            appStore.success('Price added successfully');
        }
        closeModal();
        fetchPriceMatrix();
    } catch (error) {
        appStore.error('Failed to save price');
    }
};

const handleDelete = async (item) => {
    if (!confirm('Are you sure you want to delete this price?')) return;
    
    try {
        await axios.delete(`/api/price-matrix/${item.id}`);
        appStore.success('Price deleted successfully');
        fetchPriceMatrix();
    } catch (error) {
        appStore.error('Failed to delete price');
    }
};

const handlePageChange = (page) => {
    fetchPriceMatrix(page);
};

onMounted(() => {
    fetchPriceMatrix();
});
</script>

