<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import { useAppSettingsStore } from '@/stores/appSettings';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import {
    Sliders,
    Shield,
    Key,
    Lock,
    User,
    Globe,
    Tv,
    Clock,
    Instagram,
    Mail,
    Phone,
    Eye,
    EyeOff,
    Check,
    Copy,
    Save,
    ExternalLink,
    Upload,
    Trash2,
    Sparkles,
    Server,
    Smartphone,
    Info,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';

const appSettingsStore = useAppSettingsStore();
const authStore = useAuthStore();
const toastStore = useToastStore();

const activeTab = ref('app'); // 'app' | 'access'

// App Settings Form
const appForm = reactive({
    app_name: '',
    business_name: '',
    display_domain: 'trotiluxe.ma',
    default_slide_duration: 10,
    default_transition: 'fade',
    default_orientation: 'landscape',
    operating_hours_enabled: false,
    opening_time: '08:00',
    closing_time: '23:00',
    instagram_handle: '',
    contact_email: '',
    contact_phone: '',
    master_pin: '1234',
});

// Profile Form
const profileForm = reactive({
    name: '',
    email: '',
});
const savingProfile = ref(false);

// Password Form
const passwordForm = reactive({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});
const savingPassword = ref(false);
const showPasswords = ref(false);

// Logo Upload
const logoInput = ref(null);
const uploadingLogo = ref(false);

// Clipboard Helpers
const copiedShort = ref(false);
const copiedApi = ref(false);
const copiedLocal = ref(false);

const cleanDomain = computed(() => {
    return (appForm.display_domain || 'trotiluxe.ma').replace(/^https?:\/\//, '').replace(/\/+$/, '');
});

const sampleShortUrl = computed(() => {
    return `${cleanDomain.value}/v/1`;
});

const populateForms = () => {
    const s = appSettingsStore.settings || {};
    appForm.app_name = s.app_name || 'AtoFood Signage';
    appForm.business_name = s.business_name || 'Trotiluxe';
    appForm.display_domain = s.display_domain || 'trotiluxe.ma';
    appForm.default_slide_duration = s.default_slide_duration || 10;
    appForm.default_transition = s.default_transition || 'fade';
    appForm.default_orientation = s.default_orientation || 'landscape';
    appForm.operating_hours_enabled = !!s.operating_hours_enabled;
    appForm.opening_time = s.opening_time || '08:00';
    appForm.closing_time = s.closing_time || '23:00';
    appForm.instagram_handle = s.instagram_handle || '';
    appForm.contact_email = s.contact_email || '';
    appForm.contact_phone = s.contact_phone || '';
    appForm.master_pin = s.master_pin || '1234';

    if (authStore.user) {
        profileForm.name = authStore.user.name || '';
        profileForm.email = authStore.user.email || '';
    }

    appSettingsStore.updateDocumentTitle('Settings');
};

onMounted(async () => {
    await appSettingsStore.fetchSettings();
    await authStore.fetchUser();
    populateForms();
});

const handleSaveAppSettings = async () => {
    try {
        await appSettingsStore.saveSettings(appForm);
        appSettingsStore.updateDocumentTitle('Settings');
    } catch (e) {}
};

const handleUpdateProfile = async () => {
    if (!profileForm.name.trim() || !profileForm.email.trim()) {
        toastStore.error('Validation Error', 'Name and email are required.');
        return;
    }
    savingProfile.value = true;
    try {
        await authStore.updateProfile(profileForm);
        toastStore.success('Profile Updated', 'Your administrator profile details have been saved.');
    } catch (err) {
        const msg = err.response?.data?.message || 'Failed to update profile information.';
        toastStore.error('Update Failed', msg);
    } finally {
        savingProfile.value = false;
    }
};

const handleUpdatePassword = async () => {
    if (!passwordForm.current_password) {
        toastStore.error('Validation Error', 'Please enter your current password.');
        return;
    }
    if (passwordForm.new_password.length < 6) {
        toastStore.error('Weak Password', 'New password must be at least 6 characters long.');
        return;
    }
    if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
        toastStore.error('Password Mismatch', 'New password and confirmation do not match.');
        return;
    }

    savingPassword.value = true;
    try {
        await authStore.updatePassword(passwordForm);
        toastStore.success('Password Changed', 'Your login password has been changed successfully.');
        passwordForm.current_password = '';
        passwordForm.new_password = '';
        passwordForm.new_password_confirmation = '';
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.errors?.current_password?.[0] || 'Failed to change password.';
        toastStore.error('Error', msg);
    } finally {
        savingPassword.value = false;
    }
};

const handleLogoFile = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    uploadingLogo.value = true;
    try {
        const fd = new FormData();
        fd.append('logo', file);
        await appSettingsStore.uploadLogo(fd);
    } finally {
        uploadingLogo.value = false;
        e.target.value = '';
    }
};

