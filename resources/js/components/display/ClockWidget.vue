<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { CloudSun, Clock } from 'lucide-vue-next';

const props = defineProps({
    format: {
        type: String,
        default: '24h', // 12h or 24h
    },
    showWeather: {
        type: Boolean,
        default: false,
    },
    city: {
        type: String,
        default: 'Paris',
    },
    accentColor: {
        type: String,
        default: '#f59e0b',
    },
});

const now = ref(new Date());
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

const formattedTime = computed(() => {
    const hours = now.value.getHours();
    const minutes = String(now.value.getMinutes()).padStart(2, '0');
    const seconds = String(now.value.getSeconds()).padStart(2, '0');

    if (props.format === '12h') {
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const h12 = hours % 12 || 12;
        return {
            time: `${h12}:${minutes}`,
            seconds,
            ampm,
        };
    }

    return {
        time: `${String(hours).padStart(2, '0')}:${minutes}`,
        seconds,
        ampm: '',
    };
});

const formattedDate = computed(() => {
    return now.value.toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
    });
});
</script>

<template>
    <div class="pointer-events-none select-none z-30 flex items-center gap-3">
        <div class="glass-pill px-5 py-2.5 rounded-2xl flex items-center gap-4 text-white shadow-2xl border border-white/10 backdrop-blur-xl">
            <!-- Time Display -->
            <div class="flex items-baseline gap-1.5 font-mono">
                <span class="text-2xl md:text-3xl font-bold tracking-tight text-white drop-shadow">
                    {{ formattedTime.time }}
                </span>
                <span class="text-xs md:text-sm text-zinc-400 font-medium">
                    :{{ formattedTime.seconds }}
                </span>
                <span v-if="formattedTime.ampm" class="text-xs font-semibold px-1 py-0.5 rounded bg-zinc-800/80 text-amber-400 ml-1">
                    {{ formattedTime.ampm }}
                </span>
            </div>

            <div class="h-6 w-px bg-zinc-700/60" />

            <!-- Date Display -->
            <div class="text-xs md:text-sm font-medium text-zinc-300 capitalize">
                {{ formattedDate }}
            </div>

            <!-- Weather Widget (Optional) -->
            <template v-if="showWeather">
                <div class="h-6 w-px bg-zinc-700/60" />
                <div class="flex items-center gap-1.5 text-xs md:text-sm font-medium text-amber-300">
                    <CloudSun class="w-4 h-4 text-amber-400" />
                    <span>22°C</span>
                    <span v-if="city" class="text-zinc-400 text-xs font-normal">({{ city }})</span>
                </div>
            </template>
        </div>
    </div>
</template>
