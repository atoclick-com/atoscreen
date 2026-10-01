<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import SkeletonLoader from '@/components/common/SkeletonLoader.vue';
import MediaUploadZone from '@/components/editor/MediaUploadZone.vue';
import SlideList from '@/components/editor/SlideList.vue';
import PromoSlideModal from '@/components/editor/PromoSlideModal.vue';
import InstagramSlideModal from '@/components/editor/InstagramSlideModal.vue';
import ScheduleModal from '@/components/editor/ScheduleModal.vue';
import { resolveMediaUrl } from '@/api/media';
import { useScreensStore } from '@/stores/screens';
import { useToastStore } from '@/stores/toast';
import {
    Tv,
    ArrowLeft,
    ExternalLink,
    Settings,
    BarChart3,
    Plus,
    Sparkles,
    Check,
    Copy,
    Clock,
    Sliders,
    Instagram,
    Maximize2,
    Square,
} from 'lucide-vue-next';

import Modal from '@/components/common/Modal.vue';

const route = useRoute();
const router = useRouter();
const screensStore = useScreensStore();
const toastStore = useToastStore();

const screenId = computed(() => route.params.id);
const screen = computed(() => screensStore.currentScreen);

const showPromoModal = ref(false);
const editingSlide = ref(null);
const showInstagramModal = ref(false);
const editingInstagramSlide = ref(null);
const showScheduleModal = ref(false);
const schedulingSlide = ref(null);
const previewingVideo = ref(null);
const copied = ref(false);

const openVideoPreview = (slide) => {
    previewingVideo.value = slide;
};

onMounted(async () => {
    await screensStore.fetchScreen(screenId.value);
});

const handleReorder = async (newList) => {
    const slideIds = newList.map(s => s.id);
    await screensStore.reorderSlides(screenId.value, slideIds);
};

const handleToggleActive = async (slideId) => {
    await screensStore.toggleSlideActive(slideId);
};

const handleToggleAudio = async (slideId) => {
    const slide = screen.value?.slides.find(s => s.id === slideId);
    if (!slide) return;
    const newAudio = !slide.audio_enabled;
    await screensStore.updateSlide(slideId, { audio_enabled: newAudio });
    toastStore.info(
        newAudio ? 'Audio Enabled' : 'Audio Muted',
        newAudio ? 'Video will play with sound on TV' : 'Video sound muted on TV'
    );
};

const handleUpdateTiming = async ({ slideId, duration }) => {
    await screensStore.updateSlide(slideId, { duration_override: duration });
    toastStore.success('Timing Updated', `Slide duration set to ${duration} seconds.`);
};

const handleUpdateFitMode = async ({ slideId, fitMode }) => {
    await screensStore.updateSlide(slideId, { fit_mode: fitMode });
    const label = fitMode === 'cover' ? 'Full Screen (Edge-to-Edge)' : (fitMode === 'contain' ? 'Fit Screen (Letterbox)' : 'Boxed Card');
    toastStore.success('Display Mode Updated', `Slide set to ${label}.`);
};

const handleBatchFitMode = async (fitMode) => {
    try {
        await screensStore.batchUpdateFitMode(screenId.value, fitMode);
    } catch (e) {}
};

const handleDeleteSlide = async (slideId) => {
    if (confirm('Are you sure you want to remove this slide?')) {
        await screensStore.deleteSlide(slideId);
    }
};

const openPromoModal = (slide = null) => {
    editingSlide.value = slide;
    showPromoModal.value = true;
};

const openInstagramModal = (slide = null) => {
    editingInstagramSlide.value = slide;
    showInstagramModal.value = true;
};

const openScheduleModal = (slide) => {
    schedulingSlide.value = slide;
    showScheduleModal.value = true;
};

