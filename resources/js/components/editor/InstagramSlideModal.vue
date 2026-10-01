<script setup>
import { ref, reactive, watch, computed } from 'vue';
import Modal from '@/components/common/Modal.vue';
import ToggleSwitch from '@/components/common/ToggleSwitch.vue';
import { useScreensStore } from '@/stores/screens';
import { useToastStore } from '@/stores/toast';
import {
    Instagram,
    Sparkles,
    Volume2,
    Clock,
    QrCode,
    Link,
    CheckCircle2,
    Heart,
    Eye,
    Check,
} from 'lucide-vue-next';

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
const toastStore = useToastStore();

const isEditing = computed(() => !!props.slide);
const saving = ref(false);

const presetMedia = [
    {
        name: 'Trotiluxe All Models (Reel)',
        url: 'https://www.instagram.com/p/Dd4bjHcjYk3/',
        type: 'reel',
        author: 'trotiluxe',
        caption: 'Tous les modèles disponibles chez Trotiluxe ! Trottinettes, motos et vélos électriques.',
    },
    {
        name: 'Gourmet Truffle Burger (Reel)',
        url: 'https://www.instagram.com/reel/C8atofood/',
        type: 'reel',
        media_url: 'https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=1200&q=80',
        author: 'atofood_paris',
        caption: 'Our signature Truffle Wagyu Burger with aged cheddar and crispy onions.',
    },
    {
        name: 'Artisan Woodfire Pizza (Square)',
        url: 'https://www.instagram.com/p/C7woodfire/',
        type: 'square',
        media_url: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80',
        author: 'atofood_official',
        caption: 'Neapolitan style sourdough pizza, baked at 450°C in our Italian oven.',
    },
];

const form = reactive({
    title: '',
    url: '',
    author: '',
    author_avatar: '',
    media_type: 'reel',
    media_url: '',
    caption: '',
    likes_count: '',
    duration: 15,
    audio_enabled: true,
    fit_mode: 'ambient_blur',
    show_qr_code: true,
});

const embedPreviewUrl = computed(() => {
    if (!form.url) return null;
    const match = form.url.match(/instagram\.com\/(?:p|reel|tv)\/([a-zA-Z0-9_-]+)/i);
    if (match && match[1]) {
        return `https://www.instagram.com/p/${match[1]}/embed/`;
    }
    return null;
});

const extractInstagramInfo = (url) => {
    if (!url) return;
    const cleanUrl = url.trim();
    if (cleanUrl.includes('/reel/')) {
        form.media_type = 'reel';
    } else if (cleanUrl.includes('/p/')) {
        form.media_type = 'reel'; // Most video posts are reels
    }

    const match = cleanUrl.match(/instagram\.com\/([a-zA-Z0-9._]+)\/(?:reel|p)\//);
    if (match && match[1]) {
        form.author = match[1];
        if (!form.title) {
            form.title = `Instagram @${match[1]}`;
        }
    }
};

const handleUrlChange = () => {
    extractInstagramInfo(form.url);
};

const applyPreset = (preset) => {
    form.title = preset.name;
    form.url = preset.url;
    form.media_type = preset.type;
    form.media_url = preset.media_url || '';
    form.caption = preset.caption;
    form.author = preset.author;
    extractInstagramInfo(preset.url);
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.slide && props.slide.type === 'instagram') {
            form.title = props.slide.title || '';
            form.duration = props.slide.duration_override || 15;
            form.audio_enabled = props.slide.audio_enabled !== false;
            form.fit_mode = props.slide.fit_mode || 'ambient_blur';

            const c = props.slide.content || {};
            form.url = c.url || '';
            form.author = c.author || '';
            form.author_avatar = c.author_avatar || '';
            form.media_type = c.media_type || 'reel';
            form.media_url = c.media_url || '';
            form.caption = c.caption || '';
            form.likes_count = c.likes_count || '';
            form.show_qr_code = c.show_qr_code !== false;
        } else {
            // New slide defaults: ready for user's URL
            form.title = 'Instagram Post';
            form.url = 'https://www.instagram.com/p/Dd4bjHcjYk3/';
            form.author = 'trotiluxe';
            form.author_avatar = '';
            form.media_type = 'reel';
            form.media_url = '';
            form.caption = 'Découvrez tous nos modèles disponibles en magasin !';
            form.likes_count = '';
            form.duration = 15;
            form.audio_enabled = true;
            form.fit_mode = 'ambient_blur';
            form.show_qr_code = true;
            extractInstagramInfo(form.url);
        }
    }
});

