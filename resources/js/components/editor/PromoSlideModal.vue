<script setup>
import { ref, reactive, watch, computed } from 'vue';
import Modal from '@/components/common/Modal.vue';
import { useScreensStore } from '@/stores/screens';
import { Sparkles, Flame, Eye } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    screenId: {
        type: String,
        required: true,
    },
    slide: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const screensStore = useScreensStore();
const saving = ref(false);

const gradientPresets = [
    { label: 'Onyx Dark', value: 'linear-gradient(135deg, #18181b 0%, #09090b 100%)', accent: '#f59e0b' },
    { label: 'Midnight Blue', value: 'linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%)', accent: '#38bdf8' },
    { label: 'Emerald Forest', value: 'linear-gradient(135deg, #14532d 0%, #064e3b 100%)', accent: '#34d399' },
    { label: 'Crimson Flame', value: 'linear-gradient(135deg, #450a0a 0%, #1c0505 100%)', accent: '#f87171' },
    { label: 'Royal Violet', value: 'linear-gradient(135deg, #3b0764 0%, #18032e 100%)', accent: '#c084fc' },
];

const form = reactive({
    title: '',
    duration_override: 10,
    badge: '🔥 CHEF SPECIAL',
    headline: 'Signature Truffle Burger',
    description: 'Dry-aged patty with caramelized onions, aged cheddar, and house black truffle sauce.',
    price: '$18.99',
    price_subtitle: 'Includes seasoned fries',
    image_url: 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80',
    bg_gradient: 'linear-gradient(135deg, #18181b 0%, #09090b 100%)',
    accent_color: '#f59e0b',
});

watch(
    () => props.slide,
    (newSlide) => {
        if (newSlide) {
            form.title = newSlide.title || '';
            form.duration_override = newSlide.duration_override || 10;
            const content = newSlide.content || {};
            form.badge = content.badge || '';
            form.headline = content.headline || '';
            form.description = content.description || '';
            form.price = content.price || '';
            form.price_subtitle = content.price_subtitle || '';
            form.image_url = content.image_url || '';
            form.bg_gradient = content.bg_gradient || gradientPresets[0].value;
            form.accent_color = content.accent_color || gradientPresets[0].accent;
        } else {
            form.title = 'New Special Promo';
            form.duration_override = 10;
            form.badge = '🔥 CHEF SPECIAL';
            form.headline = 'Signature Dish';
            form.description = 'Handcrafted with premium fresh seasonal ingredients.';
            form.price = '$19.00';
            form.price_subtitle = 'Available all day';
            form.image_url = 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80';
            form.bg_gradient = gradientPresets[0].value;
            form.accent_color = gradientPresets[0].accent;
        }
    },
    { immediate: true }
);

const selectPreset = (preset) => {
    form.bg_gradient = preset.value;
    form.accent_color = preset.accent;
};

