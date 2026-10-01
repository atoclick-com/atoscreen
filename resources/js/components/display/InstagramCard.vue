<script setup>
import { computed, ref, onMounted, watch } from 'vue';
import QRCode from 'qrcode';
import { resolveMediaUrl } from '@/api/media';
import { Heart, MessageCircle, Instagram, CheckCircle2, QrCode, ExternalLink, Play } from 'lucide-vue-next';

const props = defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
    mediaUrl: {
        type: String,
        default: null,
    },
    audioEnabled: {
        type: Boolean,
        default: false,
    },
    accentColor: {
        type: String,
        default: '#f59e0b',
    },
    fitMode: {
        type: String,
        default: 'ambient_blur',
    },
});

const isFullScreen = computed(() => {
    return props.fitMode === 'cover' || props.fitMode === 'contain' || props.fitMode === 'stretch';
});

const emit = defineEmits(['ended']);

const qrDataUrl = ref('');
const videoEl = ref(null);
const isPlaying = ref(false);

const postUrl = computed(() => {
    return props.content?.url || 'https://instagram.com';
});

// Extract shortcode and generate official embed URL if needed
const embedUrl = computed(() => {
    const url = props.content?.url || '';
    if (!url) return null;
    const match = url.match(/instagram\.com\/(?:p|reel|tv)\/([a-zA-Z0-9_-]+)/i);
    if (match && match[1]) {
        return `https://www.instagram.com/p/${match[1]}/embed/`;
    }
    return null;
});

const mediaSrc = computed(() => {
    const raw = props.mediaUrl || props.content?.media_url || props.content?.image_url || null;
    return resolveMediaUrl(raw);
});

const isVideo = computed(() => {
    if (props.content?.is_video === true) return true;
    if (!mediaSrc.value) return false;
    const url = mediaSrc.value.toLowerCase();
    return url.includes('.mp4') || url.includes('.webm') || url.includes('.mov') || url.includes('/videos/') || props.content?.media_format === 'video' || props.content?.media_type === 'reel';
});

const hasDirectMedia = computed(() => {
    if (isVideo.value) return true;
    if (!mediaSrc.value) return false;
    const url = mediaSrc.value.toLowerCase();
    return url.includes('.mp4') || url.includes('.jpg') || url.includes('.jpeg') || url.includes('.png') || url.includes('.webp') || url.includes('unsplash') || url.includes('/storage/');
});

const onVideoEnded = () => {
    console.log('[InstagramCard] Video playback ended. Notifying parent to advance slide.');
    emit('ended');
};

const onVideoError = (err) => {
    console.warn('[InstagramCard] Video playback error:', err);
    emit('ended');
};

const ensureAutoplay = () => {
    if (videoEl.value && isVideo.value) {
        videoEl.value.currentTime = 0;
        const playPromise = videoEl.value.play();
        if (playPromise !== undefined) {
            playPromise.then(() => {
                isPlaying.value = true;
            }).catch((err) => {
                console.warn('[InstagramCard] Autoplay with audio was restricted, muting to guarantee autoplay:', err);
                if (videoEl.value) {
                    videoEl.value.muted = true;
                    videoEl.value.play().then(() => {
                        isPlaying.value = true;
                    }).catch(e => console.warn('[InstagramCard] Muted autoplay also failed:', e));
                }
            });
        }
    }
};

const generateQr = async () => {
    if (postUrl.value) {
        try {
            qrDataUrl.value = await QRCode.toDataURL(postUrl.value, {
                width: 220,
                margin: 1,
                color: {
                    dark: '#09090b',
                    light: '#ffffff',
                },
            });
        } catch (e) {
            console.warn('QR code generation error:', e);
        }
    }
};

onMounted(() => {
    generateQr();
    ensureAutoplay();
});

watch(() => props.audioEnabled, (newVal) => {
    if (videoEl.value) {
        videoEl.value.muted = !newVal;
    }
});

watch(() => mediaSrc.value, () => {
    ensureAutoplay();
});

watch(() => props.content?.url, () => {
    generateQr();
});
</script>

