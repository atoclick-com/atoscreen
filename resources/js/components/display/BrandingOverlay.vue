<script setup>
import { computed } from 'vue';
import { resolveMediaUrl } from '@/api/media';
import { UtensilsCrossed } from 'lucide-vue-next';

const props = defineProps({
    logoUrl: {
        type: String,
        default: null,
    },
    position: {
        type: String,
        default: 'top-right',
    },
    accentColor: {
        type: String,
        default: '#f59e0b',
    },
    businessName: {
        type: String,
        default: 'AtoFood',
    },
});

const resolvedLogoUrl = computed(() => resolveMediaUrl(props.logoUrl));

const positionClasses = computed(() => {
    switch (props.position) {
        case 'top-left': return 'top-6 left-6';
        case 'top-center': return 'top-6 left-1/2 -translate-x-1/2';
        case 'top-right': return 'top-6 right-6';
        case 'center-left': return 'top-1/2 left-6 -translate-y-1/2';
        case 'center': return 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2';
        case 'center-right': return 'top-1/2 right-6 -translate-y-1/2';
        case 'bottom-left': return 'bottom-16 left-6';
        case 'bottom-center': return 'bottom-16 left-1/2 -translate-x-1/2';
        case 'bottom-right': return 'bottom-16 right-6';
        default: return 'top-6 right-6';
    }
});
</script>

<template>
    <div
        class="absolute z-30 pointer-events-none select-none transition-all duration-300"
        :class="positionClasses"
    >
        <!-- Custom Uploaded Logo -->
        <div v-if="resolvedLogoUrl" class="p-2 rounded-2xl glass-pill shadow-2xl backdrop-blur-md">
            <img
                :src="resolvedLogoUrl"
                alt="Brand Logo"
                class="h-10 md:h-14 w-auto object-contain max-w-[200px] drop-shadow-lg"
            />
        </div>

        <!-- Default Sleek Brand Badge if no logo uploaded -->
        <div
            v-else
            class="flex items-center gap-2.5 px-4 py-2 rounded-2xl glass-pill shadow-2xl border border-white/10 backdrop-blur-md"
        >
            <div
                class="w-8 h-8 rounded-xl flex items-center justify-center text-zinc-950 font-bold shadow-md"
                :style="{ background: accentColor }"
            >
                <UtensilsCrossed class="w-4 h-4 text-zinc-950" />
            </div>
            <span class="text-white font-bold tracking-tight text-sm md:text-base drop-shadow">
                {{ businessName || 'AtoFood' }}
            </span>
        </div>
    </div>
</template>
