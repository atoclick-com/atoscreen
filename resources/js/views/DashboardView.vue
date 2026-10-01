<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import SkeletonLoader from '@/components/common/SkeletonLoader.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import Modal from '@/components/common/Modal.vue';
import { resolveMediaUrl } from '@/api/media';
import { useScreensStore } from '@/stores/screens';
import { useToastStore } from '@/stores/toast';
import {
    Tv,
    Plus,
    ExternalLink,
    Settings,
    Layers,
    Copy,
    Check,
    BarChart3,
    Sparkles,
    Monitor,
    Radio,
    Play,
    ArrowRight,
} from 'lucide-vue-next';

const screensStore = useScreensStore();
const toastStore = useToastStore();

const showCreateModal = ref(false);
const copiedId = ref(null);
const showWizardBanner = ref(localStorage.getItem('atofood_dismiss_wizard_banner') !== 'true');

const dismissWizardBanner = () => {
    showWizardBanner.value = false;
    localStorage.setItem('atofood_dismiss_wizard_banner', 'true');
};

const form = ref({
    name: '',
    default_slide_duration: 10,
    transition_effect: 'fade',
    orientation: 'landscape',
    resolution_hint: '1920x1080',
});

onMounted(() => {
    screensStore.fetchScreens();
});

const copyDisplayUrl = async (screen) => {
    try {
        const textToCopy = screen.short_url ? `https://${screen.short_url}` : screen.public_url;
        await navigator.clipboard.writeText(textToCopy);
        copiedId.value = screen.id;
        toastStore.success('Copied Short Link', `${screen.short_url || textToCopy} copied to clipboard.`);
        setTimeout(() => {
            copiedId.value = null;
        }, 2000);
    } catch (e) {
        toastStore.error('Copy Failed', 'Unable to access clipboard');
    }
};

