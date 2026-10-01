<script setup>
import { ref } from 'vue';
import { useScreensStore } from '@/stores/screens';
import { useToastStore } from '@/stores/toast';
import { UploadCloud, Image, Film, AlertCircle, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    screenId: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['uploaded']);

const screensStore = useScreensStore();
const toastStore = useToastStore();

const isDragging = ref(false);
const fileInput = ref(null);
const uploading = ref(false);
const uploadProgress = ref(0);
const errorMessage = ref(null);

const acceptedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'mp4', 'webm', 'mov', 'm4v', 'mkv', 'avi'];
const maxSizeBytes = 200 * 1024 * 1024; // 200MB

const validateFile = (file) => {
    const ext = file.name.split('.').pop()?.toLowerCase();
    const isImageOrVideoMime = file.type?.startsWith('image/') || file.type?.startsWith('video/');
    const isKnownExtension = acceptedExtensions.includes(ext);

    if (!isImageOrVideoMime && !isKnownExtension) {
        return `"${file.name}" is not a supported format. Please upload JPG, PNG, WEBP, or MP4/WebM/MOV videos.`;
    }
    if (file.size > maxSizeBytes) {
        return `"${file.name}" exceeds the 200MB maximum limit.`;
    }
    return null;
};

const handleFiles = async (files) => {
    errorMessage.value = null;
    if (!files || !files.length) return;

    const fileList = Array.from(files);

    // Validate all files
    for (const file of fileList) {
        const err = validateFile(file);
        if (err) {
            errorMessage.value = err;
            toastStore.error('Validation Error', err);
            return;
        }
    }

    uploading.value = true;
    uploadProgress.value = 15;

    try {
        const formData = new FormData();
        fileList.forEach((file) => {
            formData.append('files[]', file);
        });

        uploadProgress.value = 45;
        await screensStore.batchUpload(props.screenId, formData);
        uploadProgress.value = 100;

        setTimeout(() => {
            uploading.value = false;
            uploadProgress.value = 0;
            emit('uploaded');
        }, 500);
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Failed to upload files';
        uploading.value = false;
        uploadProgress.value = 0;
    }
};

const onDrop = (e) => {
    isDragging.value = false;
    handleFiles(e.dataTransfer.files);
};

const onFileChange = (e) => {
    handleFiles(e.target.files);
    e.target.value = ''; // Reset input
};
</script>

<template>
    <div class="w-full">
        <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
            @click="fileInput?.click()"
            class="relative border-2 border-dashed rounded-2xl p-8 text-center transition-all cursor-pointer group"
            :class="[
                isDragging
                    ? 'border-amber-500 bg-amber-500/10'
                    : 'border-zinc-800 bg-zinc-900/30 hover:border-zinc-700 hover:bg-zinc-900/50',
                uploading ? 'pointer-events-none opacity-80' : ''
            ]"
        >
            <input
                ref="fileInput"
                type="file"
                multiple
                accept="image/*,video/*,.mp4,.webm,.mov,.m4v,.mkv,.avi"
                class="hidden"
                @change="onFileChange"
            />

            <!-- Normal state -->
            <div v-if="!uploading" class="flex flex-col items-center justify-center space-y-3">
                <div class="w-14 h-14 rounded-2xl bg-zinc-800/80 border border-zinc-700/50 flex items-center justify-center text-zinc-400 group-hover:scale-105 group-hover:text-amber-400 transition-all shadow-inner">
                    <UploadCloud class="w-7 h-7" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-200">
                        <span class="text-amber-400 group-hover:underline">Click to browse</span> or drag & drop media here
                    </p>
                    <p class="text-xs text-zinc-500 mt-1">
                        Images (JPG, PNG, WEBP) & Full HD / 4K Videos (MP4, WebM, MOV) up to 200MB
                    </p>
                </div>

                <div class="flex items-center gap-3 pt-2 text-[11px] text-zinc-500">
                    <span class="flex items-center gap-1"><Image class="w-3.5 h-3.5 text-zinc-400" /> Multiple photos</span>
                    <span>•</span>
                    <span class="flex items-center gap-1"><Film class="w-3.5 h-3.5 text-zinc-400" /> Video clips</span>
                </div>
            </div>

            <!-- Uploading progress state -->
            <div v-else class="space-y-4 py-2">
                <div class="flex items-center justify-center gap-2 text-sm font-semibold text-amber-400">
                    <div class="w-4 h-4 rounded-full border-2 border-amber-500/30 border-t-amber-500 animate-spin" />
                    <span>Uploading promotional media... {{ uploadProgress }}%</span>
                </div>
                <div class="w-full max-w-xs mx-auto bg-zinc-800 rounded-full h-2 overflow-hidden">
                    <div
                        class="bg-amber-500 h-full rounded-full transition-all duration-300"
                        :style="{ width: `${uploadProgress}%` }"
                    />
                </div>
            </div>
        </div>

        <div v-if="errorMessage" class="mt-3 p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs text-rose-400 flex items-center gap-2">
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{{ errorMessage }}</span>
        </div>
    </div>
</template>
