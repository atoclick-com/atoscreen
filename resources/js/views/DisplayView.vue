<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import api from '@/api/client';
import { subscribeToScreenUpdates } from '@/api/realtimeSync';
import { resolveMediaUrl } from '@/api/media';
import KenBurnsImage from '@/components/display/KenBurnsImage.vue';
import VideoPlayer from '@/components/display/VideoPlayer.vue';
import HtmlPromoCard from '@/components/display/HtmlPromoCard.vue';
import InstagramCard from '@/components/display/InstagramCard.vue';
import ClockWidget from '@/components/display/ClockWidget.vue';
import MarqueeTicker from '@/components/display/MarqueeTicker.vue';
import BrandingOverlay from '@/components/display/BrandingOverlay.vue';
import { WifiOff, Maximize, AlertCircle, Volume2, VolumeX, Moon } from 'lucide-vue-next';

const route = useRoute();
const uuid = computed(() => route.params.code || route.params.uuid);

// State
const screen = ref(null);
const settings = ref(null);
const slides = ref([]);
const currentSlideIndex = ref(0);
const loading = ref(true);
const isOffline = ref(false);
const errorMessage = ref(null);
const playlistChecksum = ref(null);
const pendingPlaylist = ref(null);
const isUserUnmuted = ref(true);

// Timers & listeners
let slideTimer = null;
let pollTimer = null;
let pingTimer = null;
let mouseTimer = null;
let businessHoursTimer = null;
let cleanupSync = null;
const isMouseActive = ref(true);
const isOutsideOperatingHours = ref(false);

const currentSlide = computed(() => {
    if (!slides.value.length) return null;
    return slides.value[currentSlideIndex.value] || slides.value[0];
});

const transitionName = computed(() => {
    const effect = screen.value?.transition_effect || 'fade';
    if (effect === 'slide') return 'slide-horizontal';
    if (effect === 'zoom') return 'zoom-fade';
    if (effect === 'none') return 'no-transition';
    return 'fade-cross';
});

const currentFitMode = computed(() => {
    const screenMode = settings.value?.aspect_ratio_mode || 'cover';
    const slideMode = currentSlide.value?.fit_mode;
    if (!slideMode || slideMode === 'ambient_blur') {
        return screenMode;
    }
    return slideMode;
});

const isAudioActiveForSlide = computed(() => {
    if (!currentSlide.value) return false;
    if (currentSlide.value.type !== 'video' && currentSlide.value.type !== 'instagram') return false;
    if (settings.value?.audio_enabled) {
        return currentSlide.value.audio_enabled !== false;
    }
    return !!currentSlide.value.audio_enabled;
});

const audioVolumeLevel = computed(() => {
    return (settings.value?.audio_volume ?? 80) / 100;
});

// Cache key for offline playback
const cacheKey = computed(() => `signage_cache_${uuid.value}`);

// Check Operating Hours (e.g. 08:00 to 23:00)
const checkOperatingHours = () => {
    // If preview query parameter is present, force standby mode for testing
    if (route.query.preview_standby === '1' || route.query.standby === '1') {
        isOutsideOperatingHours.value = true;
        return;
    }

    if (!settings.value?.operating_hours_enabled) {
        isOutsideOperatingHours.value = false;
        return;
    }

    const openTime = settings.value?.opening_time;
    const closeTime = settings.value?.closing_time;
    if (!openTime || !closeTime) {
        isOutsideOperatingHours.value = false;
        return;
    }

    const now = new Date();
    const currentMins = now.getHours() * 60 + now.getMinutes();

    const [openH, openM] = openTime.split(':').map(Number);
    const [closeH, closeM] = closeTime.split(':').map(Number);
    const openMins = openH * 60 + openM;
    const closeMins = closeH * 60 + closeM;

    if (openMins <= closeMins) {
        // Normal daytime window (e.g. 08:00 to 16:15)
        isOutsideOperatingHours.value = currentMins < openMins || currentMins >= closeMins;
    } else {
        // Overnight window (e.g. 18:00 to 02:00)
        isOutsideOperatingHours.value = currentMins >= closeMins && currentMins < openMins;
    }
};

