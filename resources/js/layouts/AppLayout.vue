<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white shadow-sm">
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <button
                            @click="appStore.toggleSidebar()"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                        >
                            <Bars3Icon class="h-6 w-6" />
                        </button>
                        <div class="flex-shrink-0 flex items-center ml-4">
                            <h1 class="text-xl font-semibold text-gray-900">Phone Hospital CRM</h1>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="relative ml-3">
                                <div class="flex items-center space-x-4">
                                    <span class="text-sm text-gray-700">{{ authStore.user?.name }}</span>
                                    <button
                                        @click="handleLogout"
                                        class="text-sm text-gray-500 hover:text-gray-700"
                                    >
                                        Logout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex">
            <!-- Sidebar -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <aside
                    v-if="appStore.sidebarOpen"
                    class="w-64 bg-white shadow-sm min-h-screen"
                >
                    <nav class="mt-5 px-2 space-y-1">
                        <router-link
                            v-for="item in navigation"
                            :key="item.name"
                            :to="item.to"
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md"
                            :class="[
                                $route.name === item.name
                                    ? 'bg-indigo-100 text-indigo-900'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                            ]"
                        >
                            <component :is="item.icon" class="mr-3 h-6 w-6" />
                            {{ item.label }}
                        </router-link>
                    </nav>
                </aside>
            </Transition>

            <!-- Main content -->
            <main class="flex-1 p-6">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import {
    HomeIcon,
    UserGroupIcon,
    CurrencyDollarIcon,
    BuildingStorefrontIcon,
    DocumentTextIcon,
    SpeakerWaveIcon,
    Bars3Icon,
} from '@heroicons/vue/24/outline';
import { useAuthStore } from '../stores/auth';
import { useAppStore } from '../stores/app';

const router = useRouter();
const authStore = useAuthStore();
const appStore = useAppStore();

const navigation = computed(() => [
    { name: 'dashboard', to: '/', label: 'Dashboard', icon: HomeIcon },
    { name: 'tickets', to: '/tickets', label: 'Tickets', icon: DocumentTextIcon },
    { name: 'customers', to: '/customers', label: 'Customers', icon: UserGroupIcon },
    { name: 'price-matrix', to: '/price-matrix', label: 'Price Matrix', icon: CurrencyDollarIcon },
    { name: 'stores', to: '/stores', label: 'Stores', icon: BuildingStorefrontIcon },
    { name: 'campaigns', to: '/campaigns', label: 'Campaigns', icon: SpeakerWaveIcon },
]);

const handleLogout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>

