<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Stores</h1>
            <button
                v-if="authStore.isAdmin"
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <PlusIcon class="h-5 w-5 mr-2" />
                Add Store
            </button>
        </div>

        <DataTable
            :columns="columns"
            :data="stores"
            :loading="loading"
            :pagination="pagination"
            :actions="authStore.isAdmin ? ['edit', 'delete'] : []"
            @edit="openEditModal"
            @delete="handleDelete"
            @page-change="handlePageChange"
        />

        <Modal :show="showModal" :title="modalTitle" max-width="2xl" @close="closeModal">
            <form @submit.prevent="handleSubmit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Code</label>
                    <input v-model="form.code" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Address</label>
                    <textarea v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Location</label>
                    <button type="button" @click="showMapPicker = true" class="mt-1 px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        Pick Location on Map
                    </button>
                    <div v-if="form.location_lat && form.location_lng" class="mt-2 text-sm text-gray-600">
                        Lat: {{ form.location_lat }}, Lng: {{ form.location_lng }}
                    </div>
                </div>
                <div class="flex items-center">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600" />
                    <label class="ml-2 block text-sm text-gray-900">Active</label>
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

        <Modal :show="showMapPicker" title="Pick Location" max-width="4xl" @close="showMapPicker = false">
            <div class="h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                <p class="text-gray-500">Map integration placeholder. Click to set location.</p>
                <div class="absolute inset-0 flex items-center justify-center">
                    <button
                        @click="setLocation(40.7128, -74.0060)"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md"
                    >
                        Use Sample Location
                    </button>
                </div>
            </div>
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
import { useAuthStore } from '../stores/auth';

const appStore = useAppStore();
const authStore = useAuthStore();

const stores = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showModal = ref(false);
const showMapPicker = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    code: '',
    address: '',
    location_lat: null,
    location_lng: null,
    is_active: true,
});

const modalTitle = computed(() => editingId.value ? 'Edit Store' : 'Add Store');

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'code', label: 'Code' },
    { key: 'address', label: 'Address' },
    { key: 'is_active', label: 'Active', format: 'boolean' },
];

const fetchStores = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/stores', { params: { page } });
        stores.value = response.data.data || response.data;
        pagination.value = response.data;
    } catch (error) {
        appStore.error('Failed to fetch stores');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingId.value = null;
    form.value = { name: '', code: '', address: '', location_lat: null, location_lng: null, is_active: true };
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
    form.value = { name: '', code: '', address: '', location_lat: null, location_lng: null, is_active: true };
};

const setLocation = (lat, lng) => {
    form.value.location_lat = lat;
    form.value.location_lng = lng;
    showMapPicker.value = false;
};

const handleSubmit = async () => {
    try {
        if (editingId.value) {
            await axios.put(`/api/stores/${editingId.value}`, form.value);
            appStore.success('Store updated successfully');
        } else {
            await axios.post('/api/stores', form.value);
            appStore.success('Store added successfully');
        }
        closeModal();
        fetchStores();
    } catch (error) {
        appStore.error('Failed to save store');
    }
};

const handleDelete = async (item) => {
    if (!confirm('Are you sure you want to delete this store?')) return;
    
    try {
        await axios.delete(`/api/stores/${item.id}`);
        appStore.success('Store deleted successfully');
        fetchStores();
    } catch (error) {
        appStore.error('Failed to delete store');
    }
};

const handlePageChange = (page) => {
    fetchStores(page);
};

onMounted(() => {
    fetchStores();
});
</script>