watch(
    () => [
        settings.value?.operating_hours_enabled,
        settings.value?.opening_time,
        settings.value?.closing_time,
        settings.value?.aspect_ratio_mode,
        route.query.preview_standby,
        route.query.standby,
    ],
    () => {
        checkOperatingHours();
    },
    { deep: true }
);

watch(
    [screen, settings],
    () => {
        if (screen.value) {
            const screenName = screen.value.name || 'Screen';
            const brand = settings.value?.business_name || localStorage.getItem('atofood_business_name') || 'TV Display';
            document.title = `${screenName} | ${brand}`;
        }
    },
    { immediate: true }
);

// Fetch playlist from API
const fetchPlaylist = async (isInitial = false, forceImmediate = false) => {
    try {
        const response = await api.get(`/display/${uuid.value}/playlist`);
        const data = response.data;

        isOffline.value = false;

        // If this is initial load or our first data
        if (isInitial || !screen.value) {
            screen.value = data.screen;
            settings.value = data.settings;
            slides.value = data.slides;
            playlistChecksum.value = data.checksum;

            checkOperatingHours();

            // Cache for offline resilience
            try {
                localStorage.setItem(cacheKey.value, JSON.stringify(data));
            } catch (e) {
                console.warn('LocalStorage cache failed:', e);
            }

            if (slides.value.length > 0) {
                preloadNextSlide();
                startSlideTimer();
            }
        } else {
            const isChecksumDifferent = data.checksum !== playlistChecksum.value;

            // Always update screen metadata and settings without interrupting slide playback
            screen.value = data.screen;
            settings.value = data.settings;
            checkOperatingHours();

            // If the playlist checksum is identical and not forced, DO NOT interrupt active playback!
            if (!isChecksumDifferent && !forceImmediate) {
                return;
            }

            console.log('[DisplayView] Playlist change detected, updating slides.');
            playlistChecksum.value = data.checksum;

            // Preserve current slide position if valid, else clamp
            const currentPlayingId = currentSlide.value?.id;
            const previousDuration = currentSlide.value?.duration;
            slides.value = data.slides;
            pendingPlaylist.value = null;

            if (slides.value.length > 0) {
                const foundIndex = slides.value.findIndex(s => s.id === currentPlayingId);
                if (foundIndex !== -1) {
                    currentSlideIndex.value = foundIndex;
                    // If duration changed or timer was lost, restart slide timer
                    const newDuration = slides.value[foundIndex]?.duration;
                    if (!slideTimer || (newDuration && newDuration !== previousDuration)) {
                        startSlideTimer();
                    }
                } else if (currentSlideIndex.value >= slides.value.length) {
                    currentSlideIndex.value = 0;
                    preloadNextSlide();
                    startSlideTimer();
                } else {
                    preloadNextSlide();
                    startSlideTimer();
                }
            } else {
                currentSlideIndex.value = 0;
                if (slideTimer) {
                    clearTimeout(slideTimer);
                    slideTimer = null;
                }
            }

            try {
                localStorage.setItem(cacheKey.value, JSON.stringify(data));
            } catch (e) {}
        }
    } catch (err) {
        console.warn('Playlist fetch failed, falling back to cache if available:', err);
        isOffline.value = true;

        if (isInitial) {
            // Try loading from offline cache
            const cached = localStorage.getItem(cacheKey.value);
            if (cached) {
                try {
                    const data = JSON.parse(cached);
                    screen.value = data.screen;
                    settings.value = data.settings;
                    slides.value = data.slides;
                    playlistChecksum.value = data.checksum;
                    if (slides.value.length > 0) {
                        startSlideTimer();
                    }
                } catch (e) {
                    errorMessage.value = 'Failed to load cached playlist';
                }
            } else {
                errorMessage.value = err.response?.data?.error || 'Unable to connect to display server.';
            }
        }
    } finally {
        loading.value = false;
    }
};

