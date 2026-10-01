<script setup>
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import QRCode from 'qrcode';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import SkeletonLoader from '@/components/common/SkeletonLoader.vue';
import PositionGridPicker from '@/components/editor/PositionGridPicker.vue';
import ToggleSwitch from '@/components/common/ToggleSwitch.vue';
import { useScreensStore } from '@/stores/screens';
import { useToastStore } from '@/stores/toast';
import {
    ArrowLeft,
    Sparkles,
    Sliders,
    Type,
    Clock,
    UploadCloud,
    Trash2,
    RefreshCw,
    QrCode,
    Copy,
    Check,
    AlertTriangle,
    ExternalLink,
    Save,
    Volume2,
    VolumeX,
    Layers,
    Instagram,
    Moon,
    Sun,
    Maximize2,
    Minimize2,
    Square,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const screensStore = useScreensStore();
const toastStore = useToastStore();

const screenId = computed(() => route.params.id);
const screen = computed(() => screensStore.currentScreen);

const qrCodeDataUrl = ref('');
const copied = ref(false);
const copiedShort = ref(false);
const logoFileInput = ref(null);
const uploadingLogo = ref(false);

const accentPresets = [
    { label: 'Amber Gold', value: '#f59e0b' },
    { label: 'Sky Cyan', value: '#0ea5e9' },
    { label: 'Emerald Leaf', value: '#10b981' },
    { label: 'Crimson Red', value: '#f43f5e' },
    { label: 'Violet Glow', value: '#a855f7' },
    { label: 'Pure White', value: '#f4f4f5' },
];

const screenForm = reactive({
    name: '',
    short_code: '',
    default_slide_duration: 10,
    transition_effect: 'fade',
    orientation: 'landscape',
    resolution_hint: '1920x1080',
});

const settingsForm = reactive({
    logo_overlay_enabled: false,
    logo_position: 'top-right',
    accent_color: '#f59e0b',
    audio_enabled: false,
    audio_volume: 80,
    aspect_ratio_mode: 'ambient_blur',
    ticker_enabled: false,
    ticker_text: '',
    ticker_speed: 25,
    clock_widget_enabled: true,
    clock_format: '24h',
    weather_widget_enabled: false,
    weather_city: '',
    operating_hours_enabled: false,
    opening_time: '08:00',
    closing_time: '23:00',
    instagram_handle: '',
    auto_refresh_interval: 60,
    screen_pin: '',
});

const generateQrCode = async (url) => {
    if (!url) return;
    try {
        qrCodeDataUrl.value = await QRCode.toDataURL(url, {
            width: 280,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#ffffff',
            },
        });
    } catch (err) {
        console.error('QR code generation failed:', err);
    }
};

const initForms = () => {
    if (!screen.value) return;

    screenForm.name = screen.value.name || '';
    screenForm.short_code = screen.value.short_code || '';
    screenForm.default_slide_duration = screen.value.default_slide_duration || 10;
    screenForm.transition_effect = screen.value.transition_effect || 'fade';
    screenForm.orientation = screen.value.orientation || 'landscape';
    screenForm.resolution_hint = screen.value.resolution_hint || '1920x1080';

    const s = screen.value.settings || {};
    settingsForm.logo_overlay_enabled = !!s.logo_overlay_enabled;
    settingsForm.logo_position = s.logo_position || 'top-right';
    settingsForm.accent_color = s.accent_color || '#f59e0b';
    settingsForm.audio_enabled = !!s.audio_enabled;
    settingsForm.audio_volume = s.audio_volume !== undefined ? s.audio_volume : 80;
    settingsForm.aspect_ratio_mode = s.aspect_ratio_mode || 'ambient_blur';
    settingsForm.ticker_enabled = !!s.ticker_enabled;
    settingsForm.ticker_text = s.ticker_text || '';
    settingsForm.ticker_speed = s.ticker_speed || 25;
    settingsForm.clock_widget_enabled = s.clock_widget_enabled !== false;
    settingsForm.clock_format = s.clock_format || '24h';
    settingsForm.weather_widget_enabled = !!s.weather_widget_enabled;
    settingsForm.weather_city = s.weather_city || '';
    settingsForm.operating_hours_enabled = !!s.operating_hours_enabled;
    settingsForm.opening_time = s.opening_time || '08:00';
    settingsForm.closing_time = s.closing_time || '23:00';
    settingsForm.instagram_handle = s.instagram_handle || '';
    settingsForm.auto_refresh_interval = s.auto_refresh_interval || 60;
    settingsForm.screen_pin = s.screen_pin || '';

    const qrUrl = screen.value.short_url ? `https://${screen.value.short_url}` : screen.value.public_url;
    generateQrCode(qrUrl);
};

