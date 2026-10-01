<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import SkeletonLoader from '@/components/common/SkeletonLoader.vue';
import api from '@/api/client';
import {
    ArrowLeft,
    BarChart3,
    Film,
    Sparkles,
    Eye,
    TrendingUp,
    Tv,
    Calendar,
} from 'lucide-vue-next';

const route = useRoute();
const screenId = computed(() => route.params.id);

const analytics = ref(null);
const loading = ref(true);

const fetchAnalytics = async () => {
    loading.value = true;
    try {
        const response = await api.get(`/screens/${screenId.value}/analytics`);
        analytics.value = response.data;
    } catch (e) {
        console.error('Failed to load analytics:', e);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchAnalytics();
});

const maxPlays = computed(() => {
    if (!analytics.value?.slides?.length) return 1;
    return Math.max(...analytics.value.slides.map(s => s.plays), 1);
});
</script>

<template>
    <AdminLayout>
        <div v-if="loading" class="space-y-6">
            <SkeletonLoader type="text" :count="2" />
            <SkeletonLoader type="card" :count="3" />
        </div>

        <div v-else-if="analytics" class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-zinc-800/80">
                <div class="space-y-1">
                    <router-link
                        :to="`/screens/${screenId}`"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-zinc-400 hover:text-zinc-200 transition-colors"
                    >
                        <ArrowLeft class="w-3.5 h-3.5" />
                        Back to Screen Slides
                    </router-link>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-100">
                        {{ analytics.screen_name }} Analytics
                    </h1>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-400">Status:</span>
                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-semibold"
                        :class="analytics.is_online ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-zinc-800 text-zinc-400'"
                    >
                        {{ analytics.is_online ? 'Online Heartbeat Active' : 'Offline' }}
                    </span>
                </div>
            </div>

            <!-- Stats KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="p-6 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 backdrop-blur-sm space-y-2">
                    <div class="flex items-center justify-between text-zinc-400">
                        <span class="text-xs font-semibold uppercase tracking-wider">Total Slide Plays</span>
                        <Eye class="w-4 h-4 text-amber-400" />
                    </div>
                    <p class="text-3xl font-black text-zinc-100">{{ analytics.total_plays }}</p>
                    <p class="text-[11px] text-zinc-500">Recorded via TV heartbeat callbacks</p>
                </div>

                <div class="p-6 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 backdrop-blur-sm space-y-2">
                    <div class="flex items-center justify-between text-zinc-400">
                        <span class="text-xs font-semibold uppercase tracking-wider">Active Slides</span>
                        <Film class="w-4 h-4 text-blue-400" />
                    </div>
                    <p class="text-3xl font-black text-zinc-100">{{ analytics.slides.filter(s => s.active).length }}</p>
                    <p class="text-[11px] text-zinc-500">In continuous rotation loop</p>
                </div>

                <div class="p-6 rounded-2xl bg-zinc-900/40 border border-zinc-800/80 backdrop-blur-sm space-y-2">
                    <div class="flex items-center justify-between text-zinc-400">
                        <span class="text-xs font-semibold uppercase tracking-wider">Peak Slide Impressions</span>
                        <TrendingUp class="w-4 h-4 text-emerald-400" />
                    </div>
                    <p class="text-3xl font-black text-emerald-400">{{ maxPlays }}</p>
                    <p class="text-[11px] text-zinc-500">Highest impressions on a single promo</p>
                </div>
            </div>

            <!-- Visual Bar Chart of Slide Plays -->
            <div class="p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 space-y-6">
                <div class="flex items-center justify-between pb-3 border-b border-zinc-800">
                    <div class="flex items-center gap-2">
                        <BarChart3 class="w-5 h-5 text-amber-400" />
                        <h3 class="font-bold text-base text-zinc-100">Slide Play Count Distribution</h3>
                    </div>
                    <span class="text-xs text-zinc-500">Plays per slide</span>
                </div>

                <div v-if="analytics.slides && analytics.slides.length" class="space-y-4">
                    <div
                        v-for="slide in analytics.slides"
                        :key="slide.slide_id"
                        class="space-y-1.5"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-zinc-200 truncate max-w-md">
                                {{ slide.title }}
                            </span>
                            <div class="flex items-center gap-2 font-mono">
                                <span class="font-bold text-amber-400">{{ slide.plays }} plays</span>
                                <span class="text-zinc-500">
                                    ({{ analytics.total_plays > 0 ? Math.round((slide.plays / analytics.total_plays) * 100) : 0 }}%)
                                </span>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full bg-zinc-950 rounded-full h-3 overflow-hidden border border-zinc-800/60 flex">
                            <div
                                class="bg-gradient-to-r from-amber-500 to-amber-400 h-full rounded-full transition-all duration-500"
                                :style="{ width: `${(slide.plays / maxPlays) * 100}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-zinc-500 text-xs">
                    No slide play data recorded yet. Leave the TV screen running to collect play metrics.
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
