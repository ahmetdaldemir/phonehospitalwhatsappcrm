import { defineStore } from 'pinia';

export const useAppStore = defineStore('app', {
    state: () => ({
        sidebarOpen: true,
        loading: false,
        toasts: [],
    }),

    actions: {
        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        setLoading(loading) {
            this.loading = loading;
        },

        addToast(message, type = 'info') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            
            setTimeout(() => {
                this.removeToast(id);
            }, 5000);
        },

        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }
        },

        success(message) {
            this.addToast(message, 'success');
        },

        error(message) {
            this.addToast(message, 'error');
        },

        info(message) {
            this.addToast(message, 'info');
        },

        warning(message) {
            this.addToast(message, 'warning');
        },
    },
});

