<script setup>
import { X } from 'lucide-vue-next';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: 'max-w-xl',
    },
});

defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200 transition"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-150 transition"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="$emit('close')"
            >
                <div
                    class="relative w-full rounded-2xl bg-zinc-900 border border-zinc-800 shadow-2xl p-6 overflow-hidden transform transition-all"
                    :class="maxWidth"
                >
                    <div class="flex items-center justify-between pb-4 border-b border-zinc-800/80 mb-5">
                        <h3 class="text-lg font-semibold text-zinc-100">{{ title }}</h3>
                        <button
                            @click="$emit('close')"
                            class="p-1 rounded-lg text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800 transition-colors cursor-pointer"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="mt-6 pt-4 border-t border-zinc-800/80 flex justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