const copyUrl = async () => {
    const textToCopy = screen.value?.short_url ? `https://${screen.value.short_url}` : screen.value?.public_url;
    if (!textToCopy) return;
    try {
        await navigator.clipboard.writeText(textToCopy);
        copied.value = true;
        toastStore.success('Copied Short Link', `${screen.value?.short_url || textToCopy} copied to clipboard.`);
        setTimeout(() => { copied.value = false; }, 2000);
    } catch (e) {
        toastStore.error('Copy Failed', 'Unable to access clipboard');
    }
};

const refreshScreen = async () => {
    await screensStore.fetchScreen(screenId.value);
};
</script>

<template>
    <AdminLayout>
        <div v-if="screensStore.loading && !screen" class="space-y-6">
            <SkeletonLoader type="text" :count="2" />
            <SkeletonLoader type="slide" :count="4" />
        </div>

        <div v-else-if="screen" class="space-y-8">
            <!-- Top Header & Navigation -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-zinc-800/80">
                <div class="space-y-2">
                    <router-link
                        to="/"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-400 hover:text-zinc-200 transition-colors"
                    >
                        <ArrowLeft class="w-3.5 h-3.5" />
                        Back to Screens
                    </router-link>

                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-100">
                            {{ screen.name }}
                        </h1>

                        <!-- Status Pill -->
                        <div
                            class="flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold border backdrop-blur-md"
                            :class="screen.is_online
                                ? 'bg-emerald-950/80 text-emerald-300 border-emerald-500/30'
                                : 'bg-zinc-900/80 text-zinc-400 border-zinc-700/50'"
                        >
                            <span
                                class="w-2 h-2 rounded-full"
                                :class="screen.is_online ? 'bg-emerald-400 animate-ping' : 'bg-zinc-500'"
                            />
                            <span>{{ screen.is_online ? 'Live on TV' : 'Offline' }}</span>
                        </div>

                        <!-- Short TV Link Pill -->
                        <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono font-medium bg-amber-500/10 text-amber-300 border border-amber-500/30">
                            <span class="text-[10px] uppercase font-sans font-bold text-amber-400">TV:</span>
                            <span>{{ screen.short_url || ('trotiluxe.ma/v/' + (screen.short_code || 1)) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs / Links -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="copyUrl"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 border border-zinc-800 text-xs font-semibold transition-colors cursor-pointer"
                    >
                        <Check v-if="copied" class="w-3.5 h-3.5 text-emerald-400" />
                        <Copy v-else class="w-3.5 h-3.5" />
                        <span>{{ copied ? 'Copied' : 'Copy TV Link' }}</span>
                    </button>

                    <router-link
                        :to="`/screens/${screen.id}/settings`"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 border border-zinc-800 text-xs font-semibold transition-colors"
                    >
                        <Settings class="w-3.5 h-3.5" />
                        <span>Settings</span>
                    </router-link>

                    <router-link
                        :to="`/screens/${screen.id}/analytics`"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-zinc-300 border border-zinc-800 text-xs font-semibold transition-colors"
                    >
                        <BarChart3 class="w-3.5 h-3.5" />
                        <span>Analytics</span>
                    </router-link>

                    <a
                        :href="screen.public_url"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/20 active:scale-95"
                    >
                        <span>Preview Live TV</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>

            <!-- Content Area: Upload Zone + Actions + Slide List -->
            <div class="space-y-6">
                <!-- Upload & Action Bar -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-100">Playlist Slides</h2>
                        <p class="text-xs text-zinc-400">
                            Drag to reorder sequence. Tweak sound (🔊), timing (5s-30s), and ambient glass fit mode inline.
                        </p>
                    </div>

                    <!-- Quick Display Mode Batch Actions + Create Actions -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center rounded-xl bg-zinc-900/90 p-1 border border-zinc-800 shadow-sm" title="Apply display mode to all slides in this playlist">
                            <button
                                @click="handleBatchFitMode('cover')"
                                type="button"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-zinc-300 hover:text-emerald-300 hover:bg-emerald-950/40 transition-all cursor-pointer"
                                title="Set all slides to Full Screen (Edge-to-Edge, unboxed)"
                            >
                                <Maximize2 class="w-3.5 h-3.5 text-emerald-400" />
                                <span>All Full Screen</span>
                            </button>
                            <span class="w-px h-4 bg-zinc-800 mx-0.5" />
                            <button
                                @click="handleBatchFitMode('ambient_blur')"
                                type="button"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-zinc-300 hover:text-amber-300 hover:bg-amber-950/40 transition-all cursor-pointer"
                                title="Set all slides to Boxed Card (Ambient Glass Blur)"
                            >
                                <Square class="w-3.5 h-3.5 text-amber-400" />
                                <span>All Boxed</span>
                            </button>
                        </div>

                        <button
                            @click="openInstagramModal()"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-rose-500 to-amber-500 hover:from-pink-400 hover:to-amber-400 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-pink-500/20 active:scale-95 cursor-pointer shrink-0"
                        >
                            <Instagram class="w-4 h-4 text-white" />
                            <span>Add Instagram Post / Reel</span>
                        </button>

                        <button
                            @click="openPromoModal()"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-zinc-200 font-bold text-xs uppercase tracking-wider transition-all shadow-md cursor-pointer shrink-0"
                        >
                            <Sparkles class="w-4 h-4 text-amber-400" />
                            <span>Design Promo Card</span>
                        </button>
                    </div>
                </div>

                <!-- Media Upload Dropzone -->
                <MediaUploadZone
                    :screen-id="screen.id"
                    @uploaded="refreshScreen"
                />

                <!-- Reorderable Slide List -->
                <div class="pt-4">
                    <SlideList
                        :slides="screen.slides"
                        :default-duration="screen.default_slide_duration"
                        @reorder="handleReorder"
                        @toggle-active="handleToggleActive"
                        @toggle-audio="handleToggleAudio"
                        @update-timing="handleUpdateTiming"
                        @update-fit-mode="handleUpdateFitMode"
                        @edit-schedule="openScheduleModal"
                        @edit-promo="openPromoModal"
                        @edit-instagram="openInstagramModal"
                        @preview-video="openVideoPreview"
                        @delete="handleDeleteSlide"
                    />
                </div>
            </div>
        </div>

        <!-- Video Player Preview Modal -->
        <Modal
            :show="!!previewingVideo"
            :title="previewingVideo ? `Video Preview: ${previewingVideo.title}` : 'Video Preview'"
            max-width="max-w-3xl"
            @close="previewingVideo = null"
        >
            <div v-if="previewingVideo" class="space-y-4">
                <div class="rounded-2xl overflow-hidden bg-black aspect-video border border-zinc-800 shadow-2xl flex items-center justify-center">
                    <video
                        :src="resolveMediaUrl(previewingVideo.file_url)"
                        controls
                        autoplay
                        playsinline
                        class="w-full h-full object-contain"
                    />
                </div>

                <div class="flex items-center justify-between text-xs text-zinc-400 px-1">
                    <span>Format: Video (MP4/WebM)</span>
                    <span class="font-mono text-zinc-500">File: {{ previewingVideo.file_path }}</span>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="previewingVideo = null"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
                >
                    Close Preview
                </button>
            </template>
        </Modal>

        <!-- Instagram Slide Modal -->
        <InstagramSlideModal
            :show="showInstagramModal"
            :screen-id="screenId"
            :slide="editingInstagramSlide"
            @close="showInstagramModal = false"
            @saved="refreshScreen"
        />

        <!-- HTML Promo Modal -->
        <PromoSlideModal
            :show="showPromoModal"
            :screen-id="screenId"
            :slide="editingSlide"
            @close="showPromoModal = false"
            @saved="refreshScreen"
        />

        <!-- Schedule Modal -->
        <ScheduleModal
            :show="showScheduleModal"
            :slide="schedulingSlide"
            @close="showScheduleModal = false"
            @saved="refreshScreen"
        />
    </AdminLayout>
</template>
