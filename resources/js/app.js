/*
 * Register the service worker in /public/sw.js so the app installs as a PWA
 * and routes gain offline/caching behaviour. Scope "/" so navigations across
 * the public web, auth and /admin panels share one worker. The hourly update
 * call lets a freshly deployed sw.js take over without a hard reload.
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then((registration) => {
                setInterval(() => registration.update(), 60 * 60 * 1000);
            })
            .catch((error) => {
                console.warn('Art-DB SW registration failed:', error);
            });
    });
}
