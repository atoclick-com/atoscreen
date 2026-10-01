<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/client';
import { useToastStore } from '@/stores/toast';
import { useAppSettingsStore } from '@/stores/appSettings';
import QRCode from 'qrcode';
import {
    Sparkles,
    Tv,
    Layers,
    Flame,
    CheckCircle2,
    ArrowRight,
    ArrowLeft,
    Clock,
    RotateCw,
    Sliders,
    Globe,
    Shield,
    Tag,
    Copy,
    Check,
    ExternalLink,
    Play,
    Monitor,
    Radio,
    Zap,
    LayoutDashboard,
    X,
    Eye,
    Info,
    Calendar,
    Bell,
    Smartphone,
} from 'lucide-vue-next';

const router = useRouter();
const toastStore = useToastStore();
const appSettingsStore = useAppSettingsStore();

// Current Step: 1 = Brand, 2 = Engine/Schedule, 3 = Primary Screen, 4 = Starter Content, 5 = Connect & Launch
const currentStep = ref(1);
const maxStep = 5;
const loadingInitial = ref(true);
const saving = ref(false);
const saveCompleted = ref(false);
const saveProgress = ref(0);
const saveStatusMessage = ref('');

// Step 5 Connection state
const qrCodeDataUrl = ref('');
const copiedUrl = ref(false);
const completedScreen = ref(null);
const completedUrls = ref(null);

// Wizard State Data
const wizardData = reactive({
    // Step 1: Brand & Kiosk Identity
    app_name: 'trotiluxe',
    business_name: 'Trotiluxe E-Bikes & Bistro',
    display_domain: 'trotiluxe.ma',
    master_pin: '1234',
    instagram_handle: '@trotiluxe',

    // Step 2: Master Display Engine & Schedule
    default_orientation: 'landscape', // 'landscape' | 'portrait'
    default_transition: 'fade', // 'fade' | 'zoom' | 'slide' | 'none'
    default_slide_duration: 10,
    operating_hours_enabled: true,
    opening_time: '08:00',
    closing_time: '23:00',

    // Step 3: Primary Screen & Overlays
    screen_id: null,
    screen_name: 'Main Display - Bar & Bistro',
    screen_short_code: '1',
    clock_widget_enabled: true,
    clock_format: '24h',
    ticker_enabled: true,
    ticker_text: '✨ Welcome to our venue • Free High-Speed Wi-Fi • Ask about today\'s chef specials ✨',

    // Step 4: Starter Content Templates
    create_welcome_slide: true,
    welcome_headline: 'Welcome to Trotiluxe',
    welcome_description: 'Discover handcrafted delights, premium amenities, and our seasonal special offers.',
    welcome_badge: 'Welcome',

    create_promo_slide: true,
    promo_headline: 'Happy Hour Special',
    promo_description: 'Enjoy 20% off all artisanal drinks, refreshments, and selected desserts.',
    promo_price: '20% OFF',
    promo_price_subtitle: 'Daily 5:00 PM – 7:00 PM',
    promo_badge: 'Limited Offer',

    // Active card for live preview in Step 4
    preview_card_type: 'welcome', // 'welcome' | 'promo'
});

