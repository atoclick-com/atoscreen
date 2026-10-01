<script setup>
import { useToastStore } from '@/stores/toast';
import { CheckCircle2, AlertTriangle, AlertCircle, Info, X } from 'lucide-vue-next';

const toastStore = useToastStore();

const getIcon = (type) => {
    switch (type) {
        case 'success': return CheckCircle2;
        case 'warning': return AlertTriangle;
        case 'error': return AlertCircle;
        default: return Info;
    }
};

const getBorderColor = (type) => {
    switch (type) {
        case 'success': return 'border-emerald-500/30 text-emerald-400';
        case 'warning': return 'border-amber-500/30 text-amber-400';
        case 'error': return 'border-rose-500/30 text-rose-400';
        default: return 'border-blue-500/30 text-blue-400';
    }
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none">
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-4 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-for="toast in toastStore.toasts"
                :key="toast.id"
                class="pointer-events-auto flex items-start gap-3 p-4 rounded-xl bg-zinc-900/95 dark:bg-zinc-900/95 border shadow-2xl backdrop-blur-md"
                :class="getBorderColor(toast.type)"
            >
                <component :is="getIcon(toast.type)" class="w-5 h-5 shrink-0 mt-0.5" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-zinc-100">{{ toast.title }}</p>
                    <p v-if="toast.message" class="text-xs text-zinc-400 mt-0.5 leading-relaxed">{{ toast.message }}</p>
                </div>
                <button
                    @click="toastStore.remove(toast.id)"
                    class="text-zinc-500 hover:text-zinc-200 transition-colors p-1 -mr-1 -mt-1 rounded-md"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
