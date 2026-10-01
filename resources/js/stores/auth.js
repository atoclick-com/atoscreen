import { defineStore } from 'pinia';
import api from '@/api/client';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('atofood_user') || 'null'),
        token: localStorage.getItem('atofood_token') || null,
        loading: false,
        darkMode: localStorage.getItem('atofood_dark_mode') !== 'false',
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(email, password) {
            this.loading = true;
            try {
                const response = await api.post('/auth/login', { email, password });
                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('atofood_token', this.token);
                localStorage.setItem('atofood_user', JSON.stringify(this.user));
                return response.data;
            } finally {
                this.loading = false;
            }
        },

        async register(payload) {
            this.loading = true;
            try {
                const response = await api.post('/auth/register', payload);
                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('atofood_token', this.token);
                localStorage.setItem('atofood_user', JSON.stringify(this.user));
                return response.data;
            } finally {
                this.loading = false;
            }
        },

        async fetchUser() {
            if (!this.token) return null;
            try {
                const response = await api.get('/auth/me');
                this.user = response.data.user;
                localStorage.setItem('atofood_user', JSON.stringify(this.user));
                return this.user;
            } catch (err) {
                this.logout();
                return null;
            }
        },

        async updateProfile(payload) {
            const response = await api.post('/user/profile', payload);
            this.user = response.data.user;
            localStorage.setItem('atofood_user', JSON.stringify(this.user));
            return response.data;
        },

        async updatePassword(payload) {
            const response = await api.post('/user/password', payload);
            return response.data;
        },

        async logout() {
            try {
                if (this.token) {
                    await api.post('/auth/logout');
                }
            } catch (e) {
                // Ignore failure on logout
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('atofood_token');
                localStorage.removeItem('atofood_user');
            }
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('atofood_dark_mode', this.darkMode.toString());
            this.applyDarkMode();
        },

        applyDarkMode() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
    },
});
