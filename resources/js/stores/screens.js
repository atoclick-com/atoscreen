import { defineStore } from 'pinia';
import api from '@/api/client';
import { useToastStore } from './toast';

export const useScreensStore = defineStore('screens', {
    state: () => ({
        screens: [],
        currentScreen: null,
        loading: false,
        saving: false,
        error: null,
    }),

    getters: {
        totalScreens: (state) => state.screens.length,
        onlineScreens: (state) => state.screens.filter(s => s.is_online).length,
        totalActiveSlides: (state) => state.screens.reduce((acc, s) => acc + (s.active_slides_count || 0), 0),
    },

    actions: {
        async fetchScreens() {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/screens');
                this.screens = response.data.screens;
                return this.screens;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch screens';
                useToastStore().error('Error', this.error);
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async fetchScreen(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get(`/screens/${id}`);
                this.currentScreen = response.data.screen;
                return this.currentScreen;
            } catch (err) {
                this.error = err.response?.data?.message || 'Screen not found';
                useToastStore().error('Error', this.error);
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async createScreen(payload) {
            this.saving = true;
            try {
                const response = await api.post('/screens', payload);
                useToastStore().success('Screen Created', 'Your new TV screen has been registered.');
                await this.fetchScreens();
                return response.data.screen;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to create screen';
                useToastStore().error('Creation Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async updateScreen(id, payload) {
            this.saving = true;
            try {
                const response = await api.put(`/screens/${id}`, payload);
                if (this.currentScreen && this.currentScreen.id === id) {
                    this.currentScreen = response.data.screen;
                }
                useToastStore().success('Updated', 'Screen properties updated successfully.');
                return response.data.screen;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to update screen';
                useToastStore().error('Update Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async deleteScreen(id) {
            this.saving = true;
            try {
                await api.delete(`/screens/${id}`);
                this.screens = this.screens.filter(s => s.id !== id);
                if (this.currentScreen && this.currentScreen.id === id) {
                    this.currentScreen = null;
                }
                useToastStore().success('Deleted', 'Screen removed successfully.');
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to delete screen';
                useToastStore().error('Delete Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async regenerateUrl(id) {
            this.saving = true;
            try {
                const response = await api.post(`/screens/${id}/regenerate-url`);
                this.currentScreen = response.data.screen;
                useToastStore().success('URL Regenerated', 'Old TV link invalidated. New link is ready.');
                return response.data;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to regenerate URL';
                useToastStore().error('Action Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async resetScreen(id) {
            this.saving = true;
            try {
                const response = await api.post(`/screens/${id}/reset`);
                this.currentScreen = response.data.screen;
                useToastStore().success('Screen Reset', 'All slides cleared and defaults restored.');
                return response.data.screen;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to reset screen';
                useToastStore().error('Reset Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        // Slide operations
        async uploadSlide(screenId, payload) {
            this.saving = true;
            try {
                const isForm = payload instanceof FormData;
                const config = isForm ? { headers: { 'Content-Type': 'multipart/form-data' } } : {};
                const response = await api.post(`/screens/${screenId}/slides`, payload, config);
                if (this.currentScreen) {
                    this.currentScreen.slides.push(response.data.slide);
                }
                useToastStore().success('Slide Added', response.data.message);
                return response.data.slide;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to add slide';
                useToastStore().error('Upload Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async batchUpload(screenId, formData) {
            this.saving = true;
            try {
                const response = await api.post(`/screens/${screenId}/slides/batch-upload`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                if (this.currentScreen) {
                    this.currentScreen.slides.push(...response.data.slides);
                }
                useToastStore().success('Batch Uploaded', response.data.message);
                return response.data.slides;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to batch upload';
                useToastStore().error('Upload Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async updateSlide(slideId, payload) {
            this.saving = true;
            try {
                let response;
                if (payload instanceof FormData) {
                    payload.append('_method', 'PUT');
                    response = await api.post(`/slides/${slideId}`, payload, {
                        headers: { 'Content-Type': 'multipart/form-data' },
                    });
                } else {
                    response = await api.put(`/slides/${slideId}`, payload);
                }

                if (this.currentScreen) {
                    const idx = this.currentScreen.slides.findIndex(s => s.id === slideId);
                    if (idx !== -1) {
                        this.currentScreen.slides[idx] = response.data.slide;
                    }
                }
                useToastStore().success('Saved', 'Slide updated.');
                return response.data.slide;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to update slide';
                useToastStore().error('Error', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async toggleSlideActive(slideId) {
            try {
                const response = await api.post(`/slides/${slideId}/toggle-active`);
                if (this.currentScreen) {
                    const slide = this.currentScreen.slides.find(s => s.id === slideId);
                    if (slide) {
                        slide.active = response.data.slide.active;
                    }
                }
                useToastStore().info(
                    response.data.slide.active ? 'Slide Activated' : 'Slide Paused',
                    'Display playlist will update within 60s'
                );
                return response.data.slide;
            } catch (err) {
                useToastStore().error('Toggle Failed', err.response?.data?.message || 'Error');
                throw err;
            }
        },

        async reorderSlides(screenId, slideIds) {
            try {
                const response = await api.post(`/screens/${screenId}/slides/reorder`, { slide_ids: slideIds });
                if (this.currentScreen) {
                    this.currentScreen.slides = response.data.slides;
                }
                useToastStore().success('Reordered', 'Playlist order updated.');
            } catch (err) {
                useToastStore().error('Reorder Failed', err.response?.data?.message || 'Error');
            }
        },

        async batchUpdateFitMode(screenId, fitMode) {
            this.saving = true;
            try {
                const response = await api.post(`/screens/${screenId}/slides/batch-fit-mode`, { fit_mode: fitMode });
                if (this.currentScreen) {
                    this.currentScreen.slides = response.data.slides;
                    if (this.currentScreen.settings) {
                        this.currentScreen.settings.aspect_ratio_mode = fitMode;
                    }
                }
                const label = fitMode === 'cover' ? 'Full Screen (Edge-to-Edge)' : (fitMode === 'contain' ? 'Fit Screen (Letterbox)' : 'Boxed Card');
                useToastStore().success('Updated All Slides', `All playlist slides set to ${label}.`);
                return response.data.slides;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to update slides display mode';
                useToastStore().error('Update Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async deleteSlide(slideId) {
            this.saving = true;
            try {
                await api.delete(`/slides/${slideId}`);
                if (this.currentScreen) {
                    this.currentScreen.slides = this.currentScreen.slides.filter(s => s.id !== slideId);
                }
                useToastStore().success('Deleted', 'Slide removed from screen.');
            } catch (err) {
                useToastStore().error('Delete Failed', err.response?.data?.message || 'Error');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        // Settings operations
        async saveScreenSettings(screenId, screenPayload, settingsPayload) {
            this.saving = true;
            try {
                const response = await api.put(`/screens/${screenId}`, {
                    ...screenPayload,
                    settings: settingsPayload,
                });
                if (this.currentScreen && this.currentScreen.id === screenId) {
                    this.currentScreen = response.data.screen;
                }
                useToastStore().success('Settings Saved', 'Screen properties and display settings saved successfully.');
                return response.data.screen;
            } catch (err) {
                const msg = err.response?.data?.message || 'Failed to save settings';
                useToastStore().error('Save Failed', msg);
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async updateSettings(screenId, payload) {
            this.saving = true;
            try {
                const response = await api.post(`/screens/${screenId}/settings`, payload);
                if (this.currentScreen) {
                    this.currentScreen.settings = response.data.settings;
                }
                useToastStore().success('Settings Saved', 'Display settings updated.');
                return response.data.settings;
            } catch (err) {
                useToastStore().error('Save Failed', err.response?.data?.message || 'Error');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async uploadLogo(screenId, formData) {
            this.saving = true;
            try {
                const response = await api.post(`/screens/${screenId}/settings/logo`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                if (this.currentScreen) {
                    this.currentScreen.settings = response.data.settings;
                }
                useToastStore().success('Logo Uploaded', 'Screen branding logo updated.');
                return response.data.settings;
            } catch (err) {
                useToastStore().error('Upload Failed', err.response?.data?.message || 'Error');
                throw err;
            } finally {
                this.saving = false;
            }
        },

        async removeLogo(screenId) {
            this.saving = true;
            try {
                const response = await api.delete(`/screens/${screenId}/settings/logo`);
                if (this.currentScreen) {
                    this.currentScreen.settings = response.data.settings;
                }
                useToastStore().info('Logo Removed', 'Screen will not display overlay logo.');
            } catch (err) {
                useToastStore().error('Error', err.response?.data?.message || 'Failed to remove logo');
            } finally {
                this.saving = false;
            }
        },
    },
});
