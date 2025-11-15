import axios from 'axios';
import { useAuthStore } from '../stores/auth';
import { useAppStore } from '../stores/app';

const axiosInstance = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor
axiosInstance.interceptors.request.use(
    (config) => {
        const authStore = useAuthStore();
        if (authStore.token) {
            config.headers.Authorization = `Bearer ${authStore.token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor
axiosInstance.interceptors.response.use(
    (response) => {
        return response;
    },
    async (error) => {
        const authStore = useAuthStore();
        const appStore = useAppStore();

        if (error.response?.status === 401) {
            await authStore.logout();
            window.location.href = '/login';
        }

        if (error.response?.status === 403) {
            appStore.error('You do not have permission to perform this action.');
        }

        if (error.response?.status >= 500) {
            appStore.error('Server error. Please try again later.');
        }

        return Promise.reject(error);
    }
);

export default axiosInstance;

