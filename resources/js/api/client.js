import axios from 'axios';

// Use app base path injected by Laravel (e.g. '/v' when deployed at trotiluxe.ma/v)
// Falls back to empty string for root deployments (localhost, etc.)
const appBase = window.__APP_BASE__ || '';

const api = axios.create({
    baseURL: appBase + '/api/v1',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('atofood_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const path = window.location.pathname;
            const loginPath = appBase + '/login';
            const displayPrefix = appBase + '/display/';
            const isDisplay = path.startsWith(displayPrefix) || path.startsWith('/display/');
            if (!isDisplay && path !== loginPath && path !== '/login') {
                localStorage.removeItem('atofood_token');
                localStorage.removeItem('atofood_user');
                window.location.href = loginPath;
            }
        }
        return Promise.reject(error);
    }
);

export default api;
