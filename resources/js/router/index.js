import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/LoginView.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/screens/:id',
        name: 'screen-editor',
        component: () => import('@/views/ScreenEditorView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/screens/:id/settings',
        name: 'screen-settings',
        component: () => import('@/views/ScreenSettingsView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/screens/:id/analytics',
        name: 'screen-analytics',
        component: () => import('@/views/ScreenAnalyticsView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/settings',
        name: 'app-settings',
        component: () => import('@/views/AppSettingsView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/wizard',
        alias: '/install',
        name: 'setup-wizard',
        component: () => import('@/views/SetupWizardView.vue'),
        meta: { requiresAuth: false },
    },
    {
        path: '/v/:code',
        name: 'short-display',
        component: () => import('@/views/DisplayView.vue'),
        meta: { isDisplay: true },
    },
    {
        path: '/:code(\\d+)',
        name: 'numeric-short-display',
        component: () => import('@/views/DisplayView.vue'),
        meta: { isDisplay: true },
    },
    {
        path: '/display/:uuid',
        name: 'display',
        component: () => import('@/views/DisplayView.vue'),
        meta: { isDisplay: true },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    // If public display page, allow without auth
    if (to.meta.isDisplay) {
        return next();
    }

    const authStore = useAuthStore();
    const token = localStorage.getItem('atofood_token');

    if (to.meta.requiresAuth && !token) {
        return next({ name: 'login', query: { redirect: to.fullPath } });
    }

    if (to.meta.guestOnly && token) {
        return next({ name: 'dashboard' });
    }

    next();
});

router.afterEach((to) => {
    try {
        const appTitle = localStorage.getItem('atofood_app_name') || window.__APP_SETTINGS__?.appName || 'trotiluxe';
        const businessTitle = localStorage.getItem('atofood_business_name') || window.__APP_SETTINGS__?.businessName || 'Trotiluxe E-Bikes & Bistro';

        if (to.meta.isDisplay) {
            document.title = `${businessTitle} | TV Display`;
        } else if (to.name === 'dashboard') {
            document.title = `Dashboard | ${appTitle}`;
        } else if (to.name === 'screen-editor') {
            document.title = `Playlist Editor | ${appTitle}`;
        } else if (to.name === 'screen-settings') {
            document.title = `Screen Settings | ${appTitle}`;
        } else if (to.name === 'screen-analytics') {
            document.title = `Analytics | ${appTitle}`;
        } else if (to.name === 'app-settings') {
            document.title = `Settings | ${appTitle}`;
        } else if (to.name === 'setup-wizard') {
            document.title = `Setup Wizard | ${appTitle}`;
        } else if (to.name === 'login') {
            document.title = `Login | ${appTitle}`;
        } else {
            document.title = `${appTitle} | ${businessTitle}`;
        }
    } catch (e) {}
});

export default router;
