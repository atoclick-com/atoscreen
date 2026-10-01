<script setup>
defineProps({
    type: {
        type: String,
        default: 'card', // card, text, slide, row
    },
    count: {
        type: Number,
        default: 1,
    },
});
</script>

<template>
    <div class="space-y-4 w-full">
        <!-- Card Skeleton (for Screens grid) -->
        <template v-if="type === 'card'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="n in count"
                    :key="n"
                    class="rounded-2xl border border-zinc-800/80 bg-zinc-900/40 p-5 space-y-4 overflow-hidden"
                >
                    <div class="h-44 w-full rounded-xl skeleton-pulse" />
                    <div class="space-y-2">
                        <div class="h-5 w-2/3 rounded-md skeleton-pulse" />
                        <div class="h-4 w-1/3 rounded-md skeleton-pulse" />
                    </div>
                    <div class="pt-3 border-t border-zinc-800/60 flex justify-between items-center">
                        <div class="h-4 w-20 rounded-md skeleton-pulse" />
                        <div class="h-8 w-24 rounded-lg skeleton-pulse" />
                    </div>
                </div>
            </div>
        </template>

        <!-- Slide item skeleton -->
        <template v-else-if="type === 'slide'">
            <div
                v-for="n in count"
                :key="n"
                class="flex items-center gap-4 p-4 rounded-xl border border-zinc-800/80 bg-zinc-900/40"
            >
                <div class="w-8 h-8 rounded-md skeleton-pulse" />
                <div class="w-24 h-16 rounded-lg skeleton-pulse shrink-0" />
                <div class="flex-1 space-y-2">
                    <div class="h-4 w-1/2 rounded skeleton-pulse" />
                    <div class="h-3 w-1/4 rounded skeleton-pulse" />
                </div>
                <div class="flex gap-2">
                    <div class="w-12 h-6 rounded-full skeleton-pulse" />
                    <div class="w-8 h-8 rounded-lg skeleton-pulse" />
                </div>
            </div>
        </template>

        <!-- Plain text/row skeleton -->
        <template v-else>
            <div v-for="n in count" :key="n" class="h-6 w-full rounded-md skeleton-pulse" />
        </template>
    </div>
</template>