const liveClock = ref('');
let clockTimer = null;

const updateLiveClock = () => {
    const now = new Date();
    liveClock.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const isCurrentlyStoreOpen = computed(() => {
    if (!settingsForm.operating_hours_enabled) return true;
    if (!settingsForm.opening_time || !settingsForm.closing_time) return true;

    const now = new Date();
    const currentMins = now.getHours() * 60 + now.getMinutes();

    const [openH, openM] = settingsForm.opening_time.split(':').map(Number);
    const [closeH, closeM] = settingsForm.closing_time.split(':').map(Number);
    const openMins = openH * 60 + openM;
    const closeMins = closeH * 60 + closeM;

    if (openMins <= closeMins) {
        return currentMins >= openMins && currentMins < closeMins;
    } else {
        return currentMins >= openMins || currentMins < closeMins;
    }
});

const openStandbyPreview = () => {
    const baseUrl = screen.value?.public_url || `/v/${screenForm.short_code || screenId.value}`;
    const url = baseUrl.includes('?') ? `${baseUrl}&preview_standby=1` : `${baseUrl}?preview_standby=1`;
    window.open(url, '_blank');
};

onMounted(async () => {
    await screensStore.fetchScreen(screenId.value);
    initForms();
    updateLiveClock();
    clockTimer = setInterval(updateLiveClock, 5000);
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
});

watch(screenId, async (newId) => {
    if (newId) {
        await screensStore.fetchScreen(newId);
        initForms();
    }
});

const savingAll = ref(false);

const handleSaveAll = async () => {
    if (savingAll.value) return;
    savingAll.value = true;
    try {
        const screenPayload = {
            name: screenForm.name,
            short_code: screenForm.short_code ? String(screenForm.short_code).trim() : null,
            default_slide_duration: Number(screenForm.default_slide_duration) || 10,
            transition_effect: screenForm.transition_effect || 'fade',
            orientation: screenForm.orientation || 'landscape',
            resolution_hint: screenForm.resolution_hint || '1920x1080',
        };

        const settingsPayload = {
            logo_overlay_enabled: !!settingsForm.logo_overlay_enabled,
            logo_position: settingsForm.logo_position || 'top-right',
            accent_color: settingsForm.accent_color || '#f59e0b',
            audio_enabled: !!settingsForm.audio_enabled,
            audio_volume: Number(settingsForm.audio_volume) ?? 80,
            aspect_ratio_mode: settingsForm.aspect_ratio_mode || 'ambient_blur',
            ticker_enabled: !!settingsForm.ticker_enabled,
            ticker_text: settingsForm.ticker_text || null,
            ticker_speed: Number(settingsForm.ticker_speed) || 25,
            clock_widget_enabled: !!settingsForm.clock_widget_enabled,
            clock_format: settingsForm.clock_format || '24h',
            weather_widget_enabled: !!settingsForm.weather_widget_enabled,
            weather_city: settingsForm.weather_city || null,
            operating_hours_enabled: !!settingsForm.operating_hours_enabled,
            opening_time: settingsForm.opening_time || '08:00',
            closing_time: settingsForm.closing_time || '23:00',
            instagram_handle: settingsForm.instagram_handle || null,
            auto_refresh_interval: Number(settingsForm.auto_refresh_interval) || 60,
            screen_pin: settingsForm.screen_pin || null,
        };

        const updated = await screensStore.saveScreenSettings(screenId.value, screenPayload, settingsPayload);
        const qrUrl = updated?.short_url ? `https://${updated.short_url}` : (updated?.public_url || screen.value?.public_url);
        generateQrCode(qrUrl);
    } catch (err) {
        const msg = err.response?.data?.message || err.message || 'Failed to save settings.';
        toastStore.error('Save Failed', msg);
    } finally {
        savingAll.value = false;
    }
};

const applyingToAllSlides = ref(false);

const handleApplyFitModeToAll = async () => {
    applyingToAllSlides.value = true;
    try {
        await screensStore.batchUpdateFitMode(screenId.value, settingsForm.aspect_ratio_mode);
        await handleSaveAll();
    } finally {
        applyingToAllSlides.value = false;
    }
};

const handleLogoUpload = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    uploadingLogo.value = true;
    try {
        const fd = new FormData();
        fd.append('logo', file);
        await screensStore.uploadLogo(screenId.value, fd);
        settingsForm.logo_overlay_enabled = true;
    } finally {
        uploadingLogo.value = false;
        e.target.value = '';
    }
};

const handleRemoveLogo = async () => {
    if (confirm('Remove custom logo from this screen?')) {
        await screensStore.removeLogo(screenId.value);
    }
};

