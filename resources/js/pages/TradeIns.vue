<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Trade-Ins</h1>
        </div>

        <div class="mb-4 flex gap-4">
            <select v-model="filters.status" @change="fetchTradeIns" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="waiting_device">Waiting Device</option>
                <option value="completed">Completed</option>
                <option value="canceled">Canceled</option>
            </select>
            <select v-model="filters.condition" @change="fetchTradeIns" class="rounded-md border-gray-300 shadow-sm">
                <option value="">All Conditions</option>
                <option value="A">A - Mükemmel</option>
                <option value="B">B - İyi</option>
                <option value="C">C - Orta</option>
            </select>
        </div>

        <DataTable
            :columns="columns"
            :data="tradeIns"
            :loading="loading"
            :pagination="pagination"
            :actions="['view']"
            @view="openDetailModal"
            @page-change="handlePageChange"
        />

        <SideModal :show="showDetailModal" :title="selectedTradeIn ? `Trade-In #${selectedTradeIn.id.substring(0, 8)}` : ''" @close="showDetailModal = false">
            <div v-if="selectedTradeIn" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-1 flex items-center gap-2">
                        <select
                            v-model="selectedTradeIn.status"
                            @change="updateStatus"
                            class="rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="new">New</option>
                            <option value="waiting_device">Waiting Device</option>
                            <option value="completed">Completed</option>
                            <option value="canceled">Canceled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Customer</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTradeIn.customer?.name || selectedTradeIn.customer?.phone_number || 'N/A' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Brand</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTradeIn.brand }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Model</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTradeIn.model }}</p>
                </div>

                <div v-if="selectedTradeIn.storage">
                    <label class="block text-sm font-medium text-gray-700">Storage</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTradeIn.storage }}</p>
                </div>

                <div v-if="selectedTradeIn.color">
                    <label class="block text-sm font-medium text-gray-700">Color</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTradeIn.color }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Condition</label>
                    <span :class="conditionClasses[selectedTradeIn.condition]" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                        {{ selectedTradeIn.condition }} - {{ conditionLabels[selectedTradeIn.condition] }}
                    </span>
                </div>

                <div v-if="selectedTradeIn.battery_health !== null">
                    <label class="block text-sm font-medium text-gray-700">Battery Health</label>
                    <p class="mt-1 text-sm text-gray-900">%{{ selectedTradeIn.battery_health }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Auto-Calculated Price</label>
                    <div v-if="pricePreview" class="mt-1 p-2 bg-gray-50 rounded-md">
                        <p class="text-sm text-gray-600">
                            Base: {{ pricePreview.base_price }} TL | 
                            Calculated: {{ pricePreview.calculated }} TL | 
                            Range: {{ pricePreview.min }} - {{ pricePreview.max }} TL
                        </p>
                    </div>
                    <button
                        @click="calculatePricePreview"
                        class="mt-2 px-3 py-1 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700"
                    >
                        Recalculate
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Price Offer Range</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            v-model.number="priceForm.offer_min"
                            type="number"
                            placeholder="Min"
                            class="rounded-md border-gray-300 shadow-sm w-32"
                        />
                        <span>-</span>
                        <input
                            v-model.number="priceForm.offer_max"
                            type="number"
                            placeholder="Max"
                            class="rounded-md border-gray-300 shadow-sm w-32"
                        />
                        <button
                            @click="updatePrice"
                            class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                        >
                            Update Range
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Final Price (Manual Override)</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            v-model.number="priceForm.final_price"
                            type="number"
                            placeholder="Final price"
                            class="rounded-md border-gray-300 shadow-sm w-40"
                        />
                        <button
                            @click="updateFinalPrice"
                            class="px-3 py-1 text-sm bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            Set Final Price
                        </button>
                        <button
                            v-if="selectedTradeIn?.final_price"
                            @click="clearFinalPrice"
                            class="px-3 py-1 text-sm bg-red-600 text-white rounded-md hover:bg-red-700"
                        >
                            Clear Override
                        </button>
                    </div>
                    <p v-if="selectedTradeIn?.final_price" class="mt-1 text-sm text-green-600">
                        Final Price: {{ selectedTradeIn.final_price }} TL (Manual Override)
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Payment Option</label>
                    <div class="mt-1">
                        <select
                            v-model="selectedTradeIn.payment_option"
                            @change="updatePaymentOption"
                            class="rounded-md border-gray-300 shadow-sm w-full"
                        >
                            <option :value="null">Not Selected</option>
                            <option value="cash">Nakit</option>
                            <option value="voucher">Aksesuar Hediye Çeki</option>
                            <option value="tradein">Yeni cihazda indirim</option>
                        </select>
                        <p v-if="recommendedPaymentOption" class="mt-1 text-sm text-blue-600">
                            💡 Recommended: {{ getPaymentOptionLabel(recommendedPaymentOption) }}
                        </p>
                    </div>
                </div>

                <div v-if="selectedTradeIn.store">
                    <label class="block text-sm font-medium text-gray-700">Store</label>
                    <p class="mt-1 text-sm text-gray-900">{{ selectedTradeIn.store.name }}</p>
                </div>

                <div v-if="selectedTradeIn.photos && selectedTradeIn.photos.length > 0">
                    <label class="block text-sm font-medium text-gray-700">Photos</label>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <img
                            v-for="photo in selectedTradeIn.photos"
                            :key="photo"
                            :src="`/storage/${photo}`"
                            alt="Trade-in photo"
                            class="rounded-lg"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Created At</label>
                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(selectedTradeIn.created_at) }}</p>
                </div>
            </div>
        </SideModal>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import DataTable from '../components/DataTable.vue';
