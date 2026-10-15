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