// Clean display domain
const cleanDomain = computed(() => {
    return (wizardData.display_domain || 'trotiluxe.ma')
        .replace(/^https?:\/\//i, '')
        .replace(/\/+$/, '');
});

// Live short link preview
const liveShortUrl = computed(() => {
    const code = wizardData.screen_short_code || '1';
    return `${cleanDomain.value}/v/${code}`;
});

// Live simulated clock string for TV mockup
const simulatedTime = computed(() => {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    if (wizardData.clock_format === '12h') {
        const h = now.getHours() % 12 || 12;
        const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
        return `${h}:${minutes} ${ampm}`;
    }
    return `${hours}:${minutes}`;
});

// Fetch initial data from server
onMounted(async () => {
    try {
        const response = await api.get('/wizard/initial-data');
        const data = response.data;

        if (data.settings) {
            wizardData.app_name = data.settings.app_name || 'trotiluxe';
            wizardData.business_name = data.settings.business_name || 'Trotiluxe E-Bikes & Bistro';
            wizardData.display_domain = data.settings.display_domain || data.suggested_domain;
            wizardData.master_pin = data.settings.master_pin || '1234';
            wizardData.instagram_handle = data.settings.instagram_handle || '@trotiluxe';
            wizardData.default_slide_duration = data.settings.default_slide_duration || 10;
            wizardData.default_transition = data.settings.default_transition || 'fade';
            wizardData.default_orientation = data.settings.default_orientation || 'landscape';
            wizardData.operating_hours_enabled = !!data.settings.operating_hours_enabled;
            wizardData.opening_time = data.settings.opening_time || '08:00';
            wizardData.closing_time = data.settings.closing_time || '23:00';
        }

        if (data.first_screen) {
            wizardData.screen_id = data.first_screen.id;
            wizardData.screen_name = data.first_screen.name || 'Main Display - Bar & Bistro';
            wizardData.screen_short_code = data.first_screen.short_code || '1';
            wizardData.default_orientation = data.first_screen.orientation || wizardData.default_orientation;

            if (data.first_screen.settings) {
                wizardData.clock_widget_enabled = !!data.first_screen.settings.clock_widget_enabled;
                wizardData.clock_format = data.first_screen.settings.clock_format || '24h';
                wizardData.ticker_enabled = !!data.first_screen.settings.ticker_enabled;
                if (data.first_screen.settings.ticker_text) {
                    wizardData.ticker_text = data.first_screen.settings.ticker_text;
                }
            }
        }
    } catch (err) {
        console.warn('Failed to load wizard initial data:', err);
    } finally {
        loadingInitial.value = false;
    }
});

// Navigation between steps
const canGoNext = computed(() => {
    if (currentStep.value === 1) {
        return !!wizardData.app_name.trim();
    }
    if (currentStep.value === 3) {
        return !!wizardData.screen_name.trim();
    }
    return true;
});

const nextStep = () => {
    if (currentStep.value < maxStep && canGoNext.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1 && !saving.value) {
        currentStep.value--;
    }
};

const goToStep = (step) => {
    if (step < currentStep.value && !saving.value) {
        currentStep.value = step;
    }
};

// Finish setup wizard: Save to backend
const handleFinishSetup = async () => {
    saving.value = true;
    saveProgress.value = 10;
    saveStatusMessage.value = 'Configuring Master Brand Identity...';

    try {
        await new Promise((r) => setTimeout(r, 400));
        saveProgress.value = 35;
        saveStatusMessage.value = 'Setting Up Display Engine & Schedule...';

        await new Promise((r) => setTimeout(r, 400));
        saveProgress.value = 65;
        saveStatusMessage.value = 'Creating Primary Screen & Live Overlays...';

        const payload = {
            app_name: wizardData.app_name,
            business_name: wizardData.business_name,
            display_domain: wizardData.display_domain,
            master_pin: wizardData.master_pin,
            instagram_handle: wizardData.instagram_handle,

            default_slide_duration: wizardData.default_slide_duration,
            default_transition: wizardData.default_transition,
            default_orientation: wizardData.default_orientation,
            operating_hours_enabled: wizardData.operating_hours_enabled,
            opening_time: wizardData.opening_time,
            closing_time: wizardData.closing_time,

            screen_id: wizardData.screen_id,
            screen_name: wizardData.screen_name,
            screen_short_code: wizardData.screen_short_code,
            screen_orientation: wizardData.default_orientation,
            clock_widget_enabled: wizardData.clock_widget_enabled,
            clock_format: wizardData.clock_format,
            ticker_enabled: wizardData.ticker_enabled,
            ticker_text: wizardData.ticker_text,

            create_welcome_slide: wizardData.create_welcome_slide,
            welcome_headline: wizardData.welcome_headline,
            welcome_description: wizardData.welcome_description,
            welcome_badge: wizardData.welcome_badge,

            create_promo_slide: wizardData.create_promo_slide,
            promo_headline: wizardData.promo_headline,
            promo_description: wizardData.promo_description,
            promo_price: wizardData.promo_price,
            promo_price_subtitle: wizardData.promo_price_subtitle,
            promo_badge: wizardData.promo_badge,
        };

        const response = await api.post('/wizard/complete', payload);
        saveProgress.value = 90;
        saveStatusMessage.value = 'Generating Starter Content & QR Code...';

        completedScreen.value = response.data.screen;
        completedUrls.value = response.data.tv_urls;

        // Generate QR code for immediate mobile/TV scan
        const targetUrl = completedUrls.value?.short_url || completedUrls.value?.local_display_url || window.location.origin + '/v/1';
        try {
            qrCodeDataUrl.value = await QRCode.toDataURL(targetUrl, {
                width: 320,
                margin: 2,
                color: {
                    dark: '#09090b',
                    light: '#f59e0b',
                },
            });
        } catch (e) {
            console.warn('QR code generation failed:', e);
        }

        saveProgress.value = 100;
        saveStatusMessage.value = 'Setup Complete!';
        saveCompleted.value = true;
        currentStep.value = 5;

        // Sync local storage & title immediately
        localStorage.setItem('atofood_setup_completed', 'true');
        localStorage.setItem('atofood_app_name', wizardData.app_name);
        localStorage.setItem('atofood_business_name', wizardData.business_name);
        appSettingsStore.updateDocumentTitle('Setup Wizard');

        toastStore.success('Setup Completed!', 'Your smart digital display system is live and ready.');
    } catch (err) {
        console.error('Failed to complete setup wizard:', err);
        const msg = err.response?.data?.message || 'Failed to complete setup wizard. Please verify inputs.';
        toastStore.error('Setup Error', msg);
    } finally {
        saving.value = false;
    }
};

// Copy link helper
const copyLink = async (url) => {
    try {
        await navigator.clipboard.writeText(url);
        copiedUrl.value = true;
        toastStore.success('Copied', 'Display URL copied to clipboard.');
        setTimeout(() => (copiedUrl.value = false), 2000);
    } catch (e) {
        toastStore.error('Copy Failed', 'Unable to copy to clipboard.');
    }
};
</script>

<template>
    <div class="min-h-screen bg-[#09090b] text-zinc-100 flex flex-col selection:bg-amber-500/30 selection:text-amber-200">
        <!-- Top App Bar -->
        <header class="border-b border-zinc-800/80 bg-zinc-950/70 backdrop-blur-xl sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Brand Title & Tag -->
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-400 flex items-center justify-center text-zinc-950 font-bold shadow-lg shadow-amber-500/20">
                        <Tv class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold text-base tracking-tight text-zinc-100">
                                {{ wizardData.business_name || 'Trotiluxe' }}
                            </span>
                            <span class="text-[10px] font-mono font-bold uppercase tracking-wider bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-full">
                                Quick Setup Wizard
                            </span>
                        </div>
                        <p class="text-[11px] text-zinc-500 hidden sm:block">Guided Display Network Initialization</p>
                    </div>
                </div>

                <!-- Exit / Close Button -->
                <button
                    @click="router.push('/')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900 border border-zinc-800 transition-colors cursor-pointer"
                    title="Exit to Dashboard"
                >
                    <X class="w-4 h-4" />
                    <span class="hidden sm:inline">Exit to Dashboard</span>
                </button>
            </div>
        </header>

        <!-- Stepper Progress Bar -->
        <div class="bg-zinc-900/60 border-b border-zinc-800/80 py-3 px-4 sm:px-6 sticky top-16 z-40 backdrop-blur-md">
            <div class="max-w-5xl mx-auto flex items-center justify-between">
                <!-- Steps Array -->
                <div class="flex items-center justify-between w-full relative">
                    <!-- Progress Line Background -->
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-zinc-800 rounded-full pointer-events-none" />
                    <!-- Active Progress Line -->
                    <div
                        class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-gradient-to-r from-amber-500 to-amber-400 rounded-full transition-all duration-500 pointer-events-none"
                        :style="{ width: `${((currentStep - 1) / (maxStep - 1)) * 100}%` }"
                    />

                    <!-- Step 1 -->
                    <button
                        type="button"
                        @click="goToStep(1)"
                        class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer group"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all border"
                            :class="currentStep === 1
                                ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg shadow-amber-500/30 scale-110'
                                : currentStep > 1
                                    ? 'bg-amber-500/20 text-amber-400 border-amber-500/50'
                                    : 'bg-zinc-900 text-zinc-500 border-zinc-800'"
                        >
                            <Check v-if="currentStep > 1" class="w-4 h-4" />
                            <Sparkles v-else class="w-4 h-4" />
                        </div>
                        <span
                            class="text-[11px] font-semibold tracking-tight hidden sm:block"
                            :class="currentStep >= 1 ? 'text-zinc-200' : 'text-zinc-500'"
                        >
                            1. Brand & Identity
                        </span>
                    </button>

                    <!-- Step 2 -->
                    <button
                        type="button"
                        @click="goToStep(2)"
                        class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer group"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all border"
                            :class="currentStep === 2
                                ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg shadow-amber-500/30 scale-110'
                                : currentStep > 2
                                    ? 'bg-amber-500/20 text-amber-400 border-amber-500/50'
                                    : 'bg-zinc-900 text-zinc-500 border-zinc-800'"
                        >
                            <Check v-if="currentStep > 2" class="w-4 h-4" />
                            <Tv v-else class="w-4 h-4" />
                        </div>
                        <span
                            class="text-[11px] font-semibold tracking-tight hidden sm:block"
                            :class="currentStep >= 2 ? 'text-zinc-200' : 'text-zinc-500'"
                        >
                            2. Engine & Hours
                        </span>
                    </button>

                    <!-- Step 3 -->
                    <button
                        type="button"
                        @click="goToStep(3)"
                        class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer group"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all border"
                            :class="currentStep === 3
                                ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg shadow-amber-500/30 scale-110'
                                : currentStep > 3
                                    ? 'bg-amber-500/20 text-amber-400 border-amber-500/50'
                                    : 'bg-zinc-900 text-zinc-500 border-zinc-800'"
                        >
                            <Check v-if="currentStep > 3" class="w-4 h-4" />
                            <Layers v-else class="w-4 h-4" />
                        </div>
                        <span
                            class="text-[11px] font-semibold tracking-tight hidden sm:block"
                            :class="currentStep >= 3 ? 'text-zinc-200' : 'text-zinc-500'"
                        >
                            3. Screen & Widgets
                        </span>
                    </button>

                    <!-- Step 4 -->
                    <button
                        type="button"
                        @click="goToStep(4)"
                        class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer group"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all border"
                            :class="currentStep === 4
                                ? 'bg-amber-500 text-zinc-950 border-amber-400 shadow-lg shadow-amber-500/30 scale-110'
                                : currentStep > 4
                                    ? 'bg-amber-500/20 text-amber-400 border-amber-500/50'
                                    : 'bg-zinc-900 text-zinc-500 border-zinc-800'"
                        >
                            <Check v-if="currentStep > 4" class="w-4 h-4" />
                            <Flame v-else class="w-4 h-4" />
                        </div>
                        <span
                            class="text-[11px] font-semibold tracking-tight hidden sm:block"
                            :class="currentStep >= 4 ? 'text-zinc-200' : 'text-zinc-500'"
                        >
                            4. Starter Content
                        </span>
                    </button>

                    <!-- Step 5 -->
                    <button
                        type="button"
                        class="relative z-10 flex flex-col items-center gap-1.5"
                    >
                        <div
                            class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all border"
                            :class="currentStep === 5
                                ? 'bg-emerald-500 text-zinc-950 border-emerald-400 shadow-lg shadow-emerald-500/30 scale-110'
                                : 'bg-zinc-900 text-zinc-500 border-zinc-800'"
                        >
                            <CheckCircle2 class="w-4 h-4" />
                        </div>
                        <span
                            class="text-[11px] font-semibold tracking-tight hidden sm:block"
                            :class="currentStep === 5 ? 'text-emerald-400' : 'text-zinc-500'"
                        >
                            5. Launch & Connect
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content Area: Split View -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- LEFT COLUMN: Wizard Steps & Controls (7 cols on large screen) -->
                <div class="lg:col-span-7 space-y-6">
                    
                    <!-- STEP 1: BRAND & KIOSK IDENTITY -->
                    <div v-if="currentStep === 1" class="p-6 sm:p-8 rounded-3xl bg-zinc-900/60 border border-zinc-800 shadow-2xl space-y-6">
                        <div class="space-y-1.5 border-b border-zinc-800 pb-5">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-xs font-semibold">
                                <Sparkles class="w-3.5 h-3.5" />
                                <span>Step 1 of 5</span>
                            </div>
                            <h2 class="text-2xl font-extrabold text-zinc-100">Brand & Kiosk Identity</h2>
                            <p class="text-xs text-zinc-400">
                                Set up your business name, browser tab branding, and remote TV access domain.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <!-- App & Tab Title -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                    <span>Application & Browser Tab Title</span>
                                    <span class="text-[10px] text-amber-400 font-mono lowercase">tab: {{ wizardData.app_name || 'trotiluxe' }}</span>
                                </label>
                                <input
                                    v-model="wizardData.app_name"
                                    type="text"
                                    required
                                    placeholder="e.g. trotiluxe"
                                    class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none transition-colors"
                                />
                                <p class="text-[11px] text-zinc-500 mt-1">
                                    Displayed on your browser tabs, kiosk window title, and admin console.
                                </p>
                            </div>

                            <!-- Business / Store Brand Name -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Business / Store Brand
                                </label>
                                <input
                                    v-model="wizardData.business_name"
                                    type="text"
                                    placeholder="e.g. Trotiluxe E-Bikes & Bistro"
                                    class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none transition-colors"
                                />
                                <p class="text-[11px] text-zinc-500 mt-1">
                                    Prominently displayed on smart TV standby screens and navigation bars.
                                </p>
                            </div>

                            <!-- Smart TV Domain for Short Links -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Smart TV Access Domain
                                </label>
                                <div class="relative flex items-center">
                                    <Globe class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                    <input
                                        v-model="wizardData.display_domain"
                                        type="text"
                                        placeholder="trotiluxe.ma or 127.0.0.1:8000"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none font-mono transition-colors"
                                    />
                                </div>
                                <p class="text-[11px] text-amber-400/80 mt-1 font-mono">
                                    Preview TV link: https://{{ liveShortUrl }}
                                </p>
                            </div>

                            <!-- Two Columns: PIN & Instagram -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Master Security PIN
                                    </label>
                                    <div class="relative flex items-center">
                                        <Shield class="absolute left-3.5 w-4 h-4 text-zinc-500" />
                                        <input
                                            v-model="wizardData.master_pin"
                                            type="text"
                                            maxlength="8"
                                            placeholder="1234"
                                            class="w-full pl-10 pr-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none font-mono"
                                        />
                                    </div>
                                    <p class="text-[11px] text-zinc-500 mt-1">Used to unlock on-screen TV settings.</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
                                        Official Instagram
                                    </label>
                                    <input
                                        v-model="wizardData.instagram_handle"
                                        type="text"
                                        placeholder="@trotiluxe"
                                        class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                    />
                                    <p class="text-[11px] text-zinc-500 mt-1">Optional social handle for overlays.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: MASTER DISPLAY ENGINE & SCHEDULE -->
                    <div v-else-if="currentStep === 2" class="p-6 sm:p-8 rounded-3xl bg-zinc-900/60 border border-zinc-800 shadow-2xl space-y-6">
                        <div class="space-y-1.5 border-b border-zinc-800 pb-5">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-xs font-semibold">
                                <Tv class="w-3.5 h-3.5" />
                                <span>Step 2 of 5</span>
                            </div>
                            <h2 class="text-2xl font-extrabold text-zinc-100">Display Engine & Operating Schedule</h2>
                            <p class="text-xs text-zinc-400">
                                Configure TV screen orientation, slide transitions, and automatic store hours.
                            </p>
                        </div>

                        <div class="space-y-6">
                            <!-- Orientation Selector -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2.5">
                                    Display Orientation
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Landscape -->
                                    <button
                                        type="button"
                                        @click="wizardData.default_orientation = 'landscape'"
                                        class="p-4 rounded-2xl border text-left flex items-start gap-3 transition-all cursor-pointer"
                                        :class="wizardData.default_orientation === 'landscape'
                                            ? 'bg-amber-500/10 border-amber-500/50 shadow-lg shadow-amber-500/10'
                                            : 'bg-zinc-950 border-zinc-800 hover:border-zinc-700'"
                                    >
                                        <div class="w-10 h-7 rounded-md bg-zinc-800 border border-zinc-700 flex items-center justify-center shrink-0 mt-0.5">
                                            <div class="w-7 h-4 rounded-xs bg-amber-400/40" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-zinc-100">Landscape (16:9)</span>
                                                <Check v-if="wizardData.default_orientation === 'landscape'" class="w-4 h-4 text-amber-400" />
                                            </div>
                                            <p class="text-xs text-zinc-400 mt-1">Standard horizontal TVs, wall mounts, and menu boards.</p>
                                        </div>
                                    </button>

                                    <!-- Portrait -->
                                    <button
                                        type="button"
                                        @click="wizardData.default_orientation = 'portrait'"
                                        class="p-4 rounded-2xl border text-left flex items-start gap-3 transition-all cursor-pointer"
                                        :class="wizardData.default_orientation === 'portrait'
                                            ? 'bg-amber-500/10 border-amber-500/50 shadow-lg shadow-amber-500/10'
                                            : 'bg-zinc-950 border-zinc-800 hover:border-zinc-700'"
                                    >
                                        <div class="w-7 h-10 rounded-md bg-zinc-800 border border-zinc-700 flex items-center justify-center shrink-0 mt-0.5">
                                            <div class="w-4 h-7 rounded-xs bg-amber-400/40" />
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-sm text-zinc-100">Portrait (9:16)</span>
                                                <Check v-if="wizardData.default_orientation === 'portrait'" class="w-4 h-4 text-amber-400" />
                                            </div>
                                            <p class="text-xs text-zinc-400 mt-1">Vertical standing totems, digital posters, and entryway kiosks.</p>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Transition Effect -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2.5">
                                    Default Transition Effect
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <button
                                        v-for="t in [
                                            { id: 'fade', name: 'Cross Fade' },
                                            { id: 'zoom', name: 'Zoom & Fade' },
                                            { id: 'slide', name: 'Horizontal Slide' },
                                            { id: 'none', name: 'Instant Cut' }
                                        ]"
                                        :key="t.id"
                                        type="button"
                                        @click="wizardData.default_transition = t.id"
                                        class="py-2.5 px-3 rounded-xl border text-center text-xs font-semibold transition-all cursor-pointer"
                                        :class="wizardData.default_transition === t.id
                                            ? 'bg-amber-500 text-zinc-950 font-bold border-amber-400 shadow-md shadow-amber-500/20'
                                            : 'bg-zinc-950 text-zinc-400 border-zinc-800 hover:text-zinc-200'"
                                    >
                                        {{ t.name }}
                                    </button>
                                </div>
                            </div>

                            <!-- Slide Duration -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold text-zinc-300 uppercase tracking-wider">
                                        Default Slide Duration
                                    </label>
                                    <span class="text-xs font-mono font-bold text-amber-400 px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">
                                        {{ wizardData.default_slide_duration }} seconds
                                    </span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <input
                                        v-model.number="wizardData.default_slide_duration"
                                        type="range"
                                        min="4"
                                        max="60"
                                        step="1"
                                        class="w-full accent-amber-500 cursor-pointer"
                                    />
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button
                                            v-for="dur in [8, 10, 15, 30]"
                                            :key="dur"
                                            type="button"
                                            @click="wizardData.default_slide_duration = dur"
                                            class="px-2.5 py-1 rounded-lg text-xs font-mono transition-colors"
                                            :class="wizardData.default_slide_duration === dur
                                                ? 'bg-amber-500 text-zinc-950 font-bold'
                                                : 'bg-zinc-800 text-zinc-400 hover:text-zinc-200'"
                                        >
                                            {{ dur }}s
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Operating Hours & Night Standby -->
                            <div class="p-5 rounded-2xl bg-zinc-950 border border-zinc-800/80 space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <Clock class="w-4 h-4 text-amber-400" />
                                            <span class="text-sm font-bold text-zinc-100">Automatic Operating Hours</span>
                                        </div>
                                        <p class="text-xs text-zinc-400">
                                            TV goes into low-power standby with an elegant clock when closed.
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="wizardData.operating_hours_enabled = !wizardData.operating_hours_enabled"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                        :class="wizardData.operating_hours_enabled ? 'bg-amber-500' : 'bg-zinc-800'"
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                            :class="wizardData.operating_hours_enabled ? 'translate-x-5' : 'translate-x-0'"
                                        />
                                    </button>
                                </div>

                                <div v-if="wizardData.operating_hours_enabled" class="grid grid-cols-2 gap-4 pt-3 border-t border-zinc-800">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Opening Time</label>
                                        <input
                                            v-model="wizardData.opening_time"
                                            type="time"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-sm text-zinc-100 font-mono outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Closing Time</label>
                                        <input
                                            v-model="wizardData.closing_time"
                                            type="time"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-sm text-zinc-100 font-mono outline-none"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: PRIMARY SCREEN & LIVE OVERLAYS -->
                    <div v-else-if="currentStep === 3" class="p-6 sm:p-8 rounded-3xl bg-zinc-900/60 border border-zinc-800 shadow-2xl space-y-6">
                        <div class="space-y-1.5 border-b border-zinc-800 pb-5">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-xs font-semibold">
                                <Layers class="w-3.5 h-3.5" />
                                <span>Step 3 of 5</span>
                            </div>
                            <h2 class="text-2xl font-extrabold text-zinc-100">Primary Screen & Live Overlays</h2>
                            <p class="text-xs text-zinc-400">
                                Define your initial TV display screen and customize real-time clock & news ticker widgets.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <!-- Screen Name -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5">
                                    Screen Location / Name
                                </label>
                                <input
                                    v-model="wizardData.screen_name"
                                    type="text"
                                    required
                                    placeholder="e.g. Main Dining Room or Front Counter"
                                    class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                                />
                            </div>

                            <!-- Screen Short Remote Code -->
                            <div>
                                <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                    <span>TV Remote Number (Short Code)</span>
                                    <span class="text-xs text-amber-400 font-mono">{{ liveShortUrl }}</span>
                                </label>
                                <div class="relative flex items-center">
                                    <input
                                        v-model="wizardData.screen_short_code"
                                        type="text"
                                        placeholder="1"
                                        class="w-full px-4 py-3 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 font-mono focus:border-amber-500 outline-none"
                                    />
                                </div>
                                <p class="text-[11px] text-zinc-500 mt-1">
                                    Allows TV remotes to type a single digit to open the display immediately.
                                </p>
                            </div>

                            <!-- Live Widgets: Clock & Ticker -->
                            <div class="space-y-4 pt-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-400">Screen Widgets</h3>
                                
                                <!-- Clock Widget Toggle -->
                                <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 flex items-center justify-between">
                                    <div class="space-y-0.5">
                                        <span class="text-sm font-bold text-zinc-100">Live Clock Widget</span>
                                        <p class="text-xs text-zinc-400">Displays elegant digital clock on the top corner.</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <select
                                            v-if="wizardData.clock_widget_enabled"
                                            v-model="wizardData.clock_format"
                                            class="px-2.5 py-1 rounded-lg bg-zinc-900 border border-zinc-700 text-xs text-zinc-200 font-mono outline-none"
                                        >
                                            <option value="24h">24h (14:30)</option>
                                            <option value="12h">12h (2:30 PM)</option>
                                        </select>
                                        <button
                                            type="button"
                                            @click="wizardData.clock_widget_enabled = !wizardData.clock_widget_enabled"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                            :class="wizardData.clock_widget_enabled ? 'bg-amber-500' : 'bg-zinc-800'"
                                        >
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                                :class="wizardData.clock_widget_enabled ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <!-- Ticker Widget Toggle -->
                                <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <div class="space-y-0.5">
                                            <span class="text-sm font-bold text-zinc-100">Bottom Scrolling Marquee Ticker</span>
                                            <p class="text-xs text-zinc-400">Continuous smooth news, alerts, and promotions ribbon.</p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="wizardData.ticker_enabled = !wizardData.ticker_enabled"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                            :class="wizardData.ticker_enabled ? 'bg-amber-500' : 'bg-zinc-800'"
                                        >
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                                :class="wizardData.ticker_enabled ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </div>

                                    <div v-if="wizardData.ticker_enabled">
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Ticker Announcement Text</label>
                                        <input
                                            v-model="wizardData.ticker_text"
                                            type="text"
                                            placeholder="✨ Welcome! Free Wi-Fi: Guest • Ask for today's special ✨"
                                            class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none focus:border-amber-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: STARTER CONTENT & TEMPLATES -->
                    <div v-else-if="currentStep === 4" class="p-6 sm:p-8 rounded-3xl bg-zinc-900/60 border border-zinc-800 shadow-2xl space-y-6">
                        <div class="space-y-1.5 border-b border-zinc-800 pb-5">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 text-xs font-semibold">
                                <Flame class="w-3.5 h-3.5" />
                                <span>Step 4 of 5</span>
                            </div>
                            <h2 class="text-2xl font-extrabold text-zinc-100">Starter Promotional Content</h2>
                            <p class="text-xs text-zinc-400">
                                Pre-load your display with professionally designed, ready-to-air promotional slides.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <!-- Template 1: Welcome Slide -->
                            <div
                                class="p-5 rounded-2xl border transition-all cursor-pointer"
                                :class="wizardData.create_welcome_slide
                                    ? 'bg-zinc-950 border-amber-500/40 shadow-lg'
                                    : 'bg-zinc-950/40 border-zinc-800 opacity-60'"
                            >
                                <div class="flex items-center justify-between pb-3 border-b border-zinc-800/80">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-xs">
                                            #1
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold text-zinc-100">Welcome & Store Showcase Slide</span>
                                            <p class="text-xs text-zinc-500">First slide greeting customers upon arrival.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            @click.stop="wizardData.preview_card_type = 'welcome'"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                                            :class="wizardData.preview_card_type === 'welcome'
                                                ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                                                : 'text-zinc-400 hover:text-zinc-200'"
                                        >
                                            Preview on TV
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop="wizardData.create_welcome_slide = !wizardData.create_welcome_slide"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                            :class="wizardData.create_welcome_slide ? 'bg-amber-500' : 'bg-zinc-800'"
                                        >
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                                :class="wizardData.create_welcome_slide ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <div v-if="wizardData.create_welcome_slide" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Headline</label>
                                        <input
                                            v-model="wizardData.welcome_headline"
                                            type="text"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Badge Tag</label>
                                        <input
                                            v-model="wizardData.welcome_badge"
                                            type="text"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Subtitle / Description</label>
                                        <input
                                            v-model="wizardData.welcome_description"
                                            type="text"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Template 2: Promo Deal Slide -->
                            <div
                                class="p-5 rounded-2xl border transition-all cursor-pointer"
                                :class="wizardData.create_promo_slide
                                    ? 'bg-zinc-950 border-amber-500/40 shadow-lg'
                                    : 'bg-zinc-950/40 border-zinc-800 opacity-60'"
                            >
                                <div class="flex items-center justify-between pb-3 border-b border-zinc-800/80">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-xs">
                                            #2
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold text-zinc-100">Featured Special Offer Card</span>
                                            <p class="text-xs text-zinc-500">Highlight your happy hour, discount, or chef special.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            @click.stop="wizardData.preview_card_type = 'promo'"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors"
                                            :class="wizardData.preview_card_type === 'promo'
                                                ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                                                : 'text-zinc-400 hover:text-zinc-200'"
                                        >
                                            Preview on TV
                                        </button>
                                        <button
                                            type="button"
                                            @click.stop="wizardData.create_promo_slide = !wizardData.create_promo_slide"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                            :class="wizardData.create_promo_slide ? 'bg-amber-500' : 'bg-zinc-800'"
                                        >
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                                :class="wizardData.create_promo_slide ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </div>
                                </div>

                                <div v-if="wizardData.create_promo_slide" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Headline</label>
                                        <input
                                            v-model="wizardData.promo_headline"
                                            type="text"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Discount Tag / Price</label>
                                        <input
                                            v-model="wizardData.promo_price"
                                            type="text"
                                            placeholder="20% OFF"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-amber-400 font-bold outline-none"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Description</label>
                                        <input
                                            v-model="wizardData.promo_description"
                                            type="text"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-zinc-400 mb-1">Price Subtitle</label>
                                        <input
                                            v-model="wizardData.promo_price_subtitle"
                                            type="text"
                                            placeholder="Daily 5-7 PM"
                                            class="w-full px-3 py-2 rounded-xl bg-zinc-900 border border-zinc-800 text-xs text-zinc-100 outline-none"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: LAUNCH & CONNECT (CELEBRATION STATE) -->
                    <div v-else-if="currentStep === 5" class="p-6 sm:p-8 rounded-3xl bg-zinc-900/80 border border-emerald-500/40 shadow-2xl space-y-6">
                        <div class="text-center space-y-2 pb-6 border-b border-zinc-800">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 mb-2 shadow-lg shadow-emerald-500/20">
                                <CheckCircle2 class="w-8 h-8" />
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-zinc-100">Setup Completed!</h2>
                            <p class="text-sm text-zinc-400 max-w-md mx-auto">
                                Your smart digital signage system is now active. Connect any Smart TV or web browser using the credentials below.
                            </p>
                        </div>

                        <!-- TV Connection Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                            <!-- Left: QR Code -->
                            <div class="p-6 rounded-2xl bg-zinc-950 border border-zinc-800 flex flex-col items-center justify-center text-center space-y-3">
                                <div class="w-48 h-48 rounded-xl bg-white p-2.5 flex items-center justify-center shadow-xl">
                                    <img v-if="qrCodeDataUrl" :src="qrCodeDataUrl" alt="Scan to Launch TV Display" class="w-full h-full object-contain" />
                                    <div v-else class="text-zinc-500 text-xs">Generating QR...</div>
                                </div>
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Instant Phone & TV Pairing</span>
                                    <p class="text-[11px] text-zinc-400 mt-0.5">Scan with phone camera or TV remote camera to air instantly.</p>
                                </div>
                            </div>

                            <!-- Right: Short Link & Actions -->
                            <div class="space-y-4">
                                <!-- Short Link Box -->
                                <div class="p-4 rounded-2xl bg-zinc-950 border border-zinc-800 space-y-2">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">TV Remote 1-Number Link</span>
                                    <div class="flex items-center justify-between gap-2 p-2.5 rounded-xl bg-zinc-900 border border-zinc-800">
                                        <span class="font-mono text-sm text-amber-400 font-bold truncate">
                                            {{ completedUrls?.short_url || `https://${liveShortUrl}` }}
                                        </span>
                                        <button
                                            type="button"
                                            @click="copyLink(completedUrls?.short_url || `https://${liveShortUrl}`)"
                                            class="p-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 transition-colors shrink-0"
                                            title="Copy Link"
                                        >
                                            <Check v-if="copiedUrl" class="w-4 h-4 text-emerald-400" />
                                            <Copy v-else class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-zinc-500">
                                        Optimized for typing easily with any physical Smart TV remote control.
                                    </p>
                                </div>

                                <!-- Localhost Test Display -->
                                <a
                                    :href="completedUrls?.local_display_url || `/v/${wizardData.screen_short_code || 1}`"
                                    target="_blank"
                                    class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all active:scale-95"
                                >
                                    <Play class="w-4 h-4 fill-zinc-950" />
                                    <span>Launch TV Display (New Tab)</span>
                                    <ExternalLink class="w-4 h-4 ml-1" />
                                </a>

                                <!-- Screen Editor Button -->
                                <router-link
                                    :to="completedScreen?.id ? `/screens/${completedScreen.id}` : '/'"
                                    class="w-full py-3 px-4 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-100 font-semibold text-xs uppercase tracking-wider flex items-center justify-center gap-2 transition-colors"
                                >
                                    <Layers class="w-4 h-4 text-amber-400" />
                                    <span>Open Playlist & Slide Editor</span>
                                </router-link>
                            </div>
                        </div>

                        <!-- Final Action: Go to Dashboard -->
                        <div class="pt-4 border-t border-zinc-800 flex justify-end">
                            <router-link
                                to="/"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-zinc-100 hover:bg-white text-zinc-950 font-bold text-sm transition-all shadow-xl active:scale-95"
                            >
                                <LayoutDashboard class="w-4 h-4" />
                                <span>Go to Screens Dashboard</span>
                            </router-link>
                        </div>
                    </div>

                    <!-- STEP CONTROLS (Back / Next / Finish) -->
                    <div v-if="currentStep < 5" class="flex items-center justify-between pt-2">
                        <!-- Back Button -->
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 border border-zinc-800 text-zinc-300 font-semibold text-xs uppercase tracking-wider transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <ArrowLeft class="w-4 h-4" />
                            <span>Back</span>
                        </button>
                        <div v-else />

                        <!-- Next / Complete Button -->
                        <div class="flex items-center gap-3">
                            <button
                                v-if="currentStep < 4"
                                type="button"
                                @click="nextStep"
                                :disabled="!canGoNext"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 active:scale-95 cursor-pointer disabled:opacity-50"
                            >
                                <span>Continue</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>

                            <button
                                v-else-if="currentStep === 4"
                                type="button"
                                @click="handleFinishSetup"
                                :disabled="saving"
                                class="inline-flex items-center gap-2 px-7 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-zinc-950 font-extrabold text-xs uppercase tracking-wider transition-all shadow-xl shadow-amber-500/30 active:scale-95 cursor-pointer disabled:opacity-50"
                            >
                                <Zap class="w-4 h-4 fill-zinc-950" />
                                <span>{{ saving ? 'Saving System Setup...' : 'Complete & Launch Display' }}</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: LIVE INTERACTIVE SMART TV MOCKUP (5 cols on large screen) -->
                <div class="lg:col-span-5 sticky top-28 space-y-4">
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" />
                            <span class="text-xs font-bold uppercase tracking-wider text-zinc-300">Live TV Screen Preview</span>
                        </div>
                        <span class="text-[11px] font-mono text-zinc-500">
                            {{ wizardData.default_orientation === 'portrait' ? '9:16 Portrait' : '16:9 Landscape' }}
                        </span>
                    </div>

                    <!-- TV Mockup Outer Bezel -->
                    <div class="relative group">
                        <!-- Ambient LED Backlight Glow -->
                        <div
                            class="absolute -inset-2 bg-gradient-to-r from-amber-500/20 via-amber-600/10 to-amber-500/20 rounded-3xl blur-xl opacity-75 group-hover:opacity-100 transition duration-700 pointer-events-none"
                        />

                        <!-- Physical TV Frame -->
                        <div class="relative rounded-2xl bg-zinc-950 p-2.5 border-4 border-zinc-800 shadow-2xl">
                            <!-- Screen Bezel -->
                            <div
                                class="relative w-full overflow-hidden rounded-lg bg-zinc-900 border border-zinc-800 flex flex-col justify-between transition-all duration-500"
                                :style="{
                                    aspectRatio: wizardData.default_orientation === 'portrait' ? '9 / 16' : '16 / 9',
                                    minHeight: wizardData.default_orientation === 'portrait' ? '460px' : '280px',
                                }"
                            >
                                <!-- Screen Header: Clock & Brand Logo -->
                                <div class="absolute top-3 inset-x-3 z-30 flex items-center justify-between pointer-events-none">
                                    <!-- Brand Logo / Badge -->
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-md border border-white/10 text-white shadow-md">
                                        <div class="w-3 h-3 rounded-full bg-amber-400" />
                                        <span class="font-bold text-[10px] tracking-tight truncate max-w-[120px]">
                                            {{ wizardData.business_name || 'Trotiluxe' }}
                                        </span>
                                    </div>

                                    <!-- Digital Clock -->
                                    <div
                                        v-if="wizardData.clock_widget_enabled"
                                        class="px-2.5 py-1 rounded-lg bg-black/60 backdrop-blur-md border border-white/10 font-mono text-[11px] font-bold text-amber-400 shadow-md"
                                    >
                                        {{ simulatedTime }}
                                    </div>
                                </div>

                                <!-- Screen Main Slide Content Preview -->
                                <div class="relative w-full h-full flex items-center justify-center p-6 text-center select-none overflow-hidden bg-gradient-to-tr from-zinc-950 via-zinc-900 to-zinc-950">
                                    <!-- Ambient radial backdrop -->
                                    <div class="absolute -top-12 -left-12 w-48 h-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />
                                    <div class="absolute -bottom-12 -right-12 w-48 h-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />

                                    <!-- Content: Welcome Slide Mode -->
                                    <div v-if="wizardData.preview_card_type === 'welcome'" class="space-y-3 max-w-xs relative z-10">
                                        <div v-if="wizardData.welcome_badge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[9px] font-bold uppercase tracking-wider">
                                            <Sparkles class="w-2.5 h-2.5" />
                                            <span>{{ wizardData.welcome_badge }}</span>
                                        </div>
                                        <h4 class="text-base sm:text-lg font-black text-white tracking-tight leading-snug drop-shadow-md">
                                            {{ wizardData.welcome_headline || 'Welcome to Our Store' }}
                                        </h4>
                                        <p class="text-[11px] text-zinc-300 leading-relaxed line-clamp-3">
                                            {{ wizardData.welcome_description || 'Enjoy our handcrafted delights and seasonal specials.' }}
                                        </p>
                                    </div>

                                    <!-- Content: Promo Deal Slide Mode -->
                                    <div v-else class="space-y-2.5 max-w-xs relative z-10">
                                        <div v-if="wizardData.promo_badge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[9px] font-bold uppercase tracking-wider">
                                            <Flame class="w-2.5 h-2.5 text-amber-400" />
                                            <span>{{ wizardData.promo_badge }}</span>
                                        </div>
                                        <h4 class="text-base sm:text-lg font-black text-white tracking-tight leading-snug">
                                            {{ wizardData.promo_headline || 'Happy Hour Deal' }}
                                        </h4>
                                        <div class="text-2xl font-black text-amber-400 tracking-tight">
                                            {{ wizardData.promo_price || '20% OFF' }}
                                        </div>
                                        <p class="text-[10px] text-zinc-400">
                                            {{ wizardData.promo_price_subtitle || 'Daily 5:00 PM – 7:00 PM' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Screen Bottom Marquee Ticker -->
                                <div
                                    v-if="wizardData.ticker_enabled"
                                    class="relative z-30 w-full py-1.5 px-3 bg-amber-500 text-zinc-950 font-bold text-[10px] overflow-hidden whitespace-nowrap shadow-lg flex items-center"
                                >
                                    <span class="inline-block animate-pulse shrink-0 mr-2">📢</span>
                                    <span class="truncate">{{ wizardData.ticker_text || '✨ Welcome to our venue ✨' }}</span>
                                </div>
                            </div>

                            <!-- TV Brand Badge Bottom Center -->
                            <div class="pt-2 flex items-center justify-center">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500" />
                            </div>
                        </div>

                        <!-- TV Stand Mockup (Only for Landscape) -->
                        <div v-if="wizardData.default_orientation === 'landscape'" class="flex flex-col items-center">
                            <div class="w-12 h-3 bg-zinc-800 rounded-b-xs" />
                            <div class="w-32 h-1.5 bg-zinc-700 rounded-full shadow-md" />
                        </div>
                    </div>

                    <!-- Preview Quick Info Box -->
                    <div class="p-4 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 text-xs space-y-1.5 text-zinc-400">
                        <div class="flex items-center justify-between">
                            <span>Screen Name:</span>
                            <span class="font-semibold text-zinc-200">{{ wizardData.screen_name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Slide Duration:</span>
                            <span class="font-semibold text-amber-400">{{ wizardData.default_slide_duration }}s ({{ wizardData.default_transition }})</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Remote Code:</span>
                            <span class="font-mono text-zinc-200">/v/{{ wizardData.screen_short_code || 1 }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Saving Modal Overlay -->
        <div
            v-if="saving"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4"
        >
            <div class="max-w-md w-full p-8 rounded-3xl bg-zinc-900 border border-zinc-800 shadow-2xl text-center space-y-6">
                <div class="w-16 h-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mx-auto animate-pulse">
                    <Zap class="w-8 h-8" />
                </div>
                
                <div class="space-y-1">
                    <h3 class="text-xl font-extrabold text-zinc-100">Initializing Display System</h3>
                    <p class="text-xs text-amber-400 font-mono">{{ saveStatusMessage }}</p>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-zinc-800 rounded-full h-2 overflow-hidden">
                    <div
                        class="bg-gradient-to-r from-amber-500 to-amber-300 h-full rounded-full transition-all duration-300"
                        :style="{ width: `${saveProgress}%` }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
