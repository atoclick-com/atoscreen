<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAppSettingsStore } from '@/stores/appSettings';
import {
    Tv,
    LayoutDashboard,
    Sun,
    Moon,
    LogOut,
    Sparkles,
    User,
    ChevronDown,
    Settings,
} from 'lucide-vue-next';

const authStore = useAuthStore();
const appSettingsStore = useAppSettingsStore();
const router = useRouter();
const route = useRoute();

const showUserMenu = ref(false);

onMounted(() => {
    if (!appSettingsStore.settings?.app_name) {
        appSettingsStore.fetchSettings().catch(() => {});
    }
});

const handleLogout = async () => {
    await authStore.logout();
    router.push({ name: 'login' });
};
</script>

<template>
    <header class="sticky top-0 z-40 w-full border-b border-zinc-800/80 bg-zinc-950/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Brand / Logo -->
            <div class="flex items-center gap-6">
                <router-link to="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-400 flex items-center justify-center text-zinc-950 font-bold shadow-lg shadow-amber-500/20 group-hover:scale-105 transition-transform overflow-hidden">
                        <img
                            v-if="appSettingsStore.settings?.logo_url"
                            :src="appSettingsStore.settings.logo_url"
                            alt="Logo"
                            class="w-full h-full object-contain p-1"
                        />
                        <Tv v-else class="w-5 h-5 text-zinc-950" />
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-base tracking-tight text-zinc-100">{{ appSettingsStore.settings?.business_name || 'AtoFood' }}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded-md font-mono font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">Signage</span>
                        </div>
                        <p class="text-[11px] text-zinc-500 hidden sm:block">{{ appSettingsStore.settings?.app_name || 'Smart TV Display Control' }}</p>
                    </div>
                </router-link>

                <!-- Navigation Tabs -->
                <nav class="hidden md:flex items-center gap-1 ml-4 pl-4 border-l border-zinc-800">
                    <router-link
                        to="/"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="route.name === 'dashboard' ? 'bg-zinc-800/80 text-zinc-100' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                    >
                        <LayoutDashboard class="w-4 h-4" />
                        Screens
                    </router-link>

                    <router-link
                        to="/settings"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                        :class="route.name === 'app-settings' ? 'bg-zinc-800/80 text-zinc-100' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900'"
                    >
                        <Settings class="w-4 h-4" />
                        Settings
                    </router-link>

                    <router-link
                        to="/wizard"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors border border-amber-500/20 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20"
                        :class="route.name === 'setup-wizard' ? 'ring-1 ring-amber-400' : ''"
                    >
                        <Sparkles class="w-3.5 h-3.5 text-amber-400 animate-pulse" />
                        <span>Setup Wizard</span>
                    </router-link>
                </nav>
            </div>

            <!-- Right Controls: Theme + Profile -->
            <div class="flex items-center gap-3">
                <!-- Dark Mode Toggle -->
                <button
                    @click="authStore.toggleDarkMode()"
                    class="p-2 rounded-lg text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900 border border-zinc-800/80 transition-colors cursor-pointer"
                    :title="authStore.darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    <Sun v-if="authStore.darkMode" class="w-4 h-4 text-amber-400" />
                    <Moon v-else class="w-4 h-4 text-zinc-300" />
                </button>

                <!-- User Profile & Dropdown -->
                <div class="relative">
                    <button
                        @click="showUserMenu = !showUserMenu"
                        class="flex items-center gap-2.5 p-1.5 pl-2.5 rounded-xl border border-zinc-800/80 bg-zinc-900/60 hover:bg-zinc-900 transition-colors cursor-pointer"
                    >
                        <div class="w-7 h-7 rounded-lg bg-zinc-800 flex items-center justify-center text-amber-400 font-semibold text-xs border border-zinc-700/50">
                            {{ authStore.user?.name?.charAt(0) || 'A' }}
                        </div>
                        <span class="text-xs font-medium text-zinc-200 hidden sm:inline-block max-w-[120px] truncate">
                            {{ authStore.user?.name || 'Manager' }}
                        </span>
                        <ChevronDown class="w-3.5 h-3.5 text-zinc-500" />
                    </button>

                    <!-- Dropdown Content -->
                    <Transition
                        enter-active-class="transition ease-out duration-100"
                        enter-from-class="transform opacity-0 scale-95"
                        enter-to-class="transform opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-75"
                        leave-from-class="transform opacity-100 scale-100"
                        leave-to-class="transform opacity-0 scale-95"
                    >
                        <div
                            v-if="showUserMenu"
                            @click="showUserMenu = false"
                            class="absolute right-0 mt-2 w-56 rounded-xl bg-zinc-900 border border-zinc-800 shadow-2xl py-1.5 z-50 divide-y divide-zinc-800/80"
                        >
                            <div class="px-4 py-2.5">
                                <p class="text-xs font-semibold text-zinc-200">{{ authStore.user?.name }}</p>
                                <p class="text-[11px] text-zinc-400 truncate">{{ authStore.user?.email }}</p>
                                <div class="mt-1 inline-flex items-center gap-1 text-[10px] font-mono px-1.5 py-0.5 rounded bg-zinc-800 text-zinc-400">
                                    Role: {{ authStore.user?.role || 'Admin' }}
                                </div>
                            </div>
                            <div class="py-1">
                                <router-link
                                    to="/settings"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-zinc-300 hover:bg-zinc-800 hover:text-zinc-100 transition-colors cursor-pointer text-left"
                                >
                                    <Settings class="w-3.5 h-3.5 text-amber-400" />
                                    App & Access Settings
                                </router-link>

                                <router-link
                                    to="/wizard"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-amber-400 hover:bg-zinc-800 transition-colors cursor-pointer text-left"
                                >
                                    <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                                    Setup Wizard
                                </router-link>

                                <button
                                    @click="handleLogout"
                                    class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer text-left"
                                >
                                    <LogOut class="w-3.5 h-3.5" />
                                    Sign Out
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>