// Advance to next slide
const nextSlide = () => {
    if (!slides.value.length) return;

    // Check if there is a pending playlist update staged from background polling
    if (pendingPlaylist.value) {
        screen.value = pendingPlaylist.value.screen;
        settings.value = pendingPlaylist.value.settings;
        slides.value = pendingPlaylist.value.slides;
        playlistChecksum.value = pendingPlaylist.value.checksum;
        pendingPlaylist.value = null;

        checkOperatingHours();

        try {
            localStorage.setItem(cacheKey.value, JSON.stringify({
                screen: screen.value,
                settings: settings.value,
                slides: slides.value,
                checksum: playlistChecksum.value,
            }));
        } catch (e) {}

        currentSlideIndex.value = 0;
        preloadNextSlide();
        startSlideTimer();
        return;
    }

    // If only 1 slide and it's a video, let it loop
    if (slides.value.length === 1) {
        return;
    }

    currentSlideIndex.value = (currentSlideIndex.value + 1) % slides.value.length;
    preloadNextSlide();
    startSlideTimer();
};

const prevSlide = () => {
    if (!slides.value.length) return;
    currentSlideIndex.value = (currentSlideIndex.value - 1 + slides.value.length) % slides.value.length;
    preloadNextSlide();
    startSlideTimer();
};

// Double-buffer Preload next asset in browser cache to eliminate blank flash
const preloadNextSlide = () => {
    if (slides.value.length <= 1) return;
    const nextIndex = (currentSlideIndex.value + 1) % slides.value.length;
    const next = slides.value[nextIndex];

    if (!next) return;

    if (next.type === 'image' && next.file_url) {
        const img = new Image();
        img.src = resolveMediaUrl(next.file_url);
    } else if (next.type === 'html_promo' && next.content?.image_url) {
        const img = new Image();
        img.src = resolveMediaUrl(next.content.image_url);
    } else if (next.type === 'instagram' && next.content?.image_url) {
        const img = new Image();
        img.src = resolveMediaUrl(next.content.image_url);
    }
};

const isVideoSlide = (slide) => {
    if (!slide) return false;
    if (slide.type === 'video') return true;
    if (slide.type === 'instagram') {
        const mediaUrl = slide.file_url || slide.content?.media_url;
        if (mediaUrl) {
            const lower = mediaUrl.toLowerCase();
            return lower.includes('.mp4') || lower.includes('.webm') || lower.includes('.mov') || slide.content?.is_video === true || slide.content?.media_type === 'reel';
        }
        return false;
    }
    return false;
};

// Slide playback timer
const startSlideTimer = () => {
    if (slideTimer) clearTimeout(slideTimer);

    const slide = currentSlide.value;
    if (!slide) return;

    // If slide is a video or instagram video, do NOT scroll until the video ends!
    if (isVideoSlide(slide)) {
        // 10 minute safety fallback so screen never gets permanently stuck
        slideTimer = setTimeout(() => {
            console.warn('[DisplayView] Video slide safety timeout reached, advancing.');
            nextSlide();
        }, 600000);
        return;
    }

    const durationSeconds = slide.duration || screen.value?.default_slide_duration || 10;
    slideTimer = setTimeout(() => {
        nextSlide();
    }, durationSeconds * 1000);
};

// Heartbeat Ping (runs every 3.5s for fast TV sync)
const sendHeartbeat = async () => {
    if (!uuid.value) return;
    try {
        const response = await api.post(`/display/${uuid.value}/ping`, {
            current_slide_id: currentSlide.value?.id || null,
            duration_seconds: currentSlide.value?.duration || screen.value?.default_slide_duration || null,
        });
        isOffline.value = false;

        // If server indicates screen was modified, poll for fresh playlist
        if (response.data?.screen_updated_at && screen.value?.updated_at) {
            const serverTime = new Date(response.data.screen_updated_at).getTime();
            const localTime = new Date(screen.value.updated_at).getTime();
            if (serverTime - localTime > 1000) {
                console.log('[DisplayView] Server modification detected via ping heartbeat, fetching latest playlist.');
                fetchPlaylist(false, false);
            }
        }
    } catch (e) {
        isOffline.value = true;
    }
};

