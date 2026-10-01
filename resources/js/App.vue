<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(() => {
    authStore.applyDarkMode();
    authStore.fetchUser();
});
</script>

<template>
    <router-view />
</template>
