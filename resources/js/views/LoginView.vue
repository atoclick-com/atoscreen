<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAppSettingsStore } from '@/stores/appSettings';
import { useToastStore } from '@/stores/toast';
import { Tv, Sparkles, ArrowRight, Lock, Mail } from 'lucide-vue-next';

const authStore = useAuthStore();
const appSettingsStore = useAppSettingsStore();
const toastStore = useToastStore();
const router = useRouter();
const route = useRoute();

const email = ref('admin@atofood.com');
const password = ref('password123');
const loading = ref(false);
const error = ref(null);

onMounted(() => {
    appSettingsStore.fetchSettings().catch(() => {});
});

const handleLogin = async () => {
    loading.value = true;
    error.value = null;
    try {
        await authStore.login(email.value, password.value);
        toastStore.success('Welcome back', `Signed in as ${authStore.user.name}`);
        const redirect = route.query.redirect || '/';
        router.push(redirect);
    } catch (err) {
        error.value = err.response?.data?.message || 'Invalid email or password';
        toastStore.error('Authentication Failed', error.value);
    } finally {
        loading.value = false;
    }
};

const fillDemo = () => {
    email.value = 'admin@atofood.com';
    password.value = 'password123';
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-zinc-950 p-4 relative overflow-hidden">
        <!-- Ambient Background Glow -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-amber-500/10 via-amber-600/5 to-transparent rounded-full blur-[140px] pointer-events-none" />

        <div class="relative w-full max-w-md">
            <!-- Header Brand -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-400 text-zinc-950 shadow-xl shadow-amber-500/20 mb-4 overflow-hidden">
                    <img
                        v-if="appSettingsStore.settings?.logo_url"
                        :src="appSettingsStore.settings.logo_url"
                        alt="Logo"
                        class="w-full h-full object-contain p-2"
                    />
                    <Tv v-else class="w-7 h-7" />
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight text-zinc-100 capitalize">
                    {{ appSettingsStore.settings?.app_name || 'trotiluxe' }}
                </h1>
                <p class="text-sm text-zinc-400 mt-1">
                    {{ appSettingsStore.settings?.business_name || 'Smart TV Digital Signage Management' }}
                </p>
            </div>

            <!-- Login Card -->
            <div class="rounded-3xl bg-zinc-900/80 border border-zinc-800 shadow-2xl backdrop-blur-xl p-8">
                <form @submit.prevent="handleLogin" class="space-y-5">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-500">
                                <Mail class="w-4 h-4" />
                            </div>
                            <input
                                v-model="email"
                                type="email"
                                required
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-zinc-950/60 border border-zinc-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-zinc-100 placeholder-zinc-500 text-sm transition-all outline-none"
                                placeholder="name@atofood.com"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-500">
                                <Lock class="w-4 h-4" />
                            </div>
                            <input
                                v-model="password"
                                type="password"
                                required
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-zinc-950/60 border border-zinc-800 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 text-zinc-100 placeholder-zinc-500 text-sm transition-all outline-none"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>

                    <div v-if="error" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-400">
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 active:scale-[0.99] text-zinc-950 font-bold text-sm transition-all shadow-lg shadow-amber-500/20 disabled:opacity-50 cursor-pointer"
                    >
                        <span v-if="!loading">Sign In to Dashboard</span>
                        <span v-else>Signing in...</span>
                        <ArrowRight v-if="!loading" class="w-4 h-4" />
                    </button>
                </form>

                <!-- Demo helper -->
                <div class="mt-6 pt-5 border-t border-zinc-800/80 flex items-center justify-between text-xs text-zinc-500">
                    <span>Default credentials:</span>
                    <button
                        @click="fillDemo"
                        type="button"
                        class="text-amber-400 hover:text-amber-300 font-medium underline underline-offset-4 cursor-pointer"
                    >
                        Auto-fill Admin
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