// Toggle Audio Unmute
const toggleAudio = () => {
    isUserUnmuted.value = !isUserUnmuted.value;
};

// Toggle Fullscreen on smart TV kiosk
const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch((err) => {
            console.warn('Fullscreen request failed:', err);
        });
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};

// Keyboard listener for kiosk operator (Space, Arrows, F, M)
const handleKeyDown = (e) => {
    if (e.key === 'ArrowRight' || e.key === ' ') {
        nextSlide();
    } else if (e.key === 'ArrowLeft') {
        prevSlide();
    } else if (e.key.toLowerCase() === 'f') {
        toggleFullscreen();
    } else if (e.key.toLowerCase() === 'm' || e.key.toLowerCase() === 's') {
        toggleAudio();
    }
};

// Hide mouse cursor after 3s of inactivity
const handleMouseMove = () => {
    isMouseActive.value = true;
    if (mouseTimer) clearTimeout(mouseTimer);
    mouseTimer = setTimeout(() => {
        isMouseActive.value = false;
    }, 3000);
};

onMounted(async () => {
    await fetchPlaylist(true);

    // Setup background fallback polling
    const interval = (settings.value?.auto_refresh_interval || 60) * 1000;
    pollTimer = setInterval(() => {
        fetchPlaylist(false);
    }, Math.max(10000, interval));

    // Setup fast Heartbeat ping every 3.5 seconds (detects updates on external TV screens in real time)
    sendHeartbeat();
    pingTimer = setInterval(sendHeartbeat, 3500);

    // Operating hours check every minute
    businessHoursTimer = setInterval(checkOperatingHours, 60000);

    // Real-time zero-latency broadcast sync for open tabs/windows
    cleanupSync = subscribeToScreenUpdates((payload) => {
        const targetId = payload?.screenId;
        const myId = String(screen.value?.id || '');
        const myCode = String(screen.value?.short_code || '');
        const myUuid = String(uuid.value || '');

        if (!targetId || targetId === myId || targetId === myCode || targetId === myUuid) {
            console.log('[DisplayView] Instant real-time update triggered by admin action!');
            fetchPlaylist(false, true);
        }
    });

    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('mousemove', handleMouseMove);
    handleMouseMove();

    // Unlock browser audio restrictions on any Smart TV remote keypress or screen tap
    const unlockAudio = () => {
        if (settings.value?.audio_enabled) {
            isUserUnmuted.value = true;
        }
        document.querySelectorAll('video').forEach(v => {
            if (settings.value?.audio_enabled) {
                v.muted = false;
                v.volume = audioVolumeLevel.value;
                v.play().catch(() => {});
            }
        });
    };
    window.addEventListener('click', unlockAudio, { passive: true });
    window.addEventListener('touchstart', unlockAudio, { passive: true });
});

onUnmounted(() => {
    if (cleanupSync) cleanupSync();
    if (slideTimer) clearTimeout(slideTimer);
    if (pollTimer) clearInterval(pollTimer);
    if (pingTimer) clearInterval(pingTimer);
    if (mouseTimer) clearTimeout(mouseTimer);
    if (businessHoursTimer) clearInterval(businessHoursTimer);
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('mousemove', handleMouseMove);
});
</script>

