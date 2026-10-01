/**
 * Cross-tab & Cross-window instant sync utility for TV displays and Admin controls.
 * BroadcastChannel delivers zero-latency messages to open display tabs.
 * localStorage event serves as cross-window/browser fallback.
 */

const SYNC_CHANNEL_NAME = 'atofood_display_sync';
const SYNC_STORAGE_KEY = 'atofood_screen_last_updated';

export const notifyScreenUpdate = (screenId = null, extra = {}) => {
    const payload = {
        type: 'SCREEN_UPDATED',
        screenId: screenId ? String(screenId) : null,
        timestamp: Date.now(),
        ...extra,
    };

    // 1. BroadcastChannel (instant in modern browsers across tabs in same origin)
    try {
        if (typeof BroadcastChannel !== 'undefined') {
            const bc = new BroadcastChannel(SYNC_CHANNEL_NAME);
            bc.postMessage(payload);
            bc.close();
        }
    } catch (e) {
        console.warn('BroadcastChannel sync failed:', e);
    }

    // 2. LocalStorage storage event (instant fallback across all tabs and windows)
    try {
        localStorage.setItem(SYNC_STORAGE_KEY, JSON.stringify(payload));
    } catch (e) {
        console.warn('Storage sync failed:', e);
    }
};

export const subscribeToScreenUpdates = (callback) => {
    let bc = null;

    try {
        if (typeof BroadcastChannel !== 'undefined') {
            bc = new BroadcastChannel(SYNC_CHANNEL_NAME);
            bc.onmessage = (event) => {
                if (event.data?.type === 'SCREEN_UPDATED') {
                    callback(event.data);
                }
            };
        }
    } catch (e) {
        console.warn('Failed to init BroadcastChannel subscriber:', e);
    }

    const storageHandler = (e) => {
        if (e.key === SYNC_STORAGE_KEY && e.newValue) {
            try {
                const data = JSON.parse(e.newValue);
                if (data?.type === 'SCREEN_UPDATED') {
                    callback(data);
                }
            } catch (err) {}
        }
    };

    window.addEventListener('storage', storageHandler);

    return () => {
        if (bc) {
            try {
                bc.close();
            } catch (e) {}
        }
        window.removeEventListener('storage', storageHandler);
    };
};
