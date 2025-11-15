import { defineStore } from 'pinia';
import axios from '../config/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('auth_token') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token && !!state.user,
        isAdmin: (state) => state.user?.role === 'admin',
        isStoreUser: (state) => state.user?.role === 'store',
        userStoreId: (state) => state.user?.store_id,
    },

    actions: {
        async login(credentials) {
            try {
                const response = await axios.post('/api/auth/login', credentials);
                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('auth_token', this.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                return { success: true };
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.message || 'Login failed',
                };
            }
        },

        async logout() {
            try {
                await axios.post('/api/auth/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('auth_token');
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        async fetchUser() {
            try {
                const response = await axios.get('/api/auth/user');
                this.user = response.data;
                return { success: true };
            } catch (error) {
                this.logout();
                return { success: false };
            }
        },

        initializeAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                this.fetchUser();
            }
        },
    },
});

