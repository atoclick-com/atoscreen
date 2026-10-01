import { defineStore } from 'pinia';

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [],
    }),
    actions: {
        add({ type = 'info', title = '', message = '', duration = 4000 }) {
            const id = Date.now() + Math.random().toString(36).substr(2, 4);
            const toast = { id, type, title, message };
            this.toasts.push(toast);

            if (duration > 0) {
                setTimeout(() => {
                    this.remove(id);
                }, duration);
            }
            return id;
        },
        success(title, message = '', duration = 3500) {
            return this.add({ type: 'success', title, message, duration });
        },
        error(title, message = '', duration = 5000) {
            return this.add({ type: 'error', title, message, duration });
        },
        warning(title, message = '', duration = 4000) {
            return this.add({ type: 'warning', title, message, duration });
        },
        info(title, message = '', duration = 3500) {
            return this.add({ type: 'info', title, message, duration });
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    },
});
