<script setup>
import { computed } from 'vue';
import draggable from 'vuedraggable';
import SlideCard from './SlideCard.vue';
import EmptyState from '@/components/common/EmptyState.vue';
import { Layers } from 'lucide-vue-next';

const props = defineProps({
    slides: {
        type: Array,
        default: () => [],
    },
    defaultDuration: {
        type: Number,
        default: 10,
    },
});

const emit = defineEmits([
    'update:slides',
    'reorder',
    'toggle-active',
    'edit-schedule',
    'edit-promo',
    'edit-instagram',
    'update-timing',
    'toggle-audio',
    'update-fit-mode',
    'delete',
    'add-slide',
    'preview-video',
]);

const list = computed({
    get: () => props.slides,
    set: (val) => {
        emit('update:slides', val);
        emit('reorder', val);
    },
});
</script>

<template>
    <div class="space-y-3">
        <!-- Reorderable List -->
        <draggable
            v-if="slides && slides.length"
            v-model="list"
            item-key="id"
            handle=".drag-handle"
            animation="200"
            ghost-class="opacity-40"
            class="space-y-2.5"
        >
            <template #item="{ element }">
                <SlideCard
                    :slide="element"
                    :default-duration="defaultDuration"
                    @toggle-active="$emit('toggle-active', $event)"
                    @edit-schedule="$emit('edit-schedule', $event)"
                    @edit-promo="$emit('edit-promo', $event)"
                    @edit-instagram="$emit('edit-instagram', $event)"
                    @update-timing="$emit('update-timing', $event)"
                    @toggle-audio="$emit('toggle-audio', $event)"
                    @update-fit-mode="$emit('update-fit-mode', $event)"
                    @delete="$emit('delete', $event)"
                    @preview-video="$emit('preview-video', $event)"
                />
            </template>
        </draggable>

        <!-- Empty State -->
        <EmptyState
            v-else
            icon="film"
            title="No slides in this playlist"
            description="Drag and drop photos/videos above, add an Instagram reel, or create a promotional chef special card."
        />
    </div>
</template>
