<script setup>
import { ref, computed, onMounted, watch } from 'vue';

const props = defineProps({
    src: {
        type: String,
        required: true,
    },
    loop: {
        type: Boolean,
        default: false,
    },
    fitMode: {
        type: String,
        default: 'ambient_blur', // ambient_blur, cover, contain
    },
    audioEnabled: {
        type: Boolean,
        default: false,
    },
    volume: {
        type: Number,
        default: 0.8,
    },
    unmuted: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['ended', 'error']);

const fgVideoRef = ref(null);
const bgVideoRef = ref(null);

const shouldPlayAudio = computed(() => {
    return props.audioEnabled && props.unmuted;
});

const syncVideos = () => {
    if (fgVideoRef.value) {
        fgVideoRef.value.volume = Math.min(1, Math.max(0, props.volume));
        fgVideoRef.value.muted = !shouldPlayAudio.value;
        fgVideoRef.value.currentTime = 0;
        fgVideoRef.value.play().catch((err) => {
            console.warn('Foreground video play blocked or waiting:', err);
        });
    }

    if (bgVideoRef.value && props.fitMode === 'ambient_blur') {
        bgVideoRef.value.muted = true;
        bgVideoRef.value.currentTime = 0;
        bgVideoRef.value.play().catch(() => {});
    }
};

const handleTimeUpdate = () => {
    // Keep background video loosely synchronized if ambient blur is active
    if (bgVideoRef.value && fgVideoRef.value && props.fitMode === 'ambient_blur') {
        const diff = Math.abs(bgVideoRef.value.currentTime - fgVideoRef.value.currentTime);
        if (diff > 0.3) {
            bgVideoRef.value.currentTime = fgVideoRef.value.currentTime;
        }
    }
};

onMounted(() => {
    syncVideos();
});

watch(() => props.src, () => {
    syncVideos();
});

watch(() => shouldPlayAudio.value, (newVal) => {
    if (fgVideoRef.value) {
        fgVideoRef.value.muted = !newVal;
        fgVideoRef.value.volume = Math.min(1, Math.max(0, props.volume));
    }
});

watch(() => props.volume, (newVol) => {
    if (fgVideoRef.value) {
        fgVideoRef.value.volume = Math.min(1, Math.max(0, newVol));
    }
});

const onVideoEnded = () => {
    if (props.loop) {
        syncVideos();
    } else {
        emit('ended');
    }
};

const onVideoError = (err) => {
    console.error('Video error encountered:', err);
    emit('error', err);
};
</script>

<template>
    <div class="absolute inset-0 w-full h-full overflow-hidden bg-black flex items-center justify-center select-none">
        <!-- Ambient Blurred Mirror Backdrop for empty space filling -->
        <div
            v-if="fitMode === 'ambient_blur'"
            class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none"
        >
            <video
                ref="bgVideoRef"
                :src="src"
                autoplay
                muted
                loop
                playsinline
                webkit-playsinline
                class="absolute inset-0 w-full h-full object-cover blur-3xl scale-125 opacity-40 brightness-75 transition-opacity duration-700"
            />
            <!-- Dark subtle gradient glass mask over ambient blur -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-black/60 backdrop-blur-md" />
        </div>

        <!-- Foreground Video Container -->
        <div
            class="relative z-10 flex items-center justify-center transition-all duration-300"
            :class="[
                fitMode === 'ambient_blur'
                    ? 'max-h-[96vh] max-w-[96vw] rounded-2xl overflow-hidden shadow-[0_0_80px_rgba(0,0,0,0.85)] border border-white/10 bg-black'
                    : 'w-full h-full'
            ]"
        >
            <video
                ref="fgVideoRef"
                :src="src"
                autoplay
                :muted="!shouldPlayAudio"
                playsinline
                webkit-playsinline
                x5-playsinline
                :loop="loop"
                class="transition-all duration-300"
                :class="[
                    fitMode === 'cover' ? 'w-full h-full object-cover' : '',
                    fitMode === 'contain' ? 'w-full h-full object-contain' : '',
                    fitMode === 'stretch' ? 'w-full h-full object-fill' : '',
                    fitMode === 'ambient_blur' ? 'max-h-[96vh] max-w-full object-contain' : '',
                ]"
                @timeupdate="handleTimeUpdate"
                @ended="onVideoEnded"
                @error="onVideoError"
            />

            <!-- Ambient Glass Highlight Ribbon (Only in ambient_blur mode) -->
            <div
                v-if="fitMode === 'ambient_blur'"
                class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-transparent pointer-events-none"
            />
        </div>
    </div>
</template>
