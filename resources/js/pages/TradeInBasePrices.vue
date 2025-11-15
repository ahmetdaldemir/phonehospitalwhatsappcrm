<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Trade-In Base Prices</h1>
            <button
                @click="showCreateModal = true"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <PlusIcon class="h-5 w-5 mr-2" />
                New Base Price
            </button>
        </div>

        <div class="mb-4 flex gap-4">
            <input
                v-model="filters.brand"
                type="text"
                placeholder="Filter by brand"
                class="rounded-md border-gray-300 shadow-sm"
                @input="fetchBasePrices"
            />
            <input
                v-model="filters.model"
                type="text"
                placeholder="Filter by model"
                class="rounded-md border-gray-300 shadow-sm"
                @input="fetchBasePrices"
            />
            <select v-model="filters.active" @change="fetchBasePrices" class="rounded-md border-gray-300 shadow-sm">
                <option :value="null">All</option>
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
            </select>
        </div>

        <DataTable
            :columns="columns"
            :data="basePrices"
            :loading="loading"
            :pagination="pagination"
            :actions="['edit', 'delete']"
            @edit="openEditModal"
            @delete="confirmDelete"
            @page-change="handlePageChange"
        />

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal">
            <template #title>
                {{ showEditModal ? 'Edit Base Price' : 'New Base Price' }}
            </template>
            <template #content>
                <form @submit.prevent="saveBasePrice" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Brand</label>
                        <input
                            v-model="form.brand"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Model</label>
                        <input
                            v-model="form.model"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Storage</label>
                        <select
                            v-model="form.storage"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">Select Storage</option>
                            <option value="64GB">64GB</option>
                            <option value="128GB">128GB</option>
                            <option value="256GB">256GB</option>
                            <option value="512GB">512GB</option>
                            <option value="1TB">1TB</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Base Price (TL)</label>
                        <input
                            v-model.number="form.base_price"
                            type="number"
                            required
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        />
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input
                                v-model="form.active"
                                type="checkbox"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm"
                            />
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                        >
                            Save
                        </button>
                    </div>
                </form>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import DataTable from '../components/DataTable.vue';
import Modal from '../components/Modal.vue';
import axios from '../config/axios';
import { useAppStore } from '../stores/app';

const appStore = useAppStore();

const basePrices = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedBasePrice = ref(null);

const filters = ref({
    brand: '',
    model: '',
    active: null,
});

const form = ref({
    brand: '',
    model: '',
    storage: '',
    base_price: 0,
    active: true,
});

const columns = [
    { key: 'brand', label: 'Brand' },
    { key: 'model', label: 'Model' },
    { key: 'storage', label: 'Storage' },
    { key: 'base_price', label: 'Base Price', formatter: (val) => `${val} TL` },
    {
        key: 'active',
        label: 'Status',
        formatter: (val) => (val ? 'Active' : 'Inactive'),
    },
    { key: 'created_at', label: 'Created', formatter: formatDate },
];

function formatDate(date) {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('tr-TR');
}

async function fetchBasePrices(page = 1) {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: page.toString(),
            ...Object.fromEntries(
                Object.entries(filters.value).filter(([_, v]) => v !== null && v !== '')
            ),
        });
        const response = await axios.get(`/api/tradein-base-prices?${params}`);
        basePrices.value = response.data.data || [];
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (error) {
        console.error('Error fetching base prices:', error);
        appStore.showToast('Error fetching base prices', 'error');
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    fetchBasePrices(page);
}

function openEditModal(basePrice) {
    selectedBasePrice.value = basePrice;
    form.value = {
        brand: basePrice.brand,
        model: basePrice.model,
        storage: basePrice.storage,
        base_price: basePrice.base_price,
        active: basePrice.active,
    };
    showEditModal.value = true;
}

function closeModal() {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedBasePrice.value = null;
    form.value = {
        brand: '',
        model: '',
        storage: '',
        base_price: 0,
        active: true,
    };
}

async function saveBasePrice() {
    try {
        if (showEditModal.value && selectedBasePrice.value) {
            await axios.put(`/api/tradein-base-prices/${selectedBasePrice.value.id}`, form.value);
            appStore.showToast('Base price updated successfully', 'success');
        } else {
            await axios.post('/api/tradein-base-prices', form.value);
            appStore.showToast('Base price created successfully', 'success');
        }
        closeModal();
        fetchBasePrices(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error saving base price:', error);
        appStore.showToast('Error saving base price', 'error');
    }
}

async function confirmDelete(basePrice) {
    if (!confirm('Are you sure you want to delete this base price?')) {
        return;
    }

    try {
        await axios.delete(`/api/tradein-base-prices/${basePrice.id}`);
        appStore.showToast('Base price deleted successfully', 'success');
        fetchBasePrices(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error deleting base price:', error);
        appStore.showToast('Error deleting base price', 'error');
    }
}

onMounted(() => {
    fetchBasePrices();
});
</script>

