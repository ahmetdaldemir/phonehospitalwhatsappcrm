<template>
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Dashboard</h1>

        <!-- Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <DocumentTextIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Tickets</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_tickets || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <UserGroupIcon class="h-6 w-6 text-gray-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.total_customers || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CheckCircleIcon class="h-6 w-6 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.completed || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <ClockIcon class="h-6 w-6 text-yellow-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">New Tickets</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ stats.new || 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Tickets by Status</h2>
                <div class="h-64">
                    <DoughnutChart :data="statusChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Tickets Over Time</h2>
                <div class="h-64">
                    <LineChart :data="timeChartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                </div>
            </div>
        </div>

        <!-- Trade-In KPIs -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Trade-In Payment Options</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Conversion by Payment Option</h3>
                    <div class="h-64">
                        <BarChart :data="paymentConversionChartData" :options="{ responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }" />
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Revenue by Payment Option</h3>
                    <div class="h-64">
                        <BarChart :data="paymentRevenueChartData" :options="{ responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { DocumentTextIcon, UserGroupIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';
import { DoughnutChart, LineChart, BarChart } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Filler,
} from 'chart.js';
import axios from '../config/axios';

ChartJS.register(
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Filler
);

const stats = ref({});
const ticketStats = ref({});
const tradeInStats = ref({});

const statusChartData = computed(() => ({
    labels: ['New', 'Directed', 'Completed', 'Canceled'],
    datasets: [{
        data: [
            ticketStats.value.new || 0,
            ticketStats.value.directed || 0,
            ticketStats.value.completed || 0,
            ticketStats.value.canceled || 0,
        ],
        backgroundColor: ['#3B82F6', '#F59E0B', '#10B981', '#EF4444'],
    }],
}));

const timeChartData = computed(() => ({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [{
        label: 'Tickets',
        data: [12, 19, 15, 25, 22, 18, 14],
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
    }],
}));

const paymentConversionChartData = computed(() => ({
    labels: ['Nakit', 'Aksesuar Çeki', 'Yeni Cihaz İndirim'],
    datasets: [{
        label: 'Conversion Rate (%)',
        data: [
            tradeInStats.value.conversion_by_payment_option?.cash || 0,
            tradeInStats.value.conversion_by_payment_option?.voucher || 0,
            tradeInStats.value.conversion_by_payment_option?.tradein || 0,
        ],
        backgroundColor: ['#10B981', '#3B82F6', '#F59E0B'],
    }],
}));

const paymentRevenueChartData = computed(() => ({
    labels: ['Nakit', 'Aksesuar Çeki', 'Yeni Cihaz İndirim'],
    datasets: [{
        label: 'Revenue (TL)',
        data: [
            tradeInStats.value.revenue_by_payment_option?.cash || 0,
            tradeInStats.value.revenue_by_payment_option?.voucher || 0,
            tradeInStats.value.revenue_by_payment_option?.tradein || 0,
        ],
        backgroundColor: ['#10B981', '#3B82F6', '#F59E0B'],
    }],
}));

onMounted(async () => {
    try {
        const [ticketStatsRes, tradeInStatsRes] = await Promise.all([
            axios.get('/api/tickets/statistics'),
            axios.get('/api/tradeins/statistics'),
        ]);
        
        ticketStats.value = ticketStatsRes.data;
        tradeInStats.value = tradeInStatsRes.data;
        stats.value = {
            total_tickets: ticketStatsRes.data.total || 0,
            total_customers: 0, // Add API endpoint for this
            completed: ticketStatsRes.data.completed || 0,
            new: ticketStatsRes.data.new || 0,
        };
    } catch (error) {
        console.error('Error fetching dashboard data:', error);
    }
});
</script>