const handleCreateScreen = async () => {
    if (!form.value.name.trim()) return;
    try {
        await screensStore.createScreen(form.value);
        showCreateModal.value = false;
        form.value = {
            name: '',
            default_slide_duration: 10,
            transition_effect: 'fade',
            orientation: 'landscape',
            resolution_hint: '1920x1080',
        };
    } catch (e) {}
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-8">
            <!-- Header & Primary CTA -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-100">
                        Display Screens
                    </h1>
                    <p class="text-sm text-zinc-400 mt-1">
                        Control promotional playlists and content for all physical smart TV kiosks.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <router-link
                        to="/wizard"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold text-sm transition-all shadow-md active:scale-95 cursor-pointer shrink-0"
                    >
                        <Sparkles class="w-4 h-4 text-amber-400" />
                        <span>Setup Wizard</span>
                    </router-link>

                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-sm transition-all shadow-lg shadow-amber-500/20 active:scale-95 cursor-pointer shrink-0"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Add New Screen</span>
                    </button>
                </div>
            </div>

            <!-- Onboarding Wizard Banner -->
            <div
                v-if="showWizardBanner"
                class="p-6 rounded-2xl bg-gradient-to-r from-amber-500/15 via-zinc-900 to-zinc-900 border border-amber-500/30 shadow-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
            >
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                        <Sparkles class="w-6 h-6 animate-pulse" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-amber-400 font-mono">Quick Start</span>
                            <span class="text-xs text-zinc-500">• 5-Minute Guided Walkthrough</span>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-100">
                            Configure your Smart TV Display Network with the Setup Wizard
                        </h3>
                        <p class="text-xs text-zinc-400 max-w-2xl leading-relaxed">
                            Set up your brand identity, screen orientations, operating schedules, clock & ticker widgets, and starter promotional playlist in one seamless flow.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <button
                        type="button"
                        @click="dismissWizardBanner"
                        class="px-3 py-2 rounded-xl text-xs font-semibold text-zinc-400 hover:text-zinc-200 transition-colors cursor-pointer"
                    >
                        Dismiss
                    </button>
                    <router-link
                        to="/wizard"
                        class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2 active:scale-95"
                    >
                        <span>Launch Setup Wizard</span>
                        <ArrowRight class="w-4 h-4" />
                    </router-link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 backdrop-blur-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-zinc-800/80 border border-zinc-700/50 flex items-center justify-center text-amber-400">
                        <Monitor class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Screens</p>
                        <p class="text-2xl font-black text-zinc-100">{{ screensStore.totalScreens }}</p>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 backdrop-blur-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <Radio class="w-6 h-6 animate-pulse" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Live & Online</p>
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl font-black text-emerald-400">{{ screensStore.onlineScreens }}</p>
                            <span class="text-xs text-zinc-500">of {{ screensStore.totalScreens }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 backdrop-blur-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400">
                        <Layers class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Active Slides</p>
                        <p class="text-2xl font-black text-zinc-100">{{ screensStore.totalActiveSlides }}</p>
                    </div>
                </div>
            </div>

            <!-- Screens Loading Skeleton -->
            <SkeletonLoader
                v-if="screensStore.loading && !screensStore.screens.length"
                type="card"
                :count="3"
            />

            <!-- Screens Card Grid -->
            <div
                v-else-if="screensStore.screens.length"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div
                    v-for="screen in screensStore.screens"
                    :key="screen.id"
                    class="rounded-2xl border border-zinc-800/80 bg-zinc-900/50 hover:border-zinc-700/80 transition-all duration-200 overflow-hidden flex flex-col justify-between group shadow-lg hover:shadow-2xl"
                >
                    <!-- Preview Image Thumbnail Header -->
                    <div class="relative h-48 w-full bg-black overflow-hidden flex items-center justify-center">
                        <!-- Video thumbnail -->
                        <template v-if="screen.thumbnail_type === 'video'">
                            <video
                                :src="resolveMediaUrl(screen.thumbnail_preview)"
                                preload="metadata"
                                muted
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500 pointer-events-none"
                            />
                            <div class="absolute inset-0 bg-black/20 flex items-center justify-center pointer-events-none">
                                <div class="w-8 h-8 rounded-full bg-black/60 border border-white/20 flex items-center justify-center text-amber-400 backdrop-blur-sm shadow-lg">
                                    <Play class="w-4 h-4 ml-0.5 fill-amber-400" />
                                </div>
                            </div>
                        </template>

                        <!-- Image thumbnail -->
                        <img
                            v-else-if="screen.thumbnail_preview"
                            :src="resolveMediaUrl(screen.thumbnail_preview)"
                            :alt="screen.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                        <div v-else class="flex flex-col items-center justify-center text-zinc-600 gap-2">
                            <Tv class="w-10 h-10" />
                            <span class="text-xs text-zinc-500">No slides loaded yet</span>
                        </div>

                        <!-- Gradient darkening at bottom of thumbnail -->
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-black/40 pointer-events-none" />

                        <!-- Online/Offline Pulsing Status Badge -->
                        <div class="absolute top-3.5 left-3.5 flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold backdrop-blur-md border shadow-lg"
                             :class="screen.is_online
                                ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/30'
                                : 'bg-zinc-900/80 text-zinc-400 border-zinc-700/50'"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="screen.is_online ? 'bg-emerald-400 animate-ping' : 'bg-zinc-500'"
                            />
                            <span>{{ screen.is_online ? 'Online' : 'Offline' }}</span>
                        </div>

                        <!-- Orientation badge -->
                        <div class="absolute top-3.5 right-3.5 px-2 py-0.5 rounded-md text-[10px] font-mono uppercase bg-black/60 text-zinc-400 border border-white/10 backdrop-blur-sm">
                            {{ screen.orientation }}
                        </div>
                    </div>

                    <!-- Screen Body Info -->
                    <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-lg font-bold text-zinc-100 group-hover:text-amber-400 transition-colors">
                                        {{ screen.name }}
                                    </h3>
                                    <div class="mt-1.5 flex items-center gap-1.5">
                                        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs font-mono font-medium">
                                            <span class="text-[9px] uppercase font-bold text-amber-500/80">TV:</span>
                                            <span>{{ screen.short_url || ('trotiluxe.ma/v/' + (screen.short_code || 1)) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    @click="copyDisplayUrl(screen)"
                                    class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-200 hover:bg-zinc-800 transition-colors cursor-pointer shrink-0"
                                    title="Copy TV Short URL"
                                >
                                    <Check v-if="copiedId === screen.id" class="w-4 h-4 text-emerald-400" />
                                    <Copy v-else class="w-4 h-4" />
                                </button>
                            </div>

                            <p class="text-xs text-zinc-500 mt-2 flex items-center gap-1.5">
                                <span>Last Ping:</span>
                                <span class="font-mono text-zinc-400">{{ screen.last_ping_human }}</span>
                            </p>
                        </div>

                        <!-- Stats Row -->
                        <div class="grid grid-cols-2 gap-2 pt-3 border-t border-zinc-800/80 text-xs">
                            <div class="p-2 rounded-xl bg-zinc-950/60 border border-zinc-800/50">
                                <span class="text-zinc-500 block text-[10px] uppercase">Slides</span>
                                <span class="font-bold text-zinc-200">{{ screen.slides_count }} total ({{ screen.active_slides_count }} active)</span>
                            </div>
                            <div class="p-2 rounded-xl bg-zinc-950/60 border border-zinc-800/50">
                                <span class="text-zinc-500 block text-[10px] uppercase">Interval</span>
                                <span class="font-bold text-zinc-200 font-mono">{{ screen.default_slide_duration }}s / {{ screen.transition_effect }}</span>
                            </div>
                        </div>

                        <!-- Actions Toolbar -->
                        <div class="pt-2 flex items-center gap-2">
                            <router-link
                                :to="`/screens/${screen.id}`"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-zinc-100 font-semibold text-xs transition-colors"
                            >
                                <Layers class="w-3.5 h-3.5" />
                                <span>Manage Slides</span>
                            </router-link>

                            <router-link
                                :to="`/screens/${screen.id}/settings`"
                                class="p-2.5 rounded-xl text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 border border-zinc-800 transition-colors"
                                title="Screen Settings & Branding"
                            >
                                <Settings class="w-4 h-4" />
                            </router-link>

                            <router-link
                                :to="`/screens/${screen.id}/analytics`"
                                class="p-2.5 rounded-xl text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 border border-zinc-800 transition-colors"
                                title="Slide Play Analytics"
                            >
                                <BarChart3 class="w-4 h-4" />
                            </router-link>

                            <a
                                :href="screen.public_url"
                                target="_blank"
                                class="p-2.5 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 transition-colors"
                                title="Launch Public Display in New Tab"
                            >
                                <ExternalLink class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else
                icon="tv"
                title="No Smart TV Screens Added Yet"
                description="Connect your restaurant or shop screens to start showing promotions, menus, and announcements."
                action-label="Create Your First Screen"
                @action="showCreateModal = true"
            />
        </div>

        <!-- Add Screen Modal -->
        <Modal
            :show="showCreateModal"
            title="Register New TV Screen"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="handleCreateScreen" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                        Screen Name / Location
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g. Front Window TV, Bar Counter Screen"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Default Slide Time (s)
                        </label>
                        <input
                            v-model.number="form.default_slide_duration"
                            type="number"
                            min="3"
                            max="300"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Transition Effect
                        </label>
                        <select
                            v-model="form.transition_effect"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                        >
                            <option value="fade">Smooth Fade</option>
                            <option value="slide">Horizontal Slide</option>
                            <option value="zoom">Cinematic Zoom</option>
                            <option value="none">Instant (Cut)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Orientation
                        </label>
                        <select
                            v-model="form.orientation"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                        >
                            <option value="landscape">Landscape (Standard 16:9)</option>
                            <option value="portrait">Portrait (Vertical 9:16)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Target Resolution
                        </label>
                        <select
                            v-model="form.resolution_hint"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none font-mono"
                        >
                            <option value="1920x1080">Full HD (1920x1080)</option>
                            <option value="3840x2160">4K Ultra HD (3840x2160)</option>
                            <option value="1080x1920">Vertical HD (1080x1920)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-zinc-800">
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="screensStore.saving"
                        class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all disabled:opacity-50 cursor-pointer"
                    >
                        {{ screensStore.saving ? 'Creating...' : 'Register Screen' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AdminLayout>
</template>
