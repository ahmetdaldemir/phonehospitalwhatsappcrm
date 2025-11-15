<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Campaigns</h1>
            <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
                <PlusIcon class="h-5 w-5 mr-2" />
                Add Campaign
            </button>
        </div>

        <DataTable
            :columns="columns"
            :data="campaigns"
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
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Discount Type</label>
                    <select v-model="form.discount_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="percent">Percent</option>
                        <option value="free_item">Free Item</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Value</label>
                    <input v-model.number="form.value" type="number" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input v-model="form.start_date" type="date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input v-model="form.end_date" type="date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
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

const campaigns = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showModal = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    discount_type: 'percent',
    value: 0,
    start_date: '',
    end_date: '',
});

const modalTitle = computed(() => editingId.value ? 'Edit Campaign' : 'Add Campaign');

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'discount_type', label: 'Type' },
    { key: 'value', label: 'Value' },
    { key: 'start_date', label: 'Start Date', format: 'date' },
    { key: 'end_date', label: 'End Date', format: 'date' },
    { key: 'usage_count', label: 'Usage' },
];

const fetchCampaigns = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/campaigns', { params: { page } });
        campaigns.value = response.data.data || response.data;
        pagination.value = response.data;
    } catch (error) {
        appStore.error('Failed to fetch campaigns');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingId.value = null;
    form.value = { name: '', discount_type: 'percent', value: 0, start_date: '', end_date: '' };
    showModal.value = true;
};

const openEditModal = (item) => {
    editingId.value = item.id;
    form.value = {
        ...item,
        start_date: item.start_date ? item.start_date.split('T')[0] : '',
        end_date: item.end_date ? item.end_date.split('T')[0] : '',
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
    form.value = { name: '', discount_type: 'percent', value: 0, start_date: '', end_date: '' };
};

const handleSubmit = async () => {
    try {
        if (editingId.value) {
            await axios.put(`/api/campaigns/${editingId.value}`, form.value);
            appStore.success('Campaign updated successfully');
        } else {
            await axios.post('/api/campaigns', form.value);
            appStore.success('Campaign added successfully');
        }
        closeModal();
        fetchCampaigns();
    } catch (error) {
        appStore.error('Failed to save campaign');
    }
};

const handleDelete = async (item) => {
    if (!confirm('Are you sure you want to delete this campaign?')) return;
    
    try {
        await axios.delete(`/api/campaigns/${item.id}`);
        appStore.success('Campaign deleted successfully');
        fetchCampaigns();
    } catch (error) {
        appStore.error('Failed to delete campaign');
    }
};

const handlePageChange = (page) => {
    fetchCampaigns(page);
};

onMounted(() => {
    fetchCampaigns();
});
</script>

