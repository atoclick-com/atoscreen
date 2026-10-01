/**
 * Media URL normalizer for standalone and subdirectory deployments (e.g. /v).
 * Resolves relative storage paths into correct browser URLs.
 */
export const getAppBase = () => {
    if (typeof window !== 'undefined' && window.__APP_BASE__) {
        return window.__APP_BASE__;
    }
    if (typeof window !== 'undefined' && (window.location.pathname === '/v' || window.location.pathname.startsWith('/v/'))) {
        return '/v';
    }
    return '';
};

export const resolveMediaUrl = (url) => {
    if (!url || typeof url !== 'string') return '';

    // Full remote URLs or data/blob URIs are returned directly
    if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:') || url.startsWith('blob:')) {
        return url;
    }

    const base = getAppBase().replace(/\/+$/, '');
    let cleanUrl = url.startsWith('/') ? url : `/${url}`;

    // If it's a storage file path but missing /storage/ prefix (e.g. /screens/...)
    if (!cleanUrl.startsWith('/storage/') && !cleanUrl.startsWith(`${base}/storage/`) && cleanUrl.startsWith('/screens/')) {
        cleanUrl = `/storage${cleanUrl}`;
    }

    // If it already starts with the base path (e.g. /v/storage/...)
    if (base && (cleanUrl === base || cleanUrl.startsWith(`${base}/`))) {
        return cleanUrl;
    }

    // Prepend app base path (e.g. /v + /storage/... -> /v/storage/...)
    return `${base}${cleanUrl}`;
};
