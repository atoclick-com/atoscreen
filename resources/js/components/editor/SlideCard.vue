<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import ToggleSwitch from '@/components/common/ToggleSwitch.vue';
import { resolveMediaUrl } from '@/api/media';
import {
    GripVertical,
    Image,
    Film,
    Sparkles,
    Calendar,
    Clock,
    Trash2,
    Edit3,
    Play,
    Volume2,
    VolumeX,
    Instagram,
    Layers,
    Maximize2,
    Minimize2,
    Square,
    ChevronDown,
    Check,
} from 'lucide-vue-next';

const props = defineProps({
    slide: {
        type: Object,
        required: true,
    },
    defaultDuration: {
        type: Number,
        default: 10,
    },
});

const emit = defineEmits([
    'toggle-active',
    'edit-schedule',
    'edit-promo',
    'edit-instagram',
    'update-timing',
    'toggle-audio',
    'update-fit-mode',
    'delete',
    'preview-video',
]);

const thumbnailSrc = computed(() => {
    let raw = null;
    if (props.slide.type === 'image' || props.slide.type === 'video') {
        raw = props.slide.file_url;
    } else if (props.slide.type === 'html_promo' && props.slide.content?.image_url) {
        raw = props.slide.content.image_url;
    } else if (props.slide.type === 'instagram') {
        raw = props.slide.content?.media_url || props.slide.content?.image_url || props.slide.file_url;
    }
    return resolveMediaUrl(raw);
});

const hasSchedule = computed(() => {
    return !!(props.slide.start_date || props.slide.end_date || (props.slide.day_of_week_schedule && props.slide.day_of_week_schedule.length));
});

const isAudioSupported = computed(() => {
    return props.slide.type === 'video' || props.slide.type === 'instagram';
});

const quickDurations = [5, 10, 15, 30];

const currentDuration = computed(() => {
    return props.slide.duration_override || props.defaultDuration;
});

const showFitMenu = ref(false);
const fitMenuRef = ref(null);

const selectFitMode = (mode) => {
    showFitMenu.value = false;
    emit('update-fit-mode', { slideId: props.slide.id, fitMode: mode });
};

