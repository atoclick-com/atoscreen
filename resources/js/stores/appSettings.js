import { defineStore } from 'pinia';
import api from '@/api/client';
import { useToastStore } from './toast';

export const useAppSettingsStore = defineStore('appSettings', {
    state: () => ({
        settings: {
            app_name: 'AtoFood Signage',
            business_name: 'Trotiluxe',
            display_domain: 'trotiluxe.ma',
            default_slide_duration: 10,
            default_transition: 'fade',
            default_orientation: 'landscape',
            operating_hours_enabled: false,
            opening_time: '08:00',
            closing_time: '23:00',
            instagram_handle: '@trotiluxe',
            contact_email: 'contact@trotiluxe.ma',
            contact_phone: '',
            master_pin: '1234',
            logo_path: null,
            logo_url: null,
        },
        systemInfo: null,
        loading: false,
        saving: false,
    }),

    actions: {
        async fetchSettings() {
            this.loading = true;
            try {
                const response = await api.get('/app-settings');
                this.settings = response.data.settings;
                this.systemInfo = response.data.system_info;

                if (this.settings?.app_name) {
                    localStorage.setItem('atofood_app_name', this.settings.app_name);
                }
                if (this.settings?.business_name) {
                    localStorage.setItem('atofood_business_name', this.settings.business_name);
                }

                return response.data;
            } catch (err) {
                console.error('Failed to fetch app settings:', err);
                throw err;
            } finally {
                this.loading = false;
            }
        },

        updateDocumentTitle(prefix = null) {
            const app = this.settings?.app_name || localStorage.getItem('atofood_app_name') || 'trotiluxe';
            const biz = this.settings?.business_name || localStorage.getItem('atofood_business_name') || 'Trotiluxe E-Bikes & Bistro';
            if (prefix) {
                document.title = `${prefix} | ${app}`;
            } else {
                document.title = `${app} | ${biz}`;
            }
        },

        async saveSettings(payload) {
            this.saving = true;
            const toast = useToastStore();
            try {
                const response = await api.post('/app-settings', payload);
                this.settings = response.data.settings;

                if (this.settings?.app_name) {
                    localStorage.setItem('atofood_app_name', this.settings.app_name);
                }
                if (this.settings?.business_name) {
                    localStorage.setItem('atofood_business_name', this.settings.business_name);
                }

                // Immediately update the current document title to reflect changes
                const app = this.settings?.app_name || 'trotiluxe';
                const cur = document.title;
                if (cur.includes('|')) {
                    const prefix = cur.split('|')[0].trim();
                    document.title = `${prefix} | ${app}`;
                } else {
                    document.title = app;
                }

                toast.success('Settings Saved', 'App information, title, and defaults have been updated.');
                return response.data;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to save settings.';
                toast.error('Save Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async uploadLogo(formData) {
            const toast = useToastStore();
            try {
                const response = await api.post('/app-settings/logo', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                this.settings = response.data.settings;
                toast.success('Logo Uploaded', 'Master brand logo updated.');
                return response.data;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to upload logo.';
                toast.error('Upload Failed', msg);
                throw err;
            }
        },

        async removeLogo() {
            const toast = useToastStore();
            try {
                const response = await api.delete('/app-settings/logo');
                this.settings = response.data.settings;
                toast.success('Logo Removed', 'Master brand logo removed.');
                return response.data;
            } catch (err) {
                toast.error('Remove Failed', 'Unable to remove logo.');
                throw err;
            }
        },
    },
});
