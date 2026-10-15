/*
 * Art-DB service worker.
 *
 * Loads Workbox from the Google CDN and sets per-route caching:
 *   - Admin/Livewire/CSRF endpoints are NetworkOnly, never cached — Filament
 *     depends on live sessions and would break otherwise.
 *   - HTML navigations fall back to a 7-day page cache after a 3s timeout.
 *   - Hashed Vite build assets cache for a year (CacheFirst).
 *   - Storage images and bunny fonts cache with SWR / CacheFirst.
 *   - Document navigations that fail fall through to the /offline page.
 *
 * Bump SW_VERSION whenever cache keys need to be invalidated across deploys.
 */

const SW_VERSION = 'v1';

importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.1.0/workbox-sw.js');

workbox.setConfig({ debug: false });

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

const NEVER_CACHE = /\/livewire\/|\/filament\/|\/admin\/|\/broadcasting\/|\/sanctum\/|\/login|\/logout|\/password\/|\/email\/verify|\/stripe\/|\/webhook/;

workbox.routing.registerRoute(
    ({ url, request }) =>
        NEVER_CACHE.test(url.pathname) || request.method !== 'GET',
    new workbox.strategies.NetworkOnly()
);

workbox.routing.registerRoute(
    ({ request }) => request.mode === 'navigate',
    new workbox.strategies.NetworkFirst({
        cacheName: `pages-${SW_VERSION}`,
        networkTimeoutSeconds: 3,
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 50,
                maxAgeSeconds: 7 * 24 * 60 * 60,
            }),
            new workbox.cacheableResponse.CacheableResponsePlugin({
                statuses: [0, 200],
            }),
        ],
    })
);

workbox.routing.registerRoute(
    ({ url }) => url.pathname.startsWith('/build/'),
    new workbox.strategies.CacheFirst({
        cacheName: `static-${SW_VERSION}`,
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 100,
                maxAgeSeconds: 365 * 24 * 60 * 60,
            }),
            new workbox.cacheableResponse.CacheableResponsePlugin({
                statuses: [0, 200],
            }),
        ],
    })
);

workbox.routing.registerRoute(
    ({ url, request }) =>
        url.pathname.startsWith('/storage/') && request.destination === 'image',
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: `storage-images-${SW_VERSION}`,
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 200,
                maxAgeSeconds: 30 * 24 * 60 * 60,
            }),
            new workbox.cacheableResponse.CacheableResponsePlugin({
                statuses: [0, 200],
            }),
        ],
    })
);

workbox.routing.registerRoute(
    ({ url }) => url.host === 'fonts.bunny.net',
    new workbox.strategies.CacheFirst({
        cacheName: `fonts-${SW_VERSION}`,
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 20,
                maxAgeSeconds: 365 * 24 * 60 * 60,
            }),
            new workbox.cacheableResponse.CacheableResponsePlugin({
                statuses: [0, 200],
            }),
        ],
    })
);

workbox.routing.registerRoute(
    ({ url }) => url.pathname === '/pwa' || url.pathname.startsWith('/pwa/'),
    new workbox.strategies.CacheFirst({
        cacheName: `pwa-icons-${SW_VERSION}`,
        plugins: [
            new workbox.expiration.ExpirationPlugin({
                maxEntries: 20,
                maxAgeSeconds: 365 * 24 * 60 * 60,
            }),
        ],
    })
);

workbox.routing.setCatchHandler(async ({ event }) => {
    if (event.request.destination === 'document') {
        const cache = await caches.open(`pages-${SW_VERSION}`);
        const cached = await cache.match('/offline');
        if (cached) return cached;
    }
    return Response.error();
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

/*
 * Web Push: incoming notification from the server. Payload shape is the
 * JSON produced by App\Notifications\* classes' toWebPush() method:
 *   { title, body, icon?, badge?, tag?, url?, actions? }
 * Everything except title is optional; icon defaults to the 192 PWA icon
 * and url defaults to the home screen entry point.
 */
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch (_e) {
        payload = { title: 'Art-DB', body: event.data.text() };
    }

    const title = payload.title || 'Art-DB';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/pwa/icon-192.png',
        badge: payload.badge || '/pwa/icon-192.png',
        tag: payload.tag || undefined,
        data: { url: payload.url || '/admin' },
        actions: payload.actions || [],
        renotify: !!payload.tag,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/admin';

    event.waitUntil(
        (async () => {
            const allClients = await self.clients.matchAll({
                type: 'window',
                includeUncontrolled: true,
            });
            // Reuse an already-open Art-DB window when there is one, so a
            // click never spawns a second tab next to the installed PWA.
            for (const client of allClients) {
                if (client.url.includes(self.location.origin)) {
                    await client.focus();
                    if ('navigate' in client) {
                        try { await client.navigate(targetUrl); } catch (_e) {}
                    }
                    return;
                }
            }
            await self.clients.openWindow(targetUrl);
        })()
    );
});