const handleClickOutside = (e) => {
    if (fitMenuRef.value && !fitMenuRef.value.contains(e.target)) {
        showFitMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div
        class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3.5 p-3.5 rounded-2xl border transition-all duration-200 group bg-zinc-900/60 backdrop-blur-sm"
        :class="slide.active
            ? 'border-zinc-800/90 hover:border-zinc-700'
            : 'border-zinc-900 bg-zinc-950/40 opacity-60'"
    >
        <div class="flex items-center gap-3.5 min-w-0 flex-1">
            <!-- Drag Handle -->
            <div class="drag-handle cursor-grab active:cursor-grabbing text-zinc-600 hover:text-zinc-300 p-1 rounded transition-colors shrink-0">
                <GripVertical class="w-5 h-5" />
            </div>

            <!-- Thumbnail Preview -->
            <div
                class="w-24 h-16 rounded-xl overflow-hidden bg-black shrink-0 relative border border-zinc-800 flex items-center justify-center group/thumb"
                :class="slide.type === 'video' ? 'cursor-pointer hover:border-amber-500/50' : ''"
                @click="slide.type === 'video' ? $emit('preview-video', slide) : null"
            >
                <!-- Video Thumbnail -->
                <template v-if="slide.type === 'video'">
                    <video
                        :src="slide.file_url"
                        preload="metadata"
                        muted
                        class="w-full h-full object-cover pointer-events-none"
                    />
                    <div class="absolute inset-0 bg-black/30 flex items-center justify-center group-hover/thumb:bg-black/10 transition-colors">
                        <div class="w-6 h-6 rounded-full bg-black/60 border border-white/20 flex items-center justify-center text-amber-400 backdrop-blur-sm shadow-md">
                            <Play class="w-3 h-3 ml-0.5 fill-amber-400" />
                        </div>
                    </div>
                </template>

                <!-- Instagram Thumbnail -->
                <template v-else-if="slide.type === 'instagram'">
                    <img
                        v-if="thumbnailSrc"
                        :src="thumbnailSrc"
                        :alt="slide.title"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="flex flex-col items-center justify-center text-pink-400">
                        <Instagram class="w-6 h-6" />
                    </div>
                </template>

                <!-- Image Thumbnail -->
                <img
                    v-else-if="slide.type === 'image' && slide.file_url"
                    :src="slide.file_url"
                    :alt="slide.title"
                    class="w-full h-full object-cover"
                />

                <!-- HTML Promo Thumbnail -->
                <img
                    v-else-if="slide.type === 'html_promo' && slide.content?.image_url"
                    :src="slide.content.image_url"
                    :alt="slide.title"
                    class="w-full h-full object-cover"
                />

                <!-- Fallback icon -->
                <div v-else class="flex flex-col items-center justify-center text-zinc-600">
                    <Sparkles v-if="slide.type === 'html_promo'" class="w-6 h-6 text-amber-500" />
                    <Image v-else class="w-6 h-6 text-zinc-600" />
                </div>

                <!-- Type Badge overlay on thumbnail -->
                <div
                    class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider backdrop-blur-sm flex items-center gap-0.5"
                    :class="[
                        slide.type === 'instagram' ? 'bg-gradient-to-r from-pink-600 to-purple-600 text-white' : 'bg-black/80 text-zinc-300'
                    ]"
                >
                    <Instagram v-if="slide.type === 'instagram'" class="w-2.5 h-2.5" />
                    <span>{{ slide.type === 'html_promo' ? 'PROMO' : (slide.type === 'instagram' ? 'REEL' : slide.type) }}</span>
                </div>
            </div>

            <!-- Slide Metadata & Title -->
            <div class="flex-1 min-w-0 space-y-1">
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-semibold text-zinc-200 truncate">
                        {{ slide.title || 'Untitled Slide' }}
                    </h4>
                    <span
                        v-if="!slide.active"
                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-zinc-800 text-zinc-500 border border-zinc-700/50 shrink-0"
                    >
                        Paused
                    </span>
                    <span
                        v-if="slide.fit_mode === 'cover'"
                        class="hidden md:inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20"
                        title="Full Screen Edge-to-Edge"
                    >
                        Full Screen
                    </span>
                    <span
                        v-else-if="slide.fit_mode === 'contain'"
                        class="hidden md:inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20"
                        title="Fit Screen (Letterbox)"
                    >
                        Fit Screen
                    </span>
                    <span
                        v-else-if="slide.fit_mode === 'ambient_blur'"
                        class="hidden md:inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20"
                        title="Boxed Card with Ambient Glass Reflection"
                    >
                        Boxed Card
                    </span>
                </div>

                <!-- Timing & Controls Bar -->
                <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-400">
                    <!-- Quick Timing Chips -->
                    <div class="flex items-center gap-1 text-zinc-400 text-[11px] font-mono">
                        <Clock class="w-3.5 h-3.5 text-zinc-500" />
                        <span class="font-bold text-zinc-300">{{ currentDuration }}s</span>
                        <div class="flex items-center gap-0.5 ml-1">
                            <button
                                v-for="d in quickDurations"
                                :key="d"
                                type="button"
                                @click="$emit('update-timing', { slideId: slide.id, duration: d })"
                                class="px-1.5 py-0.5 rounded text-[10px] transition-colors cursor-pointer"
                                :class="currentDuration === d
                                    ? 'bg-amber-500 text-zinc-950 font-bold'
                                    : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-400'"
                            >
                                {{ d }}s
                            </button>
                        </div>
                    </div>

                    <!-- Audio Sound Toggle Button (for video and instagram reels) -->
                    <button
                        v-if="isAudioSupported"
                        type="button"
                        @click="$emit('toggle-audio', slide.id)"
                        class="flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-lg border transition-all cursor-pointer font-medium"
                        :class="slide.audio_enabled
                            ? 'bg-emerald-950/70 border-emerald-500/40 text-emerald-300 hover:bg-emerald-900/70'
                            : 'bg-zinc-800/80 border-zinc-700/50 text-zinc-400 hover:bg-zinc-700/80'"
                        :title="slide.audio_enabled ? 'Audio sound is enabled on TV' : 'Audio sound is muted'"
                    >
                        <Volume2 v-if="slide.audio_enabled" class="w-3.5 h-3.5 text-emerald-400" />
                        <VolumeX v-else class="w-3.5 h-3.5 text-zinc-500" />
                        <span>{{ slide.audio_enabled ? 'Sound ON' : 'Muted' }}</span>
                    </button>

                    <!-- Display Mode Dropdown (Full Screen vs Boxed) -->
                    <div class="relative inline-block" ref="fitMenuRef">
                        <button
                            type="button"
                            @click.stop="showFitMenu = !showFitMenu"
                            class="flex items-center gap-1.5 text-[11px] px-2 py-0.5 rounded-lg border transition-all cursor-pointer font-medium"
                            :class="slide.fit_mode === 'cover'
                                ? 'bg-emerald-950/70 border-emerald-500/40 text-emerald-300 hover:bg-emerald-900/70'
                                : (slide.fit_mode === 'contain'
                                    ? 'bg-sky-950/70 border-sky-500/40 text-sky-300 hover:bg-sky-900/70'
                                    : 'bg-zinc-800/80 border-zinc-700/60 text-zinc-300 hover:bg-zinc-700/80')"
                            :title="slide.fit_mode === 'cover' ? 'Full Screen (Edge-to-Edge)' : (slide.fit_mode === 'contain' ? 'Fit Screen (Letterbox)' : 'Boxed Card (Ambient Blur)')"
                        >
                            <Maximize2 v-if="slide.fit_mode === 'cover'" class="w-3.5 h-3.5 text-emerald-400" />
                            <Minimize2 v-else-if="slide.fit_mode === 'contain'" class="w-3.5 h-3.5 text-sky-400" />
                            <Square v-else class="w-3.5 h-3.5 text-amber-400" />

                            <span>{{ slide.fit_mode === 'cover' ? 'Full Screen' : (slide.fit_mode === 'contain' ? 'Fit Screen' : 'Boxed Card') }}</span>
                            <ChevronDown class="w-3 h-3 opacity-60" />
                        </button>

                        <!-- Dropdown Menu -->
                        <div
                            v-if="showFitMenu"
                            class="absolute left-0 top-full mt-1.5 w-64 rounded-2xl bg-zinc-950 border border-zinc-800 shadow-2xl p-1.5 z-50 backdrop-blur-2xl"
                            @click.stop
                        >
                            <div class="px-2.5 py-1 text-[10px] font-bold text-zinc-500 uppercase tracking-wider border-b border-zinc-800/80 mb-1">
                                Display Mode on TV
                            </div>

                            <!-- Option 1: Full Screen (Cover) -->
                            <button
                                type="button"
                                @click="selectFitMode('cover')"
                                class="w-full flex items-start gap-2.5 px-2.5 py-2 rounded-xl text-left hover:bg-zinc-900 transition-colors cursor-pointer group"
                                :class="slide.fit_mode === 'cover' ? 'bg-emerald-950/40 text-emerald-300' : 'text-zinc-300'"
                            >
                                <div class="p-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0 mt-0.5">
                                    <Maximize2 class="w-3.5 h-3.5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold flex items-center justify-between">
                                        <span>Full Screen (Edge-to-Edge)</span>
                                        <Check v-if="slide.fit_mode === 'cover'" class="w-3.5 h-3.5 text-emerald-400" />
                                    </div>
                                    <p class="text-[10px] text-zinc-400 leading-tight mt-0.5">
                                        Fills the TV screen completely. Unboxed, no borders or black bars.
                                    </p>
                                </div>
                            </button>

                            <!-- Option 2: Fit Screen (Contain) -->
                            <button
                                type="button"
                                @click="selectFitMode('contain')"
                                class="w-full flex items-start gap-2.5 px-2.5 py-2 rounded-xl text-left hover:bg-zinc-900 transition-colors cursor-pointer group"
                                :class="slide.fit_mode === 'contain' ? 'bg-sky-950/40 text-sky-300' : 'text-zinc-300'"
                            >
                                <div class="p-1 rounded-lg bg-sky-500/10 text-sky-400 border border-sky-500/20 shrink-0 mt-0.5">
                                    <Minimize2 class="w-3.5 h-3.5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold flex items-center justify-between">
                                        <span>Fit Screen (Letterbox)</span>
                                        <Check v-if="slide.fit_mode === 'contain'" class="w-3.5 h-3.5 text-sky-400" />
                                    </div>
                                    <p class="text-[10px] text-zinc-400 leading-tight mt-0.5">
                                        Shows 100% of media uncropped with clean black bars. Not boxed.
                                    </p>
                                </div>
                            </button>

                            <!-- Option 3: Boxed Card (Ambient Blur) -->
                            <button
                                type="button"
                                @click="selectFitMode('ambient_blur')"
                                class="w-full flex items-start gap-2.5 px-2.5 py-2 rounded-xl text-left hover:bg-zinc-900 transition-colors cursor-pointer group"
                                :class="slide.fit_mode === 'ambient_blur' ? 'bg-amber-950/40 text-amber-300' : 'text-zinc-300'"
                            >
                                <div class="p-1 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 shrink-0 mt-0.5">
                                    <Square class="w-3.5 h-3.5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold flex items-center justify-between">
                                        <span>Boxed Card (Ambient Blur)</span>
                                        <Check v-if="slide.fit_mode === 'ambient_blur'" class="w-3.5 h-3.5 text-amber-400" />
                                    </div>
                                    <p class="text-[10px] text-zinc-400 leading-tight mt-0.5">
                                        Floating card with rounded corners, glass border & blur.
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Schedule indicator -->
                    <button
                        @click="$emit('edit-schedule', slide)"
                        class="flex items-center gap-1 text-[11px] rounded-md px-1.5 py-0.5 transition-colors cursor-pointer ml-auto sm:ml-0"
                        :class="hasSchedule ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30 font-medium' : 'text-zinc-500 hover:text-zinc-300'"
                    >
                        <Calendar class="w-3.5 h-3.5" />
                        <span>{{ hasSchedule ? 'Scheduled' : 'Schedule' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Action Controls -->
        <div class="flex items-center justify-end gap-2.5 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-zinc-800/50">
            <!-- Active/Inactive Toggle -->
            <div class="flex items-center gap-2" :title="slide.active ? 'Slide is active' : 'Slide is paused'">
                <ToggleSwitch
                    :model-value="slide.active"
                    @update:model-value="$emit('toggle-active', slide.id)"
                />
            </div>

            <!-- Edit Promo button (if html_promo) -->
            <button
                v-if="slide.type === 'html_promo'"
                @click="$emit('edit-promo', slide)"
                class="p-2 rounded-xl text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 border border-zinc-800 transition-colors cursor-pointer"
                title="Edit Promo Design"
            >
                <Edit3 class="w-4 h-4" />
            </button>

            <!-- Edit Instagram button (if instagram) -->
            <button
                v-if="slide.type === 'instagram'"
                @click="$emit('edit-instagram', slide)"
                class="p-2 rounded-xl text-pink-400 hover:text-pink-200 hover:bg-pink-500/10 border border-zinc-800 hover:border-pink-500/30 transition-colors cursor-pointer"
                title="Edit Instagram Post"
            >
                <Edit3 class="w-4 h-4" />
            </button>

            <!-- Delete button -->
            <button
                @click="$emit('delete', slide.id)"
                class="p-2 rounded-xl text-zinc-500 hover:text-rose-400 hover:bg-rose-500/10 border border-zinc-800 hover:border-rose-500/30 transition-colors cursor-pointer"
                title="Delete Slide"
            >
                <Trash2 class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