import SideModal from '../components/SideModal.vue';
import axios from '../config/axios';
import { useAppStore } from '../stores/app';

const appStore = useAppStore();

const tradeIns = ref([]);
const loading = ref(false);
const pagination = ref(null);
const showDetailModal = ref(false);
const selectedTradeIn = ref(null);

const filters = ref({
    status: '',
    condition: '',
});

const priceForm = ref({
    offer_min: null,
    offer_max: null,
    final_price: null,
});

const pricePreview = ref(null);
const recommendedPaymentOption = ref(null);

const conditionLabels = {
    A: 'Mükemmel',
    B: 'İyi',
    C: 'Orta',
};

const conditionClasses = {
    A: 'bg-green-100 text-green-800',
    B: 'bg-yellow-100 text-yellow-800',
    C: 'bg-orange-100 text-orange-800',
};

const statusClasses = {
    new: 'bg-blue-100 text-blue-800',
    waiting_device: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    canceled: 'bg-red-100 text-red-800',
};

const columns = [
    { key: 'id', label: 'ID', formatter: (val) => val.substring(0, 8) },
    { key: 'customer', label: 'Customer', formatter: (val) => val?.name || val?.phone_number || 'N/A' },
    { key: 'brand', label: 'Brand' },
    { key: 'model', label: 'Model' },
    { key: 'condition', label: 'Condition', formatter: (val) => `${val} - ${conditionLabels[val]}` },
    {
        key: 'status',
        label: 'Status',
        formatter: (val) => val,
    },
    {
        key: 'offer_max',
        label: 'Offer',
        formatter: (val, row) => {
            if (row.final_price) {
                return `${row.final_price} TL (Final)`;
            }
            if (row.offer_min && row.offer_max) {
                return `${row.offer_min} - ${row.offer_max} TL`;
            }
            return 'N/A';
        },
    },
    { key: 'created_at', label: 'Created', formatter: formatDate },
];

function formatDate(date) {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('tr-TR');
}

