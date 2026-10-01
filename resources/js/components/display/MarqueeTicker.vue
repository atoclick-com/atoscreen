<script setup>
import { computed } from 'vue';

const props = defineProps({
    text: {
        type: String,
        default: '',
    },
    speed: {
        type: Number,
        default: 25, // seconds
    },
    accentColor: {
        type: String,
        default: '#f59e0b',
    },
});

const durationStyle = computed(() => ({
    '--ticker-duration': `${props.speed}s`,
    borderTopColor: `${props.accentColor}40`,
}));
</script>

<template>
    <div
        v-if="text"
        class="absolute bottom-0 left-0 right-0 z-30 h-12 md:h-14 overflow-hidden border-t backdrop-blur-xl flex items-center pointer-events-none select-none bg-black/80"
        :style="durationStyle"
    >
        <!-- Ticker Tag / Label -->
        <div
            class="h-full px-4 md:px-6 flex items-center font-bold text-xs md:text-sm tracking-wider uppercase z-20 shrink-0 shadow-lg"
            :style="{
                background: accentColor,
                color: '#09090b',
            }"
        >
            <span>INFO</span>
        </div>

        <!-- Scrolling Ticker Container -->
        <div class="relative w-full overflow-hidden flex items-center">
            <div class="animate-marquee font-medium text-sm md:text-lg text-zinc-100 pl-4 tracking-wide">
                {{ text }} &nbsp;&nbsp;&nbsp;&nbsp;✦&nbsp;&nbsp;&nbsp;&nbsp; {{ text }} &nbsp;&nbsp;&nbsp;&nbsp;✦&nbsp;&nbsp;&nbsp;&nbsp;
            </div>
        </div>
    </div>
</template>
