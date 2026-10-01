<script setup>
import { computed } from 'vue';
import { Sparkles, Flame, Tag } from 'lucide-vue-next';

const props = defineProps({
    content: {
        type: Object,
        default: () => ({}),
    },
    orientation: {
        type: String,
        default: 'landscape',
    },
});

const backgroundStyle = computed(() => {
    if (props.content.bg_gradient) {
        return { background: props.content.bg_gradient };
    }
    return {
        background: 'linear-gradient(135deg, #18181b 0%, #09090b 100%)',
    };
});

const accentColor = computed(() => props.content.accent_color || '#f59e0b');
</script>

<template>
    <div
        class="absolute inset-0 w-full h-full flex items-center justify-center p-8 md:p-16 select-none overflow-hidden"
        :style="backgroundStyle"
    >
        <!-- Subtle background texture / ambient glow -->
        <div
            class="absolute -top-32 -left-32 w-96 h-96 rounded-full blur-[140px] opacity-25 pointer-events-none"
            :style="{ background: accentColor }"
        />
        <div
            class="absolute -bottom-32 -right-32 w-96 h-96 rounded-full blur-[140px] opacity-20 pointer-events-none"
            :style="{ background: accentColor }"
        />

        <!-- Main Card Container -->
        <div
            class="relative z-10 w-full max-w-6xl rounded-3xl p-8 md:p-14 border shadow-2xl backdrop-blur-md flex"
            :class="orientation === 'portrait' ? 'flex-col gap-8' : 'flex-row items-center gap-12'"
            :style="{
                borderColor: `${accentColor}33`,
                background: 'rgba(18, 18, 22, 0.75)',
            }"
        >
            <!-- Left Info Column -->
            <div class="flex-1 space-y-6">
                <!-- Badge -->
                <div v-if="content.badge" class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm"
                     :style="{
                         background: `${accentColor}20`,
                         color: accentColor,
                         border: `1px solid ${accentColor}40`
                     }"
                >
                    <Flame class="w-4 h-4 animate-pulse" />
                    <span>{{ content.badge }}</span>
                </div>

                <!-- Headline -->
                <h1 class="text-4xl md:text-6xl font-extrabold text-white tracking-tight leading-none drop-shadow-md">
                    {{ content.headline || 'Special Promotion' }}
                </h1>

                <!-- Description -->
                <p v-if="content.description" class="text-lg md:text-2xl text-zinc-300 font-normal leading-relaxed max-w-xl">
                    {{ content.description }}
                </p>

                <!-- Pricing Callout -->
                <div v-if="content.price" class="pt-4 flex items-baseline gap-4">
                    <div
                        class="text-4xl md:text-6xl font-black tracking-tight"
                        :style="{ color: accentColor }"
                    >
                        {{ content.price }}
                    </div>
                    <span v-if="content.price_subtitle" class="text-sm md:text-lg text-zinc-400 font-medium">
                        {{ content.price_subtitle }}
                    </span>
                </div>
            </div>

            <!-- Right Image Column (if promo image exists) -->
            <div
                v-if="content.image_url"
                class="shrink-0 flex items-center justify-center"
                :class="orientation === 'portrait' ? 'w-full h-72' : 'w-96 md:w-[480px] h-80 md:h-[400px]'"
            >
                <div class="relative w-full h-full rounded-2xl overflow-hidden shadow-2xl border border-white/10 group">
                    <img
                        :src="content.image_url"
                        :alt="content.headline"
                        class="w-full h-full object-cover animate-ken-burns scale-105"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none" />
                </div>
            </div>
        </div>
    </div>
</template>