async function fetchTradeIns(page = 1) {
    loading.value = true;
    try {
        const params = new URLSearchParams({
            page: page.toString(),
            ...filters.value,
        });
        const response = await axios.get(`/api/tradeins?${params}`);
        tradeIns.value = response.data.data || [];
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (error) {
        console.error('Error fetching trade-ins:', error);
        appStore.showToast('Error fetching trade-ins', 'error');
    } finally {
        loading.value = false;
    }
}

function handlePageChange(page) {
    fetchTradeIns(page);
}

async function openDetailModal(tradeIn) {
    try {
        const response = await axios.get(`/api/tradeins/${tradeIn.id}`);
        selectedTradeIn.value = response.data;
        recommendedPaymentOption.value = response.data.recommended_payment_option;
        priceForm.value = {
            offer_min: response.data.offer_min,
            offer_max: response.data.offer_max,
            final_price: response.data.final_price,
        };
        showDetailModal.value = true;
        await calculatePricePreview();
    } catch (error) {
        console.error('Error fetching trade-in details:', error);
        appStore.showToast('Error fetching trade-in details', 'error');
    }
}

async function calculatePricePreview() {
    if (!selectedTradeIn.value) return;

    try {
        const response = await axios.get(`/api/tradeins/${selectedTradeIn.value.id}/price-preview`);
        pricePreview.value = response.data;
    } catch (error) {
        console.error('Error calculating price preview:', error);
        appStore.showToast('Error calculating price preview', 'error');
    }
}

async function updateFinalPrice() {
    if (!selectedTradeIn.value || !priceForm.value.final_price) return;

    try {
        await axios.patch(`/api/tradeins/${selectedTradeIn.value.id}/final-price`, {
            final_price: priceForm.value.final_price,
        });
        selectedTradeIn.value.final_price = priceForm.value.final_price;
        appStore.showToast('Final price updated successfully', 'success');
        fetchTradeIns(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error updating final price:', error);
        appStore.showToast('Error updating final price', 'error');
    }
}

async function clearFinalPrice() {
    if (!selectedTradeIn.value) return;

    try {
        await axios.patch(`/api/tradeins/${selectedTradeIn.value.id}/final-price`, {
            final_price: null,
        });
        selectedTradeIn.value.final_price = null;
        priceForm.value.final_price = null;
        appStore.showToast('Final price override cleared', 'success');
        fetchTradeIns(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error clearing final price:', error);
        appStore.showToast('Error clearing final price', 'error');
    }
}

async function updatePaymentOption() {
    if (!selectedTradeIn.value) return;

    try {
        await axios.patch(`/api/tradeins/${selectedTradeIn.value.id}`, {
            payment_option: selectedTradeIn.value.payment_option,
        });
        appStore.showToast('Payment option updated successfully', 'success');
        fetchTradeIns(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error updating payment option:', error);
        appStore.showToast('Error updating payment option', 'error');
    }
}

function getPaymentOptionLabel(option) {
    const labels = {
        cash: 'Nakit',
        voucher: 'Aksesuar Hediye Çeki',
        tradein: 'Yeni cihazda indirim',
    };
    return labels[option] || option;
}

async function updateStatus() {
    if (!selectedTradeIn.value) return;

    try {
        await axios.patch(`/api/tradeins/${selectedTradeIn.value.id}/status`, {
            status: selectedTradeIn.value.status,
        });
        appStore.showToast('Status updated successfully', 'success');
        fetchTradeIns(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error updating status:', error);
        appStore.showToast('Error updating status', 'error');
    }
}

async function updatePrice() {
    if (!selectedTradeIn.value) return;

    try {
        await axios.patch(`/api/tradeins/${selectedTradeIn.value.id}/price`, {
            offer_min: priceForm.value.offer_min,
            offer_max: priceForm.value.offer_max,
        });
        selectedTradeIn.value.offer_min = priceForm.value.offer_min;
        selectedTradeIn.value.offer_max = priceForm.value.offer_max;
        appStore.showToast('Price updated successfully', 'success');
        fetchTradeIns(pagination.value?.current_page || 1);
    } catch (error) {
        console.error('Error updating price:', error);
        appStore.showToast('Error updating price', 'error');
    }
}

watch(showDetailModal, (newVal) => {
    if (!newVal) {
        selectedTradeIn.value = null;
        priceForm.value = { offer_min: null, offer_max: null, final_price: null };
        pricePreview.value = null;
        recommendedPaymentOption.value = null;
    }
});

onMounted(() => {
    fetchTradeIns();
});
</script>

