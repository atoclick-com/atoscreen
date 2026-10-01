<script setup>
import AdminNavbar from './AdminNavbar.vue';
import ToastContainer from '@/components/common/ToastContainer.vue';
import { useAppSettingsStore } from '@/stores/appSettings';

const appSettingsStore = useAppSettingsStore();
</script>

<template>
    <div class="min-h-screen flex flex-col bg-zinc-950 text-zinc-100 selection:bg-amber-500/30 selection:text-amber-200">
        <AdminNavbar />

        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <slot />
        </main>

        <footer class="border-t border-zinc-900 py-6 text-center text-xs text-zinc-600">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <p class="capitalize">{{ appSettingsStore.settings?.app_name || 'AtoFood Signage' }} Engine &copy; {{ new Date().getFullYear() }} • Enterprise Kiosk Control</p>
                <div class="flex items-center gap-4 text-zinc-500">
                    <span>1080p / 4K Certified</span>
                    <span>•</span>
                    <span>Zero-Flicker Preload</span>
                    <span>•</span>
                    <span>Offline Cache Enabled</span>
                </div>
            </div>
        </footer>

        <ToastContainer />
    </div>
</template>
