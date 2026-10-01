<script setup>
import { Tv, Film, Plus } from 'lucide-vue-next';

defineProps({
    icon: {
        type: String,
        default: 'tv', // tv, film
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
});

defineEmits(['action']);
</script>

<template>
    <div class="flex flex-col items-center justify-center p-12 text-center border border-dashed border-zinc-800 rounded-2xl bg-zinc-900/20 max-w-lg mx-auto my-8">
        <div class="w-16 h-16 rounded-2xl bg-zinc-800/60 border border-zinc-700/50 flex items-center justify-center text-zinc-400 mb-4 shadow-inner">
            <Tv v-if="icon === 'tv'" class="w-8 h-8 text-amber-400" />
            <Film v-else class="w-8 h-8 text-blue-400" />
        </div>
        <h3 class="text-lg font-semibold text-zinc-100 mb-1.5">{{ title }}</h3>
        <p v-if="description" class="text-sm text-zinc-400 max-w-sm mb-6 leading-relaxed">
            {{ description }}
        </p>
        <button
            v-if="actionLabel"
            @click="$emit('action')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-semibold text-sm transition-all shadow-lg shadow-amber-500/10 cursor-pointer"
        >
            <Plus class="w-4 h-4" />
            {{ actionLabel }}
        </button>
    </div>
</template>