<template>
    <div
        class="fixed inset-0 w-screen h-screen bg-black overflow-hidden select-none"
        :class="isMouseActive ? 'cursor-default' : 'cursor-none'"
        @dblclick="toggleFullscreen"
    >
        <!-- Loading State -->
        <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950 text-white z-50">
            <div class="w-16 h-16 rounded-3xl border-2 border-amber-500/20 border-t-amber-500 animate-spin mb-6" />
            <h2 class="text-xl font-bold tracking-tight">Connecting to Screen...</h2>
            <p class="text-xs text-zinc-500 mt-2 font-mono">Screen ID: {{ uuid }}</p>
        </div>

        <!-- Outside Operating Hours / Night Standby State -->
        <div
            v-else-if="isOutsideOperatingHours"
            class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950 text-white p-8 text-center z-40 select-none"
        >
            <div class="w-20 h-20 rounded-3xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-amber-400 mb-6 shadow-2xl">
                <Moon class="w-10 h-10 animate-pulse text-amber-400" />
            </div>
            <h1 class="text-3xl font-extrabold mb-2">{{ settings?.business_name || screen?.name || 'AtoFood' }}</h1>
            <p class="text-zinc-400 max-w-md text-sm mb-4">
                We are currently closed for the night. See you soon!
            </p>
            <div class="px-5 py-2.5 rounded-2xl bg-zinc-900/80 border border-white/10 text-xs font-medium text-amber-300">
                Operating Hours: {{ settings?.opening_time }} — {{ settings?.closing_time }}
            </div>
        </div>

        <!-- Error State -->
        <div v-else-if="errorMessage && !slides.length" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950 text-white p-8 text-center z-50">
            <div class="w-16 h-16 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 mb-6">
                <AlertCircle class="w-8 h-8" />
            </div>
            <h2 class="text-2xl font-bold mb-2">Display Offline</h2>
            <p class="text-zinc-400 max-w-md text-sm mb-6">{{ errorMessage }}</p>
            <button
                @click="fetchPlaylist(true)"
                class="px-5 py-2.5 rounded-xl bg-zinc-800 hover:bg-zinc-700 text-white font-medium text-xs tracking-wider uppercase transition-colors"
            >
                Retry Connection
            </button>
        </div>

        <!-- Empty Playlist / Standby State -->
        <div v-else-if="!slides.length" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-950 text-white p-8 text-center">
            <div class="w-20 h-20 rounded-3xl bg-zinc-900 border border-zinc-800 flex items-center justify-center text-amber-400 mb-6 shadow-2xl">
                <Maximize class="w-10 h-10 animate-pulse" />
            </div>
            <h1 class="text-3xl font-extrabold mb-2">{{ screen?.name || 'AtoFood TV' }}</h1>
            <p class="text-zinc-400 max-w-md text-sm mb-6">Screen is online and waiting for promotional slides from the dashboard.</p>
            <div class="px-4 py-2 rounded-xl glass-pill text-xs font-mono text-zinc-400 border border-white/10">
                Display ID: {{ uuid }}
            </div>
        </div>

        <!-- Active Playlist Display Engine -->
        <template v-else>
            <!-- Transitions Container -->
            <Transition :name="transitionName" mode="out-in">
                <div :key="currentSlide.id + '_' + currentSlideIndex" class="absolute inset-0 w-full h-full">
                    <!-- Image Slide -->
                    <KenBurnsImage
                        v-if="currentSlide.type === 'image'"
                        :src="resolveMediaUrl(currentSlide.file_url)"
                        :alt="currentSlide.title"
                        :fit-mode="currentFitMode"
                    />

                    <!-- Video Slide -->
                    <VideoPlayer
                        v-else-if="currentSlide.type === 'video'"
                        :src="resolveMediaUrl(currentSlide.file_url)"
                        :loop="slides.length === 1"
                        :fit-mode="currentFitMode"
                        :audio-enabled="isAudioActiveForSlide"
                        :volume="audioVolumeLevel"
                        :unmuted="isUserUnmuted"
                        @ended="nextSlide"
                        @error="nextSlide"
                    />

                    <!-- Instagram Card Slide (Reels, TikTok & Square Posts) -->
                    <InstagramCard
                        v-else-if="currentSlide.type === 'instagram'"
                        :content="currentSlide.content"
                        :media-url="resolveMediaUrl(currentSlide.file_url || currentSlide.content?.media_url)"
                        :loop="slides.length === 1"
                        :audio-enabled="isAudioActiveForSlide && isUserUnmuted"
                        :volume="audioVolumeLevel"
                        :accent-color="settings?.accent_color || '#f59e0b'"
                        :fit-mode="currentFitMode"
                        @ended="nextSlide"
                        @error="nextSlide"
                    />

                    <!-- HTML Promo Card Slide -->
                    <HtmlPromoCard
                        v-else-if="currentSlide.type === 'html_promo'"
                        :content="currentSlide.content"
                        :orientation="screen?.orientation || 'landscape'"
                    />
                </div>
            </Transition>

            <!-- Brand Logo Overlay -->
            <BrandingOverlay
                v-if="settings?.logo_overlay_enabled"
                :logo-url="resolveMediaUrl(settings?.logo_url)"
                :position="settings?.logo_position"
                :accent-color="settings?.accent_color"
                :business-name="settings?.business_name"
            />

            <!-- Clock & Weather Widget -->
            <div
                v-if="settings?.clock_widget_enabled"
                class="absolute top-6 z-30 transition-all duration-300"
                :class="settings?.logo_position === 'top-left' ? 'right-6' : 'left-6'"
            >
                <ClockWidget
                    :format="settings?.clock_format"
                    :show-weather="settings?.weather_widget_enabled"
                    :city="settings?.weather_city"
                    :accent-color="settings?.accent_color"
                />
            </div>

            <!-- Bottom Marquee Ticker -->
            <MarqueeTicker
                v-if="settings?.ticker_enabled && settings?.ticker_text"
                :text="settings.ticker_text"
                :speed="settings.ticker_speed"
                :accent-color="settings.accent_color"
            />

            <!-- Floating Smart TV Audio Toggle Pill (Shows when audio is configured on video or screen) -->
            <button
                v-if="isAudioActiveForSlide"
                @click="toggleAudio"
                class="absolute bottom-6 right-6 z-40 flex items-center gap-2 px-4 py-2 rounded-full border backdrop-blur-xl shadow-2xl transition-all duration-300 cursor-pointer group"
                :class="isUserUnmuted
                    ? 'bg-emerald-950/80 border-emerald-500/40 text-emerald-300 hover:bg-emerald-900/80'
                    : 'bg-zinc-900/80 border-white/20 text-zinc-300 hover:bg-zinc-800/90 animate-bounce'"
                :title="isUserUnmuted ? 'Click to Mute Audio' : 'Click to Unmute Sound'"
            >
                <template v-if="isUserUnmuted">
                    <Volume2 class="w-4 h-4 text-emerald-400 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold">Sound On ({{ Math.round(audioVolumeLevel * 100) }}%)</span>
                </template>
                <template v-else>
                    <VolumeX class="w-4 h-4 text-amber-400 group-hover:scale-110 transition-transform" />
                    <span class="text-xs font-semibold">🔊 Click to Unmute</span>
                </template>
            </button>

            <!-- Offline Reconnect Indicator (subtle, non-intrusive) -->
            <div
                v-if="isOffline"
                class="absolute top-6 left-1/2 -translate-x-1/2 z-40 px-3 py-1.5 rounded-full bg-amber-500/20 border border-amber-500/40 backdrop-blur-md flex items-center gap-2 text-amber-200 text-xs font-medium shadow-2xl"
            >
                <WifiOff class="w-3.5 h-3.5 animate-pulse text-amber-400" />
                <span>Offline • Playing cached playlist</span>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* Crossfade Transition */
.fade-cross-enter-active,
.fade-cross-leave-active {
    transition: opacity 0.8s ease-in-out;
}
.fade-cross-enter-from,
.fade-cross-leave-to {
    opacity: 0;
}

/* Horizontal Slide Transition */
.slide-horizontal-enter-active,
.slide-horizontal-leave-active {
    transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.7s ease;
}
.slide-horizontal-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.slide-horizontal-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}

/* Zoom Transition */
.zoom-fade-enter-active,
.zoom-fade-leave-active {
    transition: transform 0.8s ease, opacity 0.8s ease;
}
.zoom-fade-enter-from {
    transform: scale(1.08);
    opacity: 0;
}
.zoom-fade-leave-to {
    transform: scale(0.95);
    opacity: 0;
}
</style>