const handleSave = async () => {
    saving.value = true;
    try {
        const payload = {
            type: 'html_promo',
            title: form.title || form.headline,
            duration_override: form.duration_override,
            content: {
                badge: form.badge,
                headline: form.headline,
                description: form.description,
                price: form.price,
                price_subtitle: form.price_subtitle,
                image_url: form.image_url,
                bg_gradient: form.bg_gradient,
                accent_color: form.accent_color,
            },
        };

        if (props.slide) {
            await screensStore.updateSlide(props.slide.id, payload);
        } else {
            // For new slide, create via formData or JSON
            const fd = new FormData();
            fd.append('type', 'html_promo');
            fd.append('title', form.title || form.headline);
            fd.append('duration_override', form.duration_override);
            fd.append('content[badge]', form.badge);
            fd.append('content[headline]', form.headline);
            fd.append('content[description]', form.description);
            fd.append('content[price]', form.price);
            fd.append('content[price_subtitle]', form.price_subtitle);
            fd.append('content[image_url]', form.image_url);
            fd.append('content[bg_gradient]', form.bg_gradient);
            fd.append('content[accent_color]', form.accent_color);

            await screensStore.uploadSlide(props.screenId, fd);
        }

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
        :title="slide ? 'Edit Promo Slide' : 'Create HTML Promo Slide'"
        max-width="max-w-4xl"
        @close="$emit('close')"
    >
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Form Controls -->
            <div class="space-y-4 max-h-[65vh] overflow-y-auto pr-2">
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                        Internal Slide Title
                    </label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="e.g. Weekend Wagyu Promo"
                        class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Promo Badge
                        </label>
                        <input
                            v-model="form.badge"
                            type="text"
                            placeholder="🔥 CHEF SPECIAL"
                            class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Slide Duration (Sec)
                        </label>
                        <input
                            v-model.number="form.duration_override"
                            type="number"
                            min="3"
                            max="120"
                            class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                        Headline / Dish Name
                    </label>
                    <input
                        v-model="form.headline"
                        type="text"
                        placeholder="Double Truffle Wagyu Burger"
                        class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                        Description / Ingredients
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="Describe flavor notes, sides, or promo details..."
                        class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none resize-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Price Display
                        </label>
                        <input
                            v-model="form.price"
                            type="text"
                            placeholder="$19.50"
                            class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none font-bold"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Price Subtitle
                        </label>
                        <input
                            v-model="form.price_subtitle"
                            type="text"
                            placeholder="Includes drink & side"
                            class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-sm text-zinc-100 focus:border-amber-500 outline-none"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                        Product Photo URL (Unsplash or Direct)
                    </label>
                    <input
                        v-model="form.image_url"
                        type="url"
                        placeholder="https://images.unsplash.com/..."
                        class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-100 focus:border-amber-500 outline-none font-mono"
                    />
                </div>

                <!-- Theme / Gradient Presets -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-2">
                        Style Theme Presets
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <button
                            v-for="preset in gradientPresets"
                            :key="preset.label"
                            type="button"
                            @click="selectPreset(preset)"
                            class="px-2.5 py-2 rounded-lg border text-left text-xs font-medium transition-all flex items-center gap-2 cursor-pointer"
                            :class="form.bg_gradient === preset.value
                                ? 'border-amber-500 bg-zinc-800 text-white'
                                : 'border-zinc-800 bg-zinc-950 text-zinc-400 hover:border-zinc-700'"
                        >
                            <div class="w-3.5 h-3.5 rounded-full shrink-0 shadow" :style="{ background: preset.accent }" />
                            <span class="truncate">{{ preset.label }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Live Card Preview -->
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-xs font-semibold text-zinc-400 uppercase tracking-wider mb-2">
                    <Eye class="w-4 h-4 text-amber-400" />
                    <span>Real-time TV Preview</span>
                </div>

                <div
                    class="relative flex-1 min-h-[300px] rounded-2xl p-6 border shadow-2xl overflow-hidden flex flex-col justify-between"
                    :style="{
                        background: form.bg_gradient,
                        borderColor: `${form.accent_color}40`,
                    }"
                >
                    <!-- Background ambient glow -->
                    <div
                        class="absolute -top-12 -right-12 w-48 h-48 rounded-full blur-3xl opacity-30 pointer-events-none"
                        :style="{ background: form.accent_color }"
                    />

                    <!-- Card Header -->
                    <div>
                        <div
                            v-if="form.badge"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide shadow-sm mb-3"
                            :style="{
                                background: `${form.accent_color}25`,
                                color: form.accent_color,
                                border: `1px solid ${form.accent_color}50`
                            }"
                        >
                            <Flame class="w-3.5 h-3.5" />
                            <span>{{ form.badge }}</span>
                        </div>
                        <h4 class="text-xl md:text-2xl font-black text-white leading-tight">
                            {{ form.headline || 'Headline' }}
                        </h4>
                        <p class="text-xs text-zinc-300 mt-2 line-clamp-3 leading-relaxed">
                            {{ form.description || 'Description' }}
                        </p>
                    </div>

                    <!-- Card Bottom -->
                    <div class="pt-4 flex items-end justify-between border-t border-white/10 mt-4">
                        <div>
                            <div class="text-2xl md:text-3xl font-extrabold" :style="{ color: form.accent_color }">
                                {{ form.price }}
                            </div>
                            <div class="text-[11px] text-zinc-400">
                                {{ form.price_subtitle }}
                            </div>
                        </div>

                        <div v-if="form.image_url" class="w-20 h-20 rounded-xl overflow-hidden shadow-lg border border-white/20">
                            <img :src="form.image_url" class="w-full h-full object-cover" />
                        </div>
                    </div>
                </div>
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
                class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-amber-500/10 disabled:opacity-50 cursor-pointer"
            >
                {{ saving ? 'Saving Slide...' : (slide ? 'Update Slide' : 'Publish to TV') }}
            </button>
        </template>
    </Modal>
</template>