<template>
    <div class="absolute inset-0 w-full h-full overflow-hidden bg-black flex items-center justify-center select-none">
        <!-- Ambient Blurred Mirror Backdrop filling the TV screen -->
        <div class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none">
            <!-- If direct media exists, mirror blur it -->
            <template v-if="hasDirectMedia">
                <video
                    v-if="isVideo"
                    :src="mediaSrc"
                    autoplay
                    muted
                    loop
                    playsinline
                    class="absolute inset-0 w-full h-full object-cover blur-3xl scale-125 opacity-40 brightness-75"
                />
                <img
                    v-else
                    :src="mediaSrc"
                    alt="Ambient Backdrop"
                    class="absolute inset-0 w-full h-full object-cover blur-3xl scale-125 opacity-40 brightness-75"
                />
            </template>
            <!-- Otherwise generate an animated Instagram brand gradient aura -->
            <div
                v-else
                class="absolute inset-0 w-full h-full bg-gradient-to-tr from-purple-950/60 via-rose-950/50 to-amber-950/40 blur-2xl scale-110"
            />
            <div class="absolute inset-0 bg-black/40 backdrop-blur-xl" />
        </div>

        <!-- Mode 1A: Full Screen Edge-to-Edge Direct Media (Reels / Posts without Box) -->
        <div v-if="hasDirectMedia && isFullScreen" class="relative z-10 w-full h-full flex items-center justify-center">
            <video
                v-if="isVideo"
                ref="videoEl"
                :src="mediaSrc"
                autoplay
                :muted="!audioEnabled"
                playsinline
                webkit-playsinline
                class="transition-all duration-300"
                :class="[
                    fitMode === 'cover' ? 'w-full h-full object-cover' : '',
                    fitMode === 'contain' ? 'w-full h-full object-contain' : '',
                    fitMode === 'stretch' ? 'w-full h-full object-fill' : '',
                ]"
                @ended="onVideoEnded"
                @error="onVideoError"
            />
            <img
                v-else
                :src="mediaSrc || 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1200&q=80'"
                alt="Instagram Post"
                class="transition-all duration-300"
                :class="[
                    fitMode === 'cover' ? 'w-full h-full object-cover' : '',
                    fitMode === 'contain' ? 'w-full h-full object-contain' : '',
                    fitMode === 'stretch' ? 'w-full h-full object-fill' : '',
                ]"
            />

            <!-- Subtle Gradient at Bottom for text readability -->
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-black/80 via-black/25 to-transparent pointer-events-none" />

            <!-- Floating Pill with Instagram profile & QR in corner -->
            <div class="absolute bottom-6 right-6 z-20 flex items-center gap-3.5 p-3 rounded-2xl bg-zinc-950/80 border border-white/15 backdrop-blur-xl shadow-2xl max-w-md">
                <div class="w-11 h-11 rounded-full p-0.5 bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 shrink-0">
                    <div class="w-full h-full rounded-full bg-zinc-900 overflow-hidden flex items-center justify-center text-white">
                        <img v-if="content.author_avatar" :src="content.author_avatar" class="w-full h-full object-cover" />
                        <Instagram v-else class="w-5 h-5 text-white" />
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="font-bold text-white text-xs truncate">{{ content.author || 'trotiluxe' }}</span>
                        <CheckCircle2 class="w-3.5 h-3.5 text-blue-400 fill-blue-400 shrink-0" />
                    </div>
                    <p class="text-[11px] text-zinc-300 line-clamp-1 mt-0.5">{{ content.caption || 'Follow us on Instagram' }}</p>
                </div>
                <div v-if="qrDataUrl" class="w-11 h-11 bg-white rounded-lg p-1 shrink-0 flex items-center justify-center shadow-md">
                    <img :src="qrDataUrl" class="w-full h-full object-contain" alt="QR" />
                </div>
            </div>
        </div>

        <!-- Mode 1B: Boxed Card with Side Info (Ambient Blur) -->
        <div
            v-else-if="hasDirectMedia"
            class="relative z-10 max-h-[94vh] w-full max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-center gap-6 md:gap-10 p-4 md:p-6"
        >
            <!-- Media Frame (9:16 vertical reel or 1:1 square) -->
            <div
                class="relative rounded-3xl overflow-hidden shadow-[0_0_90px_rgba(0,0,0,0.95)] border border-white/20 bg-zinc-950 shrink-0 flex items-center justify-center transition-all"
                :class="content.media_type === 'square' ? 'w-80 md:w-[480px] h-80 md:h-[480px]' : 'w-[360px] md:w-[460px] h-[78vh] md:h-[88vh]'"
            >
                <video
                    v-if="isVideo"
                    ref="videoEl"
                    :src="mediaSrc"
                    autoplay
                    :muted="!audioEnabled"
                    playsinline
                    webkit-playsinline
                    class="w-full h-full object-cover"
                    @ended="onVideoEnded"
                    @error="onVideoError"
                />
                <img
                    v-else
                    :src="mediaSrc || 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1200&q=80'"
                    alt="Instagram Post"
                    class="w-full h-full object-cover"
                />

                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none" />

                <!-- Floating Instagram Reel Pill Badge -->
                <div class="absolute top-4 left-4 flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/70 backdrop-blur-md border border-white/20 text-white text-xs font-semibold shadow-lg">
                    <Instagram class="w-3.5 h-3.5 text-pink-400" />
                    <span>{{ isVideo ? 'Instagram Reel' : 'Instagram Post' }}</span>
                </div>

                <!-- Video Playing Indicator Pill -->
                <div v-if="isVideo" class="absolute bottom-4 left-4 flex items-center gap-2 px-3 py-1 rounded-full bg-black/60 backdrop-blur-md border border-white/10 text-white text-[11px] font-medium">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse" />
                    <span>Auto-Playing Video</span>
                </div>
            </div>

            <!-- Side Card with Customer Scan QR & Interaction -->
            <div class="w-full max-w-sm p-6 rounded-3xl bg-zinc-900/90 border border-white/10 backdrop-blur-2xl shadow-2xl flex flex-col justify-between space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full p-0.5 bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 shadow-md">
                            <div class="w-full h-full rounded-full bg-zinc-900 overflow-hidden flex items-center justify-center text-white">
                                <img
                                    v-if="content.author_avatar"
                                    :src="content.author_avatar"
                                    class="w-full h-full object-cover"
                                />
                                <Instagram v-else class="w-6 h-6 text-white" />
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-white text-base">
                                    {{ content.author || 'trotiluxe' }}
                                </span>
                                <CheckCircle2 class="w-4 h-4 text-blue-400 fill-blue-400" />
                            </div>
                            <span class="text-xs text-zinc-400">Featured Social Media Highlight</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-gradient-to-tr from-purple-600 to-pink-500 text-white shadow-lg">
                        <Instagram class="w-5 h-5" />
                    </div>
                </div>

                <!-- Custom Caption or Tagline -->
                <div class="space-y-2">
                    <p class="text-sm text-zinc-200 leading-relaxed font-normal">
                        {{ content.caption || 'Follow us on Instagram for latest daily specials, behind-the-scenes videos, and customer features!' }}
                    </p>
                    <p class="text-xs text-amber-400 font-medium">
                        #trending #reels #viral #instadaily
                    </p>
                </div>

                <!-- TV Scan to Follow QR Code -->
                <div class="flex items-center gap-4 p-4 rounded-2xl bg-zinc-950/70 border border-white/10 shadow-inner">
                    <div class="w-24 h-24 bg-white rounded-xl p-1.5 shrink-0 shadow-md flex items-center justify-center">
                        <img v-if="qrDataUrl" :src="qrDataUrl" class="w-full h-full object-contain" alt="Scan QR" />
                        <QrCode v-else class="w-8 h-8 text-black" />
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                            Scan on Phone
                        </p>
                        <p class="text-xs text-zinc-400 leading-tight">
                            Point your camera to open this post directly on Instagram and follow us!
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mode 2: Fallback Official Instagram Embed (If direct media is not available) -->
        <div
            v-else-if="embedUrl"
            class="relative z-10 max-h-[96vh] w-full max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-center gap-6 md:gap-10 p-4 md:p-6"
        >
            <div class="relative rounded-3xl overflow-hidden shadow-[0_0_90px_rgba(0,0,0,0.95)] border border-white/20 bg-zinc-950 flex items-center justify-center w-[360px] md:w-[460px] h-[78vh] md:h-[88vh] shrink-0">
                <iframe
                    :src="embedUrl"
                    class="w-full h-full border-0 rounded-2xl bg-zinc-950"
                    allowtransparency="true"
                    allow="autoplay; encrypted-media; fullscreen"
                    scrolling="no"
                />

                <div class="absolute top-3 left-3 pointer-events-none flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/70 backdrop-blur-md border border-white/20 text-white text-[11px] font-semibold shadow-lg">
                    <Instagram class="w-3.5 h-3.5 text-pink-400" />
                    <span>Instagram Live Post</span>
                </div>
            </div>

            <!-- Side Card with Customer Scan QR & Interaction -->
            <div class="w-full max-w-sm p-6 rounded-3xl bg-zinc-900/90 border border-white/10 backdrop-blur-2xl shadow-2xl flex flex-col justify-between space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full p-0.5 bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 shadow-md">
                            <div class="w-full h-full rounded-full bg-zinc-900 overflow-hidden flex items-center justify-center text-white">
                                <Instagram class="w-6 h-6" />
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-white text-base">
                                    {{ content.author || 'Instagram' }}
                                </span>
                                <CheckCircle2 class="w-4 h-4 text-blue-400 fill-blue-400" />
                            </div>
                            <span class="text-xs text-zinc-400">Featured Social Media Highlight</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-gradient-to-tr from-purple-600 to-pink-500 text-white shadow-lg">
                        <Instagram class="w-5 h-5" />
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="text-sm text-zinc-200 leading-relaxed font-normal">
                        {{ content.caption || 'Follow us on Instagram for latest daily specials, behind-the-scenes videos, and customer features!' }}
                    </p>
                </div>

                <div class="flex items-center gap-4 p-4 rounded-2xl bg-zinc-950/70 border border-white/10 shadow-inner">
                    <div class="w-24 h-24 bg-white rounded-xl p-1.5 shrink-0 shadow-md flex items-center justify-center">
                        <img v-if="qrDataUrl" :src="qrDataUrl" class="w-full h-full object-contain" alt="Scan QR" />
                        <QrCode v-else class="w-8 h-8 text-black" />
                    </div>
                    <div class="space-y-1.5">
                        <p class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                            Scan on Phone
                        </p>
                        <p class="text-xs text-zinc-400 leading-tight">
                            Point your camera to open this post directly on Instagram and follow us!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
