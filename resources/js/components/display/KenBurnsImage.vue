<script setup>
defineProps({
    src: {
        type: String,
        required: true,
    },
    alt: {
        type: String,
        default: 'Digital Signage Display',
    },
    effect: {
        type: String,
        default: 'ken-burns', // ken-burns or static
    },
    fitMode: {
        type: String,
        default: 'ambient_blur', // ambient_blur, cover, contain
    },
});
</script>

<template>
    <div class="absolute inset-0 w-full h-full overflow-hidden bg-black flex items-center justify-center select-none">
        <!-- Ambient Blurred Mirror Backdrop filling empty pillarbox spaces (only in ambient blur mode) -->
        <div
            v-if="fitMode === 'ambient_blur'"
            class="absolute inset-0 w-full h-full overflow-hidden pointer-events-none"
        >
            <img
                :src="src"
                :alt="alt"
                class="absolute inset-0 w-full h-full object-cover blur-3xl opacity-40 scale-125 brightness-75 pointer-events-none"
            />
            <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" />
        </div>

        <!-- Foreground Image Container -->
        <div
            class="relative z-10 flex items-center justify-center transition-all duration-300"
            :class="[
                fitMode === 'ambient_blur'
                    ? 'max-h-[96vh] max-w-[96vw] rounded-2xl overflow-hidden shadow-[0_0_80px_rgba(0,0,0,0.85)] border border-white/10 bg-black'
                    : 'w-full h-full'
            ]"
        >
            <img
                :src="src"
                :alt="alt"
                class="transition-all duration-1000"
                :class="[
                    fitMode === 'cover' ? 'w-full h-full object-cover' : '',
                    fitMode === 'contain' ? 'w-full h-full object-contain' : '',
                    fitMode === 'stretch' ? 'w-full h-full object-fill' : '',
                    fitMode === 'ambient_blur' ? 'max-h-[96vh] max-w-full object-contain' : '',
                    effect === 'ken-burns' && fitMode === 'cover' ? 'animate-ken-burns' : '',
                ]"
            />

            <!-- Ambient Glass Reflection Line -->
            <div
                v-if="fitMode === 'ambient_blur'"
                class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-transparent pointer-events-none"
            />
        </div>
    </div>
</template>
