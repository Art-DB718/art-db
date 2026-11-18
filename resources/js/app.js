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

/*
 * window.ArtDbShare(url, filename) — fetches the URL as a Blob and hands it
 * to the native share sheet via the Web Share API. On iOS inside the
 * installed PWA, a plain <a> to a PDF opens a chrome-less viewer with no
 * Print or Save button; routing the file through navigator.share surfaces
 * the real iOS share sheet (Save to Files, Print, AirDrop, Mail, …).
 * Falls back to a programmatic download (desktop browsers, Firefox Android)
 * and finally to location navigation if both paths are unavailable.
 */
window.ArtDbShare = async function(url, filename) {
    filename = filename || 'document.pdf';
    try {
        const resp = await fetch(url, { credentials: 'same-origin' });
        if (!resp.ok) throw new Error('fetch ' + resp.status);
        const blob = await resp.blob();
        const file = new File([blob], filename, { type: blob.type || 'application/pdf' });

        if (navigator.canShare && navigator.canShare({ files: [file] })) {
            try {
                await navigator.share({ files: [file], title: filename });
                return;
            } catch (e) {
                if (e && e.name === 'AbortError') return;
            }
        }

        const dlUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = dlUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(dlUrl), 1000);
    } catch (e) {
        console.warn('ArtDbShare failed, opening URL directly:', e);
        window.location.href = url;
    }
};

// Delegated handler for anything with data-pdf-share — Filament's print
// actions attach that attribute so the URL becomes a Web Share instead of
// a naive navigation. Capture phase so Filament's own click handling does
// not win the race.
//
// IMPORTANT: this intercept is only useful inside an iOS home-screen PWA
// (where a plain <a href=".pdf"> opens a chrome-less viewer). Everywhere
// else — desktop Safari, Chrome, Firefox, Android, macOS "Add to Dock"
// web-apps — the normal <a target="_blank"> gives a better experience
// (inline PDF viewer in a new tab). Without this guard, navigator.share()
// on macOS pops the system share sheet which is *not* what people expect
// when they hit "Print artwork card" from a desktop admin.
function isIOSHomeScreenPWA() {
    const ua = navigator.userAgent || '';
    const isIOS = /iPhone|iPad|iPod/.test(ua)
        // iPadOS 13+ reports as a Mac; detect via touch support.
        || (navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1);
    return isIOS
        && 'standalone' in window.navigator
        && window.navigator.standalone === true;
}

document.addEventListener('click', (event) => {
    const el = event.target.closest('[data-pdf-share]');
    if (!el) return;
    if (!isIOSHomeScreenPWA()) return; // let the browser handle it normally
    event.preventDefault();
    event.stopPropagation();
    const url = el.getAttribute('data-pdf-share');
    const filename = el.getAttribute('data-pdf-filename') || 'document.pdf';
    window.ArtDbShare(url, filename);
}, true);

/*
 * Window-global that the Filament push-notifications form field calls when
 * the user toggles the switch. Kept outside Alpine so it is callable from
 * both Blade and Livewire contexts.
 */
window.ArtDbPush = {
    _urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
        return output;
    },

    _getVapidKey() {
        const el = document.querySelector('meta[name="vapid-public-key"]');
        if (!el || !el.content) {
            throw new Error('Chýba VAPID public key na stránke.');
        }
        return el.content;
    },

    _getCsrfToken() {
        const el = document.querySelector('meta[name="csrf-token"]');
        return el?.content ?? '';
    },

    async subscribe() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            throw new Error('Prehliadač nepodporuje push notifikácie.');
        }

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Povolenie na notifikácie bolo zamietnuté.');
        }

        const registration = await navigator.serviceWorker.ready;
        const existing = await registration.pushManager.getSubscription();
        if (existing) {
            await this._sendToServer(existing);
            return existing;
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this._urlBase64ToUint8Array(this._getVapidKey()),
        });

        await this._sendToServer(subscription);
        return subscription;
    },

    async unsubscribe() {
        if (!('serviceWorker' in navigator)) return;
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        if (!subscription) return;

        await fetch('/push/subscriptions', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this._getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        await subscription.unsubscribe();
    },

    async _sendToServer(subscription) {
        const json = subscription.toJSON();
        const body = {
            endpoint: json.endpoint,
            keys: json.keys,
            contentEncoding: subscription.options?.applicationServerKey ? 'aesgcm' : null,
        };

        const response = await fetch('/push/subscriptions', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this._getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            throw new Error('Nepodarilo sa uložiť subscription (' + response.status + ').');
        }
    },
};