const copyUrl = async () => {
    if (!screen.value?.public_url) return;
    try {
        await navigator.clipboard.writeText(screen.value.public_url);
        copied.value = true;
        toastStore.success('Copied URL', 'Public display URL copied to clipboard.');
        setTimeout(() => { copied.value = false; }, 2000);
    } catch (e) {}
};

const copyShortUrl = async () => {
    const short = screen.value?.short_url || `trotiluxe.ma/v/${screenForm.short_code || 1}`;
    try {
        await navigator.clipboard.writeText(`https://${short}`);
        copiedShort.value = true;
        toastStore.success('Copied Short Link', `https://${short} copied to clipboard.`);
        setTimeout(() => { copiedShort.value = false; }, 2000);
    } catch (e) {}
};

const handleRegenerateUrl = async () => {
    if (confirm('Are you sure? This will generate a new link and the previous TV display will stop updating.')) {
        const res = await screensStore.regenerateUrl(screenId.value);
        if (res?.screen?.id && res.screen.id !== screenId.value) {
            router.replace({ name: 'screen-settings', params: { id: res.screen.id } });
        }
        initForms();
    }
};

const handleResetScreen = async () => {
    if (confirm('WARNING: Reset screen to factory defaults? All slides in this playlist will be removed.')) {
        await screensStore.resetScreen(screenId.value);
        initForms();
    }
};

const handleDeleteScreen = async () => {
    if (confirm(`Permanently delete screen "${screen.value?.name}"? This action cannot be undone.`)) {
        await screensStore.deleteScreen(screenId.value);
        router.push('/');
    }
};
</script>

