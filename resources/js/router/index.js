import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/Login.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../pages/Dashboard.vue'),
            },
            {
                path: '/tickets',
                name: 'tickets',
                component: () => import('../pages/Tickets.vue'),
            },
            {
                path: '/customers',
                name: 'customers',
                component: () => import('../pages/Customers.vue'),
            },
            {
                path: '/price-matrix',
                name: 'price-matrix',
                component: () => import('../pages/PriceMatrix.vue'),
            },
            {
                path: '/stores',
                name: 'stores',
                component: () => import('../pages/Stores.vue'),
            },
            {
                path: '/campaigns',
                name: 'campaigns',
                component: () => import('../pages/Campaigns.vue'),
            },
            {
                path: '/tradeins',
                name: 'tradeins',
                component: () => import('../pages/TradeIns.vue'),
            },
            {
                path: '/tradein-base-prices',
                name: 'tradein-base-prices',
                component: () => import('../pages/TradeInBasePrices.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.name === 'login' && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;