const handleRemoveLogo = async () => {
    if (confirm('Remove master brand logo? Screens using global logo fallback will use the standard badge.')) {
        await appSettingsStore.removeLogo();
    }
};

const copyToClipboard = async (text, type) => {
    try {
        await navigator.clipboard.writeText(text);
        if (type === 'short') {
            copiedShort.value = true;
            setTimeout(() => (copiedShort.value = false), 2000);
        } else if (type === 'local') {
            copiedLocal.value = true;
            setTimeout(() => (copiedLocal.value = false), 2000);
        } else if (type === 'api') {
            copiedApi.value = true;
            setTimeout(() => (copiedApi.value = false), 2000);
        }
        toastStore.success('Copied', `${text} copied to clipboard.`);
    } catch (e) {
        toastStore.error('Copy Failed', 'Unable to copy text to clipboard.');
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-8 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-zinc-800">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-md border border-amber-500/20">
                            Control Center
                        </span>
                        <span class="text-xs text-zinc-500">Global System Management</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-100">
                        App & Access Settings
                    </h1>
                    <p class="text-xs sm:text-sm text-zinc-400 mt-1">
                        Configure application branding, default smart TV domains, operating hours, and administrator access credentials.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <router-link
                        to="/wizard"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-300 font-bold text-xs uppercase tracking-wider transition-all active:scale-95 cursor-pointer"
                    >
                        <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                        <span>Setup Wizard</span>
                    </router-link>

                    <button
                        v-if="activeTab === 'app'"
                        type="button"
                        :disabled="appSettingsStore.saving"
                        @click="handleSaveAppSettings"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ appSettingsStore.saving ? 'Saving...' : 'Save App Informations' }}</span>
                    </button>

                    <!-- Navigation Tabs -->
                    <div class="flex items-center p-1 rounded-xl bg-zinc-900 border border-zinc-800 shrink-0">
                        <button
                            type="button"
                            @click="activeTab = 'app'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all cursor-pointer"
                            :class="activeTab === 'app'
                                ? 'bg-amber-500 text-zinc-950 shadow-md shadow-amber-500/20'
                                : 'text-zinc-400 hover:text-zinc-200'"
                        >
                            <Sliders class="w-4 h-4" />
                            <span>App Informations</span>
                        </button>

                        <button
                            type="button"
                            @click="activeTab = 'access'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all cursor-pointer"
                            :class="activeTab === 'access'
                                ? 'bg-amber-500 text-zinc-950 shadow-md shadow-amber-500/20'
                                : 'text-zinc-400 hover:text-zinc-200'"
                        >
                            <Shield class="w-4 h-4" />
                            <span>Access & Security</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB 1: APPLICATION INFORMATIONS & DISPLAY DEFAULTS -->
            <div v-if="activeTab === 'app'" class="space-y-8">
                <!-- TV Short Link Hero Banner -->
                <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-500/10 via-zinc-900 to-zinc-900 border border-amber-500/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 shadow-xl">
                    <div class="space-y-2 max-w-xl">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-mono font-semibold">
                            <Tv class="w-3.5 h-3.5 text-amber-400" />
                            <span>Ultra-Short TV Access Link</span>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-100">
                            Smart TV 1-Number Links: <span class="text-amber-400 font-mono">{{ sampleShortUrl }}</span>
                        </h3>
                        <p class="text-xs text-zinc-400 leading-relaxed">
                            Your screens are accessed via clean, memorable links designed for TV remote typing. Changing your domain below updates all short links across the entire system.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0 w-full md:w-auto">
                        <button
                            type="button"
                            @click="copyToClipboard(`https://${sampleShortUrl}`, 'short')"
                            class="py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-amber-500/10 transition-transform active:scale-95 cursor-pointer"
                        >
                            <Check v-if="copiedShort" class="w-4 h-4" />
                            <Copy v-else class="w-4 h-4" />
                            <span>{{ copiedShort ? 'Copied Link!' : `Copy ${sampleShortUrl}` }}</span>
                        </button>

                        <a
                            :href="appSettingsStore.systemInfo?.local_display_url || '/v/1'"
                            target="_blank"
                            class="py-2.5 px-4 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors"
                        >
                            <span>Test Display on Localhost</span>
                            <ExternalLink class="w-3.5 h-3.5 text-amber-400" />
                        </a>
                    </div>
                </div>

                <form @submit.prevent="handleSaveAppSettings" class="space-y-8">
                    <!-- Grid: 2 Columns for Sections -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Card 1: Branding & Business Identity -->
                        <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-6">
                            <div class="flex items-center gap-2.5 pb-4 border-b border-zinc-800">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <Sparkles class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-zinc-100">Branding & Store Identity</h3>
                                    <p class="text-xs text-zinc-500">Name and contact details displayed on screens.</p>
                                </div>
                            </div>

                            <!-- Master Brand Logo -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                                    Master Brand Logo
                                </label>
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                                        <img
                                            v-if="appSettingsStore.settings?.logo_url"
                                            :src="appSettingsStore.settings.logo_url"
                                            alt="Master Brand Logo"
                                            class="w-full h-full object-contain p-2"
                                        />
                                        <Tv v-else class="w-8 h-8 text-zinc-700" />
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="file"
                                                ref="logoInput"
                                                @change="handleLogoFile"
                                                accept="image/*"
                                                class="hidden"
                                            />
                                            <button
                                                type="button"
                                                @click="logoInput?.click()"
                                                :disabled="uploadingLogo"
                                                class="py-2 px-3.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-200 transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                            >
                                                <Upload class="w-3.5 h-3.5 text-amber-400" />
                                                <span>{{ uploadingLogo ? 'Uploading...' : 'Upload Logo' }}</span>
                                            </button>
                                            <button
                                                v-if="appSettingsStore.settings?.logo_url"
                                                type="button"
                                                @click="handleRemoveLogo"
                                                class="py-2 px-3 rounded-xl bg-rose-950/30 hover:bg-rose-950/60 border border-rose-900/40 text-rose-300 text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                                <span>Remove</span>
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-zinc-500">
                                            PNG, SVG, or WEBP with transparent background recommended. Max 4MB.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- App Name & Business Name -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                        <span>Application & Browser Tab Title</span>
                                        <span class="text-[10px] text-amber-400 font-mono lowercase">tab: {{ appForm.app_name || 'trotiluxe' }}</span>
                                    </label>
                                    <input
                                        v-model="appForm.app_name"
                                        type="text"
                                        required
                                        placeholder="e.g. trotiluxe"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                    <p class="text-[11px] text-zinc-500 mt-1">
                                        Renames the browser tab, window title, and administration headers.
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Business / Store Brand
                                    </label>
                                    <input
                                        v-model="appForm.business_name"
                                        type="text"
                                        placeholder="e.g. Trotiluxe E-Bikes & Bistro"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                    <p class="text-[11px] text-zinc-500 mt-1">
                                        Displayed on smart TV standby screens and navigation bars.
                                    </p>
                                </div>
                            </div>

                            <!-- Instagram Handle & Contact -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Official Instagram Handle
                                    </label>
                                    <div class="relative flex items-center">
                                        <Instagram class="absolute left-3.5 w-4 h-4 text-pink-400 select-none" />
                                        <input
                                            v-model="appForm.instagram_handle"
                                            type="text"
                                            placeholder="@trotiluxe"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                            Public Email
                                        </label>
                                        <div class="relative flex items-center">
                                            <Mail class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                            <input
                                                v-model="appForm.contact_email"
                                                type="email"
                                                placeholder="contact@trotiluxe.ma"
                                                class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                            Contact Phone
                                        </label>
                                        <div class="relative flex items-center">
                                            <Phone class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                            <input
                                                v-model="appForm.contact_phone"
                                                type="text"
                                                placeholder="+212 600-000000"
                                                class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Smart TV Display Network & Short URL Domain -->
                        <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-6">
                            <div class="flex items-center gap-2.5 pb-4 border-b border-zinc-800">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <Globe class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-zinc-100">Smart TV Domain & Network</h3>
                                    <p class="text-xs text-zinc-500">Short URL routing and default player configuration.</p>
                                </div>
                            </div>

                            <!-- Display Domain Field -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                        Public Display Domain (Short URL)
                                    </label>
                                    <span class="text-xs font-mono text-amber-400 font-bold">/v/{number}</span>
                                </div>
                                <div class="relative flex items-center">
                                    <Globe class="absolute left-3.5 w-4 h-4 text-zinc-500 select-none" />
                                    <input
                                        v-model="appForm.display_domain"
                                        type="text"
                                        required
                                        placeholder="trotiluxe.ma"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm font-mono text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                </div>
                                <div class="mt-2 p-2.5 rounded-xl bg-zinc-950/80 border border-zinc-800 flex items-center justify-between text-xs font-mono">
                                    <span class="text-zinc-500">Sample Smart TV link:</span>
                                    <span class="text-amber-400 font-bold select-all">{{ sampleShortUrl }}</span>
                                </div>
                            </div>

                            <!-- Default Slide Timing -->
                            <div>
                                <div class="flex justify-between items-center mb-1.5">
                                    <label class="text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                        Default Slide Playback Duration
                                    </label>
                                    <span class="text-xs font-mono font-bold text-amber-400">{{ appForm.default_slide_duration }} seconds</span>
                                </div>
                                <input
                                    v-model.number="appForm.default_slide_duration"
                                    type="range"
                                    min="3"
                                    max="60"
                                    step="1"
                                    class="w-full accent-amber-500 cursor-pointer"
                                />
                                <div class="flex justify-between text-[10px] text-zinc-500 mt-1 font-mono">
                                    <span>3s (Fast)</span>
                                    <span>10s (Standard)</span>
                                    <span>60s (Slow)</span>
                                </div>
                            </div>

                            <!-- Transition & Orientation -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Default Transition
                                    </label>
                                    <select
                                        v-model="appForm.default_transition"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    >
                                        <option value="fade">Cross Fade (Cinematic)</option>
                                        <option value="slide">Horizontal Slide</option>
                                        <option value="zoom">Zoom Fade</option>
                                        <option value="none">Instant Switch (Cut)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Default Orientation
                                    </label>
                                    <select
                                        v-model="appForm.default_orientation"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    >
                                        <option value="landscape">Landscape (Horizontal 16:9)</option>
                                        <option value="portrait">Portrait (Vertical 9:16)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Master Screen PIN -->
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Master TV Screen Security PIN
                                </label>
                                <div class="relative flex items-center">
                                    <Lock class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                    <input
                                        v-model="appForm.master_pin"
                                        type="text"
                                        maxlength="6"
                                        placeholder="1234"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm font-mono text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                </div>
                                <p class="text-[10px] text-zinc-500 mt-1">
                                    Default PIN used on smart TV kiosk screens to unlock device controls.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Global Operating Schedule -->
                    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <Clock class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-zinc-100">Global Operating Hours & Eco Mode</h3>
                                    <p class="text-xs text-zinc-500">Automatically switch smart TVs to sleep / power saving outside business hours.</p>
                                </div>
                            </div>

                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="appForm.operating_hours_enabled"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-zinc-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                            </label>
                        </div>

                        <div v-if="appForm.operating_hours_enabled" class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Store Opening Time
                                </label>
                                <input
                                    v-model="appForm.opening_time"
                                    type="time"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm font-mono text-zinc-100 focus:border-amber-500 outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Store Closing Time
                                </label>
                                <input
                                    v-model="appForm.closing_time"
                                    type="time"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm font-mono text-zinc-100 focus:border-amber-500 outline-none"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Bar -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-800">
                        <button
                            type="submit"
                            :disabled="appSettingsStore.saving"
                            class="px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 active:scale-95 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ appSettingsStore.saving ? 'Saving...' : 'Save App Informations' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: ACCESS INFORMATIONS & SECURITY CREDENTIALS -->
            <div v-if="activeTab === 'access'" class="space-y-8">
                <!-- System & Access Summary Banner -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-amber-400">Account Access</span>
                            <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 font-mono text-[10px]">Active</span>
                        </div>
                        <div class="text-base font-bold text-zinc-100 truncate">
                            {{ authStore.user?.email || 'admin@atofood.com' }}
                        </div>
                        <p class="text-xs text-zinc-500">
                            Role: <span class="capitalize text-zinc-300 font-semibold">{{ authStore.user?.role || 'Admin' }}</span>
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-amber-400">Local Host Access</span>
                            <button
                                type="button"
                                @click="copyToClipboard('http://localhost:8000', 'local')"
                                class="text-zinc-500 hover:text-zinc-200 transition-colors cursor-pointer"
                                title="Copy Local URL"
                            >
                                <Check v-if="copiedLocal" class="w-3.5 h-3.5 text-emerald-400" />
                                <Copy v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <div class="text-base font-mono font-bold text-zinc-100">
                            http://localhost:8000
                        </div>
                        <p class="text-xs text-zinc-500">
                            Internal development & management portal.
                        </p>
                    </div>

                    <div class="p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-2">
                        <div class="flex items-center justify-between text-xs text-zinc-400">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-amber-400">API Endpoint</span>
                            <button
                                type="button"
                                @click="copyToClipboard(appSettingsStore.systemInfo?.api_endpoint || 'http://localhost:8000/api/v1', 'api')"
                                class="text-zinc-500 hover:text-zinc-200 transition-colors cursor-pointer"
                                title="Copy API Base URL"
                            >
                                <Check v-if="copiedApi" class="w-3.5 h-3.5 text-emerald-400" />
                                <Copy v-else class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <div class="text-base font-mono font-bold text-zinc-100 truncate">
                            /api/v1
                        </div>
                        <p class="text-xs text-zinc-500">
                            REST JSON API for smart display integration.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Form 1: Administrator Profile Informations -->
                    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-6">
                        <div class="flex items-center gap-2.5 pb-4 border-b border-zinc-800">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                <User class="w-4 h-4" />
                            </div>
                            <div>
                                <h3 class="font-bold text-base text-zinc-100">Profile & Login Informations</h3>
                                <p class="text-xs text-zinc-500">Update your name and login email address.</p>
                            </div>
                        </div>

                        <form @submit.prevent="handleUpdateProfile" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Full Name
                                </label>
                                <div class="relative flex items-center">
                                    <User class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                    <input
                                        v-model="profileForm.name"
                                        type="text"
                                        required
                                        placeholder="Restaurant Manager"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Login Email Address
                                </label>
                                <div class="relative flex items-center">
                                    <Mail class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                    <input
                                        v-model="profileForm.email"
                                        type="email"
                                        required
                                        placeholder="admin@atofood.com"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                </div>
                                <p class="text-[11px] text-zinc-500 mt-1">
                                    This email is used to log in at <span class="font-mono text-zinc-400">/login</span>.
                                </p>
                            </div>

                            <div class="pt-3 flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="savingProfile"
                                    class="py-2.5 px-5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-100 font-semibold text-xs transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400" />
                                    <span>{{ savingProfile ? 'Saving...' : 'Update Profile Info' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Form 2: Change Password -->
                    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-6">
                        <div class="flex items-center justify-between pb-4 border-b border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
                                    <Key class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-zinc-100">Change Password</h3>
                                    <p class="text-xs text-zinc-500">Secure your management dashboard with a strong password.</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="showPasswords = !showPasswords"
                                class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-200 transition-colors cursor-pointer"
                                :title="showPasswords ? 'Hide passwords' : 'Show passwords'"
                            >
                                <EyeOff v-if="showPasswords" class="w-4 h-4 text-amber-400" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>

                        <form @submit.prevent="handleUpdatePassword" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Current Password
                                </label>
                                <div class="relative flex items-center">
                                    <Lock class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                    <input
                                        v-model="passwordForm.current_password"
                                        :type="showPasswords ? 'text' : 'password'"
                                        required
                                        placeholder="••••••••"
                                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        New Password
                                    </label>
                                    <div class="relative flex items-center">
                                        <Key class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                        <input
                                            v-model="passwordForm.new_password"
                                            :type="showPasswords ? 'text' : 'password'"
                                            required
                                            minlength="6"
                                            placeholder="Min. 6 characters"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Confirm Password
                                    </label>
                                    <div class="relative flex items-center">
                                        <Key class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                        <input
                                            v-model="passwordForm.new_password_confirmation"
                                            :type="showPasswords ? 'text' : 'password'"
                                            required
                                            minlength="6"
                                            placeholder="Repeat new password"
                                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="savingPassword"
                                    class="py-2.5 px-5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-md shadow-amber-500/10 active:scale-95 flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                                >
                                    <Lock class="w-3.5 h-3.5" />
                                    <span>{{ savingPassword ? 'Changing Password...' : 'Save New Password' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Session & Security Tips -->
                <div class="p-6 rounded-2xl bg-zinc-900/40 border border-zinc-800 space-y-4">
                    <div class="flex items-center gap-2 text-zinc-300 font-bold text-sm">
                        <Info class="w-4 h-4 text-amber-400" />
                        <span>Security & Access Guidelines</span>
                    </div>
                    <ul class="text-xs text-zinc-400 space-y-2 list-disc list-inside">
                        <li>The administrator email is your primary key to sign in at <strong class="text-zinc-200">http://localhost:8000/login</strong>.</li>
                        <li>Smart TV public links (<span class="font-mono text-amber-300">{{ sampleShortUrl }}</span>) do not require password authentication, making them safe to leave running unattended on Smart TVs.</li>
                        <li>To prevent customers from opening settings on your physical TVs, change the Master TV PIN from the <strong class="text-zinc-200">App Informations</strong> tab.</li>
                    </ul>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