<template>
    <AdminLayout>
        <div v-if="screensStore.loading && !screen" class="space-y-6">
            <SkeletonLoader type="text" :count="3" />
            <SkeletonLoader type="card" :count="2" />
        </div>

        <div v-else-if="screen" class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-zinc-800/80">
                <div class="space-y-1.5">
                    <router-link
                        :to="`/screens/${screen.id}`"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-400 hover:text-zinc-200 transition-colors"
                    >
                        <ArrowLeft class="w-3.5 h-3.5" />
                        Back to Playlist Editor
                    </router-link>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-100">
                        Screen Settings & Controls
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        :disabled="screensStore.saving || savingAll"
                        @click="handleSaveAll"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ (screensStore.saving || savingAll) ? 'Saving...' : 'Save Changes' }}</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left 2 Cols: Form Sections -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- 1. Display Hardware & General -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-zinc-800">
                            <Sliders class="w-5 h-5 text-amber-400" />
                            <h3 class="font-bold text-base text-zinc-100">Display Hardware & Timing</h3>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Screen Name / Location
                                    </label>
                                    <input
                                        v-model="screenForm.name"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                            Screen Number (Short Link)
                                        </label>
                                        <span class="text-[11px] font-mono text-amber-400 font-bold">/v/{{ screenForm.short_code || 1 }}</span>
                                    </div>
                                    <div class="relative flex items-center">
                                        <span class="absolute left-3.5 text-xs text-zinc-500 font-mono select-none">/v/</span>
                                        <input
                                            v-model="screenForm.short_code"
                                            type="text"
                                            placeholder="1"
                                            class="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm font-mono text-zinc-100 focus:border-amber-500 outline-none"
                                        />
                                    </div>
                                    <p class="text-[10px] text-zinc-500 mt-1">
                                        Makes the link ultra-short: <span class="text-zinc-300 font-mono font-semibold">trotiluxe.ma/v/{{ screenForm.short_code || 1 }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="flex justify-between items-center mb-1.5">
                                        <label class="text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                            Default Slide Timing
                                        </label>
                                        <span class="text-xs font-mono font-bold text-amber-400">{{ screenForm.default_slide_duration }} seconds</span>
                                    </div>
                                    <input
                                        v-model.number="screenForm.default_slide_duration"
                                        type="range"
                                        min="3"
                                        max="60"
                                        step="1"
                                        class="w-full accent-amber-500 cursor-pointer"
                                    />
                                    <div class="flex justify-between text-[10px] text-zinc-500 mt-1 font-mono">
                                        <span>3s (Fast)</span>
                                        <span>15s</span>
                                        <span>60s (Slow)</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Slide Transition Effect
                                    </label>
                                    <select
                                        v-model="screenForm.transition_effect"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    >
                                        <option value="fade">Crossfade (Soft & Elegant)</option>
                                        <option value="slide">Horizontal Slide (Dynamic)</option>
                                        <option value="zoom">Cinematic Zoom & Fade</option>
                                        <option value="none">Instant Cut (No effect)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Screen Orientation
                                    </label>
                                    <select
                                        v-model="screenForm.orientation"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    >
                                        <option value="landscape">Landscape (Horizontal 16:9 TV)</option>
                                        <option value="portrait">Portrait (Vertical 9:16 Totem / Stand)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Cloud Poll Interval
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input
                                            v-model.number="settingsForm.auto_refresh_interval"
                                            type="number"
                                            min="15"
                                            max="300"
                                            class="w-28 px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none font-mono"
                                        />
                                        <span class="text-xs text-zinc-500">sec (syncs playlist changes to TV)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Audio & Sound Controls -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                            <div class="flex items-center gap-2">
                                <Volume2 class="w-5 h-5 text-amber-400" />
                                <h3 class="font-bold text-base text-zinc-100">Audio & Sound Playback</h3>
                            </div>
                            <ToggleSwitch v-model="settingsForm.audio_enabled" />
                        </div>

                        <div class="space-y-4">
                            <p class="text-xs text-zinc-400 leading-relaxed">
                                Control whether videos and Instagram reels play background audio on this screen. A discrete unmute pill will appear on the TV to respect browser autoplay policies.
                            </p>

                            <!-- Volume Slider -->
                            <div class="p-4 rounded-xl bg-zinc-950 border border-zinc-800/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-zinc-300 uppercase tracking-wider flex items-center gap-2">
                                        <span>Master TV Volume</span>
                                        <span class="text-xs font-mono font-bold text-amber-400">{{ settingsForm.audio_volume }}%</span>
                                    </label>
                                    <span class="text-[11px] text-zinc-500">{{ settingsForm.audio_enabled ? 'Sound Active' : 'Sound Muted' }}</span>
                                </div>

                                <div class="flex items-center gap-4">
                                    <VolumeX class="w-4 h-4 text-zinc-500 shrink-0" />
                                    <input
                                        v-model.number="settingsForm.audio_volume"
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="5"
                                        :disabled="!settingsForm.audio_enabled"
                                        class="w-full accent-amber-500 cursor-pointer disabled:opacity-30"
                                    />
                                    <Volume2 class="w-4 h-4 text-amber-400 shrink-0" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Aspect Ratio & Display Mode (Full Screen vs Boxed) -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                            <div class="flex items-center gap-2">
                                <Maximize2 class="w-5 h-5 text-amber-400" />
                                <h3 class="font-bold text-base text-zinc-100">Screen Display Mode (Full Screen vs Boxed)</h3>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                :class="settingsForm.aspect_ratio_mode === 'cover'
                                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                                    : 'bg-zinc-800 text-zinc-400'"
                            >
                                {{ settingsForm.aspect_ratio_mode === 'cover' ? 'Full Screen Active' : (settingsForm.aspect_ratio_mode === 'contain' ? 'Fit Screen Active' : 'Boxed Card Active') }}
                            </span>
                        </div>

                        <p class="text-xs text-zinc-400">
                            Choose how videos and images fill your TV screen. Select <strong class="text-emerald-400">Full Screen (Cover)</strong> to make media edge-to-edge without boxed borders or margins:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <!-- Cover / Full Screen Option (Recommended) -->
                            <div
                                @click="settingsForm.aspect_ratio_mode = 'cover'"
                                class="p-4 rounded-2xl border transition-all cursor-pointer relative flex flex-col justify-between"
                                :class="settingsForm.aspect_ratio_mode === 'cover'
                                    ? 'bg-emerald-950/30 border-emerald-500/60 ring-1 ring-emerald-500/40 shadow-lg shadow-emerald-950/40'
                                    : 'bg-zinc-950 border-zinc-800 hover:border-zinc-700'"
                            >
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-zinc-100">Full Screen (Cover)</span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-400 uppercase">Recommended</span>
                                    </div>
                                    <p class="text-[11px] text-zinc-400 leading-tight">
                                        Full screen edge-to-edge. 100% unboxed, no borders or black bars.
                                    </p>
                                </div>
                                <div class="mt-3 h-12 rounded-lg bg-black border border-white/10 overflow-hidden relative flex items-center justify-center">
                                    <div class="w-full h-full bg-emerald-600/80 flex items-center justify-center">
                                        <Maximize2 class="w-4 h-4 text-emerald-100" />
                                    </div>
                                </div>
                            </div>

                            <!-- Contain Option -->
                            <div
                                @click="settingsForm.aspect_ratio_mode = 'contain'"
                                class="p-4 rounded-2xl border transition-all cursor-pointer relative flex flex-col justify-between"
                                :class="settingsForm.aspect_ratio_mode === 'contain'
                                    ? 'bg-sky-950/30 border-sky-500/60 ring-1 ring-sky-500/40'
                                    : 'bg-zinc-950 border-zinc-800 hover:border-zinc-700'"
                            >
                                <div class="space-y-1.5">
                                    <span class="font-bold text-xs text-zinc-100">Fit Screen (Letterbox)</span>
                                    <p class="text-[11px] text-zinc-400 leading-tight">
                                        Unboxed edge-to-edge. Preserves original ratio with clean black bars.
                                    </p>
                                </div>
                                <div class="mt-3 h-12 rounded-lg bg-black border border-white/10 flex items-center justify-center">
                                    <div class="w-8 h-full bg-sky-600/80 rounded-sm flex items-center justify-center">
                                        <Minimize2 class="w-3.5 h-3.5 text-sky-100" />
                                    </div>
                                </div>
                            </div>

                            <!-- Ambient Glass Blur Option -->
                            <div
                                @click="settingsForm.aspect_ratio_mode = 'ambient_blur'"
                                class="p-4 rounded-2xl border transition-all cursor-pointer relative flex flex-col justify-between"
                                :class="settingsForm.aspect_ratio_mode === 'ambient_blur'
                                    ? 'bg-amber-500/10 border-amber-500/50 ring-1 ring-amber-500/30'
                                    : 'bg-zinc-950 border-zinc-800 hover:border-zinc-700'"
                            >
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-xs text-zinc-100">Boxed Card (Glass Blur)</span>
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-400 uppercase">Card</span>
                                    </div>
                                    <p class="text-[11px] text-zinc-400 leading-tight">
                                        Floating card with rounded corners, subtle glass border & blurred backdrop.
                                    </p>
                                </div>
                                <div class="mt-3 h-12 rounded-lg bg-black/60 border border-white/10 overflow-hidden relative flex items-center justify-center p-1.5">
                                    <div class="absolute inset-0 bg-amber-500/20 blur-sm" />
                                    <div class="relative z-10 w-full h-full bg-amber-500/60 rounded border border-white/20 flex items-center justify-center">
                                        <Square class="w-3.5 h-3.5 text-amber-100" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Apply to All Existing Slides Button -->
                        <div class="pt-3 border-t border-zinc-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <p class="text-xs text-zinc-400">
                                Want all slides currently in this screen's playlist to use this mode?
                            </p>
                            <button
                                type="button"
                                :disabled="applyingToAllSlides"
                                @click="handleApplyFitModeToAll"
                                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-zinc-700 text-xs font-semibold transition-all cursor-pointer disabled:opacity-50 shrink-0"
                            >
                                <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                                <span>{{ applyingToAllSlides ? 'Updating Slides...' : 'Apply Mode to All Slides Now' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- 4. Operating Hours Schedule & Night Standby -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                            <div class="flex items-center gap-2">
                                <Moon class="w-5 h-5 text-amber-400" />
                                <h3 class="font-bold text-base text-zinc-100">Operating Hours & Night Standby</h3>
                            </div>
                            <ToggleSwitch v-model="settingsForm.operating_hours_enabled" />
                        </div>

                        <div class="space-y-4">
                            <p class="text-xs text-zinc-400">
                                When enabled, the TV screen will display an elegant night standby message outside your store's operating hours.
                            </p>

                            <!-- Live Operating Status Card -->
                            <div v-if="settingsForm.operating_hours_enabled" class="transition-all">
                                <div v-if="isCurrentlyStoreOpen" class="p-4 rounded-xl bg-emerald-950/30 border border-emerald-500/30 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse" />
                                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wide">Store Currently Open (Broadcasting Active)</span>
                                        </div>
                                        <span class="text-xs font-mono text-zinc-400">Current Time: {{ liveClock }}</span>
                                    </div>
                                    <p class="text-xs text-zinc-300 leading-relaxed">
                                        The screen is actively broadcasting within operating hours (<strong>{{ settingsForm.opening_time }}</strong> — <strong>{{ settingsForm.closing_time }}</strong>). Night Standby will automatically activate at <strong class="text-emerald-300 font-mono">{{ settingsForm.closing_time }}</strong>.
                                    </p>
                                </div>

                                <div v-else class="p-4 rounded-xl bg-indigo-950/40 border border-indigo-500/40 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <Moon class="w-4 h-4 text-indigo-400" />
                                            <span class="text-xs font-bold text-indigo-400 uppercase tracking-wide">Night Standby is Active Now</span>
                                        </div>
                                        <span class="text-xs font-mono text-zinc-400">Current Time: {{ liveClock }}</span>
                                    </div>
                                    <p class="text-xs text-zinc-300 leading-relaxed">
                                        Current time is outside operating hours. The TV screen is displaying the night standby screen and will resume broadcasting at <strong class="text-indigo-300 font-mono">{{ settingsForm.opening_time }}</strong>.
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 space-y-1.5">
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider flex items-center gap-1.5">
                                        <Sun class="w-3.5 h-3.5 text-amber-400" />
                                        <span>Opening Time</span>
                                    </label>
                                    <input
                                        v-model="settingsForm.opening_time"
                                        type="time"
                                        :disabled="!settingsForm.operating_hours_enabled"
                                        class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-zinc-100 focus:border-amber-500 outline-none font-mono disabled:opacity-40"
                                    />
                                </div>

                                <div class="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 space-y-1.5">
                                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider flex items-center gap-1.5">
                                        <Moon class="w-3.5 h-3.5 text-indigo-400" />
                                        <span>Closing Time</span>
                                    </label>
                                    <input
                                        v-model="settingsForm.closing_time"
                                        type="time"
                                        :disabled="!settingsForm.operating_hours_enabled"
                                        class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-zinc-100 focus:border-amber-500 outline-none font-mono disabled:opacity-40"
                                    />
                                </div>
                            </div>

                            <!-- Preview Night Standby Button -->
                            <div class="pt-3 border-t border-zinc-800/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <p class="text-xs text-zinc-400">
                                    Want to test or preview how your Night Standby screen looks on TV?
                                </p>
                                <button
                                    type="button"
                                    @click="openStandbyPreview"
                                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-zinc-700 text-xs font-semibold transition-all cursor-pointer shrink-0"
                                >
                                    <ExternalLink class="w-3.5 h-3.5 text-amber-400" />
                                    <span>Preview Standby Display</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Branding & Logo Overlay -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-6">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                            <div class="flex items-center gap-2">
                                <Sparkles class="w-5 h-5 text-amber-400" />
                                <h3 class="font-bold text-base text-zinc-100">Branding & Logo Overlay</h3>
                            </div>
                            <ToggleSwitch v-model="settingsForm.logo_overlay_enabled" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 items-start">
                            <!-- Logo Upload Box -->
                            <div class="space-y-3">
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                    Brand Logo (PNG / SVG)
                                </label>

                                <div class="flex items-center gap-4">
                                    <div class="w-28 h-20 rounded-xl bg-zinc-950 border border-zinc-800 p-2 flex items-center justify-center shrink-0 overflow-hidden relative group">
                                        <img
                                            v-if="screen.settings?.logo_url"
                                            :src="screen.settings.logo_url"
                                            alt="Logo"
                                            class="max-w-full max-h-full object-contain"
                                        />
                                        <div v-else class="text-[11px] text-zinc-600 text-center font-mono">
                                            No Logo
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <input
                                            ref="logoFileInput"
                                            type="file"
                                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                            class="hidden"
                                            @change="handleLogoUpload"
                                        />
                                        <button
                                            type="button"
                                            @click="logoFileInput?.click()"
                                            class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-200 transition-colors flex items-center gap-1.5 cursor-pointer"
                                        >
                                            <UploadCloud class="w-3.5 h-3.5" />
                                            <span>{{ screen.settings?.logo_url ? 'Replace Logo' : 'Upload Logo' }}</span>
                                        </button>
                                        <button
                                            v-if="screen.settings?.logo_url"
                                            type="button"
                                            @click="handleRemoveLogo"
                                            class="text-[11px] text-rose-400 hover:underline block cursor-pointer"
                                        >
                                            Remove Logo
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 3x3 Position Picker -->
                            <div class="space-y-3">
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                    Logo Position on TV (3x3 Grid)
                                </label>
                                <PositionGridPicker v-model="settingsForm.logo_position" />
                            </div>
                        </div>

                        <!-- Accent Color Picker -->
                        <div class="space-y-3 pt-2">
                            <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider">
                                Accent Brand Color
                            </label>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <button
                                    v-for="preset in accentPresets"
                                    :key="preset.value"
                                    type="button"
                                    @click="settingsForm.accent_color = preset.value"
                                    class="w-8 h-8 rounded-xl border-2 transition-transform cursor-pointer"
                                    :class="settingsForm.accent_color === preset.value ? 'scale-110 border-white shadow-lg' : 'border-transparent hover:scale-105'"
                                    :style="{ background: preset.value }"
                                    :title="preset.label"
                                />

                                <div class="flex items-center gap-2 ml-2 pl-3 border-l border-zinc-800">
                                    <input
                                        v-model="settingsForm.accent_color"
                                        type="color"
                                        class="w-8 h-8 rounded-lg bg-transparent border-0 cursor-pointer"
                                    />
                                    <span class="text-xs font-mono text-zinc-400 uppercase">{{ settingsForm.accent_color }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Bottom Marquee Ticker -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                            <div class="flex items-center gap-2">
                                <Type class="w-5 h-5 text-amber-400" />
                                <h3 class="font-bold text-base text-zinc-100">Bottom Marquee Ticker</h3>
                            </div>
                            <ToggleSwitch v-model="settingsForm.ticker_enabled" />
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Scrolling Announcement Text
                                </label>
                                <textarea
                                    v-model="settingsForm.ticker_text"
                                    rows="2"
                                    placeholder="e.g. ✨ Happy Hour 5-7 PM • Free Wi-Fi: AtoFood-Guest • Ask for today's dessert special ✨"
                                    class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none resize-none"
                                />
                            </div>

                            <!-- Live Ticker Preview Strip -->
                            <div class="space-y-1.5">
                                <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                                    Live Ticker Preview Strip
                                </span>
                                <div class="h-10 rounded-xl overflow-hidden bg-black border flex items-center relative backdrop-blur-md"
                                     :style="{ borderColor: `${settingsForm.accent_color}50` }"
                                >
                                    <div
                                        class="h-full px-3 flex items-center font-bold text-[10px] tracking-wider uppercase shrink-0"
                                        :style="{ background: settingsForm.accent_color, color: '#09090b' }"
                                    >
                                        INFO
                                    </div>
                                    <div class="relative w-full overflow-hidden flex items-center">
                                        <div class="animate-marquee font-medium text-xs text-zinc-100 pl-4 tracking-wide whitespace-nowrap">
                                            {{ settingsForm.ticker_text || 'Enter ticker text to see continuous live marquee preview' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Clock & Weather Widgets -->
                    <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-5">
                        <div class="flex items-center gap-2 pb-3 border-b border-zinc-800">
                            <Clock class="w-5 h-5 text-amber-400" />
                            <h3 class="font-bold text-base text-zinc-100">Clock & Overlay Widgets</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="p-4 rounded-xl bg-zinc-950 border border-zinc-800/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-zinc-200">Digital Clock</span>
                                    <ToggleSwitch v-model="settingsForm.clock_widget_enabled" />
                                </div>
                                <div>
                                    <label class="block text-[11px] text-zinc-400 mb-1">Clock Time Format</label>
                                    <select
                                        v-model="settingsForm.clock_format"
                                        class="w-full px-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-700 text-xs text-zinc-200 focus:border-amber-500 outline-none"
                                    >
                                        <option value="24h">24-Hour Format (19:45)</option>
                                        <option value="12h">12-Hour Format (7:45 PM)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-4 rounded-xl bg-zinc-950 border border-zinc-800/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-zinc-200">Weather Widget</span>
                                    <ToggleSwitch v-model="settingsForm.weather_widget_enabled" />
                                </div>
                                <div>
                                    <label class="block text-[11px] text-zinc-400 mb-1">City / Location Label</label>
                                    <input
                                        v-model="settingsForm.weather_city"
                                        type="text"
                                        placeholder="e.g. Paris"
                                        class="w-full px-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-700 text-xs text-zinc-200 focus:border-amber-500 outline-none"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Save Bar -->
                    <div class="p-5 rounded-2xl bg-zinc-900/90 border border-zinc-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-xl">
                        <div class="space-y-0.5">
                            <h4 class="text-sm font-bold text-zinc-100 flex items-center gap-2">
                                <Save class="w-4 h-4 text-amber-400" />
                                <span>Save All Screen Settings</span>
                            </h4>
                            <p class="text-xs text-zinc-400">
                                Apply all updates to display hardware, widgets, sound, and ticker.
                            </p>
                        </div>
                        <button
                            type="button"
                            :disabled="screensStore.saving || savingAll"
                            @click="handleSaveAll"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 active:scale-95 disabled:opacity-50 cursor-pointer"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ (screensStore.saving || savingAll) ? 'Saving...' : 'Save Changes' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Right Col: TV Pairing QR Code & Danger Zone -->
                <div class="space-y-6">
                    <!-- TV Pairing Card -->
                    <div class="p-6 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-5 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center mx-auto">
                            <QrCode class="w-6 h-6" />
                        </div>

                        <div>
                            <h3 class="font-bold text-base text-zinc-100">Connect Smart TV</h3>
                            <p class="text-xs text-zinc-400 mt-1">
                                Ultra-short TV link for easy typing on Smart TV remotes.
                            </p>
                        </div>

                        <!-- Ultra-Short TV URL Display -->
                        <div class="p-3.5 rounded-xl bg-zinc-950 border border-amber-500/30 text-left space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400">TV Short Link (1 Number):</span>
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-300 font-mono">/v/{{ screenForm.short_code || 1 }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-zinc-900 border border-zinc-800">
                                <span class="font-mono text-sm font-bold text-zinc-100 select-all truncate">
                                    {{ screen?.short_url || `trotiluxe.ma/v/${screenForm.short_code || 1}` }}
                                </span>
                                <button
                                    type="button"
                                    @click="copyShortUrl"
                                    class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 transition-colors shrink-0 cursor-pointer"
                                    title="Copy Short Link"
                                >
                                    <Check v-if="copiedShort" class="w-4 h-4 text-emerald-400" />
                                    <Copy v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-500 pt-1">
                                <span>Localhost:</span>
                                <span class="font-mono text-zinc-400 truncate">{{ screen?.local_url || screen?.public_url }}</span>
                            </div>
                        </div>

                        <!-- QR Code Image -->
                        <div class="bg-white p-3 rounded-2xl w-52 h-52 mx-auto flex items-center justify-center shadow-xl">
                            <img
                                v-if="qrCodeDataUrl"
                                :src="qrCodeDataUrl"
                                alt="TV Display QR Code"
                                class="w-full h-full object-contain"
                            />
                            <div v-else class="text-xs text-zinc-400 animate-pulse font-mono">Generating QR...</div>
                        </div>

                        <!-- Copy and Preview Buttons -->
                        <div class="space-y-2 pt-2">
                            <button
                                type="button"
                                @click="copyShortUrl"
                                class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-xs font-bold text-zinc-950 uppercase tracking-wider transition-colors flex items-center justify-center gap-2 shadow-md shadow-amber-500/10 cursor-pointer"
                            >
                                <Check v-if="copiedShort" class="w-4 h-4 text-zinc-950" />
                                <Copy v-else class="w-4 h-4" />
                                <span>{{ copiedShort ? 'Short Link Copied!' : 'Copy ' + (screen?.short_url || 'trotiluxe.ma/v/' + (screenForm.short_code || 1)) }}</span>
                            </button>

                            <button
                                type="button"
                                @click="copyUrl"
                                class="w-full py-2 px-4 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-xs font-semibold text-zinc-300 transition-colors flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <Check v-if="copied" class="w-3.5 h-3.5 text-emerald-400" />
                                <Copy v-else class="w-3.5 h-3.5" />
                                <span>{{ copied ? 'Local Link Copied!' : 'Copy Local URL' }}</span>
                            </button>

                            <a
                                :href="screen?.public_url"
                                target="_blank"
                                class="w-full py-2.5 px-4 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-xs font-bold text-zinc-100 transition-colors flex items-center justify-center gap-1.5"
                            >
                                <span>Open Fullscreen TV Mode</span>
                                <ExternalLink class="w-3.5 h-3.5 text-amber-400" />
                            </a>
                        </div>
                    </div>

                    <!-- Instagram Brand Handle Card -->
                    <div class="p-5 rounded-2xl bg-zinc-900/60 border border-zinc-800 space-y-3">
                        <div class="flex items-center gap-2">
                            <Instagram class="w-4 h-4 text-pink-400" />
                            <h4 class="text-xs font-bold text-zinc-200 uppercase tracking-wider">Shop Instagram</h4>
                        </div>
                        <input
                            v-model="settingsForm.instagram_handle"
                            type="text"
                            placeholder="@atofood_official"
                            class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-100 focus:border-amber-500 outline-none"
                        />
                        <p class="text-[10px] text-zinc-500 leading-tight">
                            Default handle displayed on TV post cards and customer scan-to-follow badges.
                        </p>
                    </div>

                    <!-- Maintenance & Danger Zone -->
                    <div class="p-6 rounded-2xl bg-rose-950/20 border border-rose-900/40 space-y-4">
                        <div class="flex items-center gap-2 text-rose-400">
                            <AlertTriangle class="w-4 h-4" />
                            <h4 class="font-bold text-xs uppercase tracking-wider">Device Maintenance</h4>
                        </div>

                        <div class="space-y-2">
                            <button
                                type="button"
                                @click="handleRegenerateUrl"
                                class="w-full py-2 px-3 rounded-lg bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-xs text-zinc-300 font-medium transition-colors flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <RefreshCw class="w-3.5 h-3.5 text-amber-400" />
                                <span>Regenerate Display Token</span>
                            </button>

                            <button
                                type="button"
                                @click="handleResetScreen"
                                class="w-full py-2 px-3 rounded-lg bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-xs text-zinc-300 font-medium transition-colors flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <span>Reset Playlist to Default</span>
                            </button>

                            <button
                                type="button"
                                @click="handleDeleteScreen"
                                class="w-full py-2 px-3 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-xs text-rose-400 font-semibold transition-colors flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                                <span>Delete Screen</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
