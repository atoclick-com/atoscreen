<script setup>
import { ref, reactive, watch } from 'vue';
import Modal from '@/components/common/Modal.vue';
import { useScreensStore } from '@/stores/screens';
import { Calendar, Clock, Check, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    slide: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const screensStore = useScreensStore();
const saving = ref(false);

const daysOfWeek = [
    { id: 1, label: 'Mon', full: 'Monday' },
    { id: 2, label: 'Tue', full: 'Tuesday' },
    { id: 3, label: 'Wed', full: 'Wednesday' },
    { id: 4, label: 'Thu', full: 'Thursday' },
    { id: 5, label: 'Fri', full: 'Friday' },
    { id: 6, label: 'Sat', full: 'Saturday' },
    { id: 0, label: 'Sun', full: 'Sunday' },
];

const form = reactive({
    start_date: '',
    end_date: '',
    selectedDays: [],
});

watch(
    () => props.slide,
    (s) => {
        if (s) {
            form.start_date = s.start_date ? s.start_date.substring(0, 10) : '';
            form.end_date = s.end_date ? s.end_date.substring(0, 10) : '';
            form.selectedDays = Array.isArray(s.day_of_week_schedule) ? [...s.day_of_week_schedule] : [];
        }
    },
    { immediate: true }
);

const toggleDay = (dayId) => {
    const idx = form.selectedDays.indexOf(dayId);
    if (idx === -1) {
        form.selectedDays.push(dayId);
    } else {
        form.selectedDays.splice(idx, 1);
    }
};

const applyPreset = (preset) => {
    if (preset === 'all') {
        form.selectedDays = [];
    } else if (preset === 'weekdays') {
        form.selectedDays = [1, 2, 3, 4, 5];
    } else if (preset === 'weekends') {
        form.selectedDays = [6, 0];
    }
};

const clearDates = () => {
    form.start_date = '';
    form.end_date = '';
};

const handleSave = async () => {
    if (!props.slide) return;
    saving.value = true;
    try {
        await screensStore.updateSlide(props.slide.id, {
            start_date: form.start_date || null,
            end_date: form.end_date || null,
            day_of_week_schedule: form.selectedDays.length ? form.selectedDays : null,
        });
        emit('saved');
        emit('close');
    } catch (e) {
        console.error(e);
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Modal
        :show="show"
        title="Schedule Slide Playback"
        max-width="max-w-md"
        @close="$emit('close')"
    >
        <div class="space-y-6">
            <!-- Information Callout -->
            <div class="p-3.5 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-400 leading-relaxed">
                Configure auto-expiration or restrict this promotion to specific days of the week (e.g. Weekend brunch, Ramadan special).
            </div>

            <!-- Date Range Picker -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-semibold text-zinc-300 uppercase tracking-wider flex items-center gap-1.5">
                        <Calendar class="w-3.5 h-3.5 text-amber-400" />
                        Date Validity Range
                    </label>
                    <button
                        v-if="form.start_date || form.end_date"
                        type="button"
                        @click="clearDates"
                        class="text-[11px] text-amber-400 hover:underline cursor-pointer"
                    >
                        Clear dates (Permanent)
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <span class="text-[11px] text-zinc-500 mb-1 block">Start Date (optional)</span>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-200 focus:border-amber-500 outline-none"
                        />
                    </div>
                    <div>
                        <span class="text-[11px] text-zinc-500 mb-1 block">End Date (optional)</span>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="w-full px-3 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-200 focus:border-amber-500 outline-none"
                        />
                    </div>
                </div>
            </div>

            <!-- Day of Week Selector -->
            <div class="space-y-3">
                <label class="text-xs font-semibold text-zinc-300 uppercase tracking-wider flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5 text-amber-400" />
                    Day of Week Schedule
                </label>

                <!-- Presets -->
                <div class="flex gap-2">
                    <button
                        type="button"
                        @click="applyPreset('all')"
                        class="px-2.5 py-1 rounded-lg border text-[11px] font-medium transition-colors cursor-pointer"
                        :class="!form.selectedDays.length ? 'border-amber-500/40 bg-amber-500/10 text-amber-300' : 'border-zinc-800 text-zinc-400 hover:border-zinc-700'"
                    >
                        Every Day
                    </button>
                    <button
                        type="button"
                        @click="applyPreset('weekdays')"
                        class="px-2.5 py-1 rounded-lg border text-[11px] font-medium transition-colors cursor-pointer"
                        :class="form.selectedDays.length === 5 && !form.selectedDays.includes(0) ? 'border-amber-500/40 bg-amber-500/10 text-amber-300' : 'border-zinc-800 text-zinc-400 hover:border-zinc-700'"
                    >
                        Weekdays (Mon-Fri)
                    </button>
                    <button
                        type="button"
                        @click="applyPreset('weekends')"
                        class="px-2.5 py-1 rounded-lg border text-[11px] font-medium transition-colors cursor-pointer"
                        :class="form.selectedDays.length === 2 && form.selectedDays.includes(6) ? 'border-amber-500/40 bg-amber-500/10 text-amber-300' : 'border-zinc-800 text-zinc-400 hover:border-zinc-700'"
                    >
                        Weekends (Sat-Sun)
                    </button>
                </div>

                <!-- Days Buttons -->
                <div class="grid grid-cols-7 gap-1.5 pt-1">
                    <button
                        v-for="d in daysOfWeek"
                        :key="d.id"
                        type="button"
                        @click="toggleDay(d.id)"
                        class="h-10 rounded-xl border flex flex-col items-center justify-center text-xs font-semibold transition-all cursor-pointer"
                        :class="form.selectedDays.includes(d.id)
                            ? 'bg-amber-500 border-amber-400 text-zinc-950 shadow-md shadow-amber-500/10'
                            : 'bg-zinc-950 border-zinc-800 text-zinc-400 hover:border-zinc-700 hover:text-zinc-200'"
                    >
                        <span>{{ d.label }}</span>
                    </button>
                </div>
                <p class="text-[11px] text-zinc-500">
                    {{ form.selectedDays.length ? 'Plays only on selected days.' : 'Plays all 7 days of the week.' }}
                </p>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
            >
                Cancel
            </button>
            <button
                type="button"
                :disabled="saving"
                @click="handleSave"
                class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all disabled:opacity-50 cursor-pointer"
            >
                {{ saving ? 'Saving...' : 'Apply Schedule' }}
            </button>
        </template>
    </Modal>
</template>