const handleSubmit = async () => {
    saving.value = true;
    try {
        const payload = {
            type: 'instagram',
            title: form.title || (form.author ? `Instagram @${form.author}` : 'Instagram Feature'),
            duration_override: form.duration,
            audio_enabled: form.audio_enabled,
            fit_mode: form.fit_mode,
            file_url: form.media_url || null,
            content: {
                url: form.url,
                author: form.author || 'Instagram',
                author_avatar: form.author_avatar,
                media_type: form.media_type,
                media_url: form.media_url || null,
                caption: form.caption,
                likes_count: form.likes_count,
                show_qr_code: form.show_qr_code,
            },
        };

        if (isEditing.value) {
            await screensStore.updateSlide(props.slide.id, payload);
            toastStore.success('Instagram Slide Updated', 'Changes synced to TV.');
        } else {
            await screensStore.uploadSlide(props.screenId, payload);
            toastStore.success('Instagram Slide Added', 'Post will now broadcast live on TV.');
        }

        emit('saved');
        emit('close');
    } catch (err) {
        console.error('Failed to save instagram slide:', err);
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Modal
        :show="show"
        :title="isEditing ? 'Edit Instagram Slide' : 'Add Instagram Reel / Post Slide'"
        max-width="max-w-4xl"
        @close="$emit('close')"
    >
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Inputs (7 cols) -->
            <div class="lg:col-span-7 space-y-4">
                <!-- Instagram Link Input -->
                <div class="p-3.5 rounded-2xl bg-gradient-to-r from-pink-500/10 via-purple-500/10 to-transparent border border-pink-500/30 space-y-2">
                    <label class="block text-xs font-bold text-white uppercase tracking-wider flex items-center gap-1.5">
                        <Instagram class="w-4 h-4 text-pink-400" />
                        <span>Paste Instagram Post or Reel Link</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.url"
                            type="url"
                            placeholder="https://www.instagram.com/p/Dd4bjHcjYk3/ or /reel/..."
                            class="w-full pl-9 pr-3.5 py-2.5 rounded-xl bg-zinc-950 border border-zinc-700 text-sm text-zinc-100 focus:border-pink-500 outline-none font-mono"
                            @input="handleUrlChange"
                        />
                        <Link class="w-4 h-4 text-pink-400 absolute left-3 top-3" />
                    </div>
                    <p class="text-[11px] text-zinc-400">
                        ✨ Just paste any Instagram link! The system will stream the live post, video, author, and audio directly to your TV.
                    </p>
                </div>

                <!-- One-click Presets -->
                <div class="space-y-1.5">
                    <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider">
                        Quick Demo Examples:
                    </span>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="(p, i) in presetMedia"
                            :key="i"
                            type="button"
                            @click="applyPreset(p)"
                            class="p-2 rounded-xl bg-zinc-950 border border-zinc-800 hover:border-pink-500/50 text-left transition-all cursor-pointer group"
                        >
                            <span class="block text-[11px] font-bold text-zinc-200 group-hover:text-pink-300 truncate">{{ p.name }}</span>
                            <span class="block text-[10px] text-zinc-500 uppercase">{{ p.author }}</span>
                        </button>
                    </div>
                </div>

                <!-- Slide Title & Account Handle -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Slide Title (Internal)
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="e.g. Trotiluxe Presentation"
                            class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-100 focus:border-amber-500 outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                            Instagram Author / Account
                        </label>
                        <input
                            v-model="form.author"
                            type="text"
                            placeholder="e.g. trotiluxe"
                            class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-100 focus:border-amber-500 outline-none"
                        />
                    </div>
                </div>

                <!-- Caption / Description -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5">
                        Post Caption (Displayed beside post on TV)
                    </label>
                    <textarea
                        v-model="form.caption"
                        rows="2"
                        placeholder="Highlight or promotional note for customers watching the TV screen..."
                        class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-100 focus:border-amber-500 outline-none resize-none"
                    />
                </div>

                <!-- Custom Direct Media (Optional) -->
                <div>
                    <label class="block text-xs font-semibold text-zinc-300 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                        <span>Direct Image or Video URL (Optional)</span>
                        <span class="text-[10px] text-zinc-500 lowercase">leave empty to stream live post</span>
                    </label>
                    <input
                        v-model="form.media_url"
                        type="url"
                        placeholder="https://... (Optional fallback media)"
                        class="w-full px-3.5 py-2 rounded-xl bg-zinc-950 border border-zinc-800 text-xs text-zinc-100 focus:border-amber-500 outline-none font-mono"
                    />
                </div>

                <!-- Playback Timing, Audio & QR Code Toggles -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-xl bg-zinc-950 border border-zinc-800/80">
                    <!-- Timing -->
                    <div class="space-y-1">
                        <span class="block text-[11px] font-semibold text-zinc-300 flex items-center gap-1">
                            <Clock class="w-3.5 h-3.5 text-amber-400" />
                            <span>Duration</span>
                        </span>
                        <div class="flex items-center gap-2">
                            <input
                                v-model.number="form.duration"
                                type="number"
                                min="5"
                                max="120"
                                class="w-16 px-2 py-1 rounded-lg bg-zinc-900 border border-zinc-700 text-xs text-zinc-100 font-mono"
                            />
                            <span class="text-xs text-zinc-400">sec</span>
                        </div>
                    </div>

                    <!-- Audio Sound Toggle -->
                    <div class="space-y-1">
                        <span class="block text-[11px] font-semibold text-zinc-300 flex items-center gap-1">
                            <Volume2 class="w-3.5 h-3.5 text-emerald-400" />
                            <span>Sound</span>
                        </span>
                        <div class="flex items-center gap-2 pt-0.5">
                            <ToggleSwitch v-model="form.audio_enabled" />
                            <span class="text-[11px] text-zinc-400 font-mono">{{ form.audio_enabled ? 'ON' : 'Muted' }}</span>
                        </div>
                    </div>

                    <!-- TV QR Code Toggle -->
                    <div class="space-y-1">
                        <span class="block text-[11px] font-semibold text-zinc-300 flex items-center gap-1">
                            <QrCode class="w-3.5 h-3.5 text-amber-400" />
                            <span>TV QR Code</span>
                        </span>
                        <div class="flex items-center gap-2 pt-0.5">
                            <ToggleSwitch v-model="form.show_qr_code" />
                            <span class="text-[11px] text-zinc-400">{{ form.show_qr_code ? 'Yes' : 'No' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Live Embed Kiosk Preview (5 cols) -->
            <div class="lg:col-span-5 flex flex-col justify-between">
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-zinc-300 uppercase tracking-wider flex items-center gap-1.5">
                        <Eye class="w-3.5 h-3.5 text-pink-400" />
                        <span>Live Instagram Stream Preview</span>
                    </span>

                    <!-- TV Frame Simulation (16:9) with Live Embed -->
                    <div class="aspect-[10/11] w-full rounded-2xl overflow-hidden bg-black border border-zinc-800 relative shadow-2xl flex items-center justify-center p-2 select-none">
                        <!-- If official embed preview exists, render actual Instagram live widget! -->
                        <iframe
                            v-if="embedPreviewUrl"
                            :src="embedPreviewUrl"
                            class="w-full h-full border-0 rounded-xl bg-black"
                            scrolling="no"
                            allowtransparency="true"
                        />
                        <!-- Fallback illustration -->
                        <div v-else class="text-center p-6 space-y-2 text-zinc-500">
                            <Instagram class="w-10 h-10 mx-auto text-zinc-700 animate-pulse" />
                            <p class="text-xs">Paste an Instagram link to see live interactive preview</p>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-gradient-to-r from-pink-500/10 to-purple-500/10 border border-pink-500/20 text-[11px] text-pink-200 mt-4 flex items-start gap-2">
                    <Sparkles class="w-4 h-4 text-pink-400 shrink-0 mt-0.5" />
                    <span>
                        Our Smart TV kiosk streams this post in high resolution with ambient glass lighting and a scannable phone QR code.
                    </span>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    :disabled="saving"
                    @click="handleSubmit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-rose-500 to-amber-500 hover:from-pink-400 hover:to-amber-400 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-lg shadow-pink-500/20 active:scale-95 disabled:opacity-50 cursor-pointer"
                >
                    <Instagram class="w-4 h-4" />
                    <span>{{ saving ? 'Adding...' : (isEditing ? 'Save Changes' : 'Add to Screen') }}</span>
                </button>
            </div>
        </template>
    </Modal>
</template>
