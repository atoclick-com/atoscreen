<script setup>
defineProps({
    modelValue: {
        type: String,
        default: 'top-right',
    },
});

defineEmits(['update:modelValue']);

const positions = [
    { id: 'top-left', label: 'Top Left' },
    { id: 'top-center', label: 'Top Center' },
    { id: 'top-right', label: 'Top Right' },
    { id: 'center-left', label: 'Center Left' },
    { id: 'center', label: 'Center' },
    { id: 'center-right', label: 'Center Right' },
    { id: 'bottom-left', label: 'Bottom Left' },
    { id: 'bottom-center', label: 'Bottom Center' },
    { id: 'bottom-right', label: 'Bottom Right' },
];
</script>

<template>
    <div class="inline-grid grid-cols-3 gap-2 p-2 rounded-xl bg-zinc-950 border border-zinc-800 w-44">
        <button
            v-for="pos in positions"
            :key="pos.id"
            type="button"
            :title="pos.label"
            @click="$emit('update:modelValue', pos.id)"
            class="h-9 rounded-lg border transition-all flex items-center justify-center cursor-pointer"
            :class="modelValue === pos.id
                ? 'bg-amber-500/20 border-amber-500 text-amber-400 font-bold shadow-sm'
                : 'bg-zinc-900 border-zinc-800 text-zinc-500 hover:border-zinc-700 hover:text-zinc-300'"
        >
            <div
                class="w-2 h-2 rounded-full"
                :class="modelValue === pos.id ? 'bg-amber-400' : 'bg-zinc-600'"
            />
        </button>
    </div>
</template>
