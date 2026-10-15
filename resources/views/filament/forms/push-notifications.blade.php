@php
    /** @var int $subscriptionCount */
    $subscriptionCount = $subscriptionCount ?? 0;
@endphp

<div
    x-data="pushNotificationsForm({ initialCount: {{ (int) $subscriptionCount }} })"
    x-init="init()"
    class="space-y-3"
>
    <div class="flex items-start gap-3">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                Push notifikácie
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="statusText"></p>
        </div>
        <button
            type="button"
            @click="toggle()"
            :disabled="busy"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition disabled:opacity-50"
            :class="subscribed
                ? 'bg-gray-100 text-gray-950 hover:bg-gray-200 dark:bg-white/5 dark:text-white dark:hover:bg-white/10'
                : 'bg-primary-600 text-white hover:bg-primary-500'"
        >
            <span x-show="!busy" x-text="subscribed ? 'Vypnúť' : 'Zapnúť'"></span>
            <span x-show="busy">...</span>
        </button>
    </div>

    <template x-if="error">
        <p class="text-sm text-danger-600 dark:text-danger-400" x-text="error"></p>
    </template>

    <template x-if="!supported">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Tento prehliadač nepodporuje Web Push. Na iPhone sa dá push zapnúť len
            po nainštalovaní appky na plochu cez Share → Add to Home Screen.
        </p>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pushNotificationsForm', ({ initialCount }) => ({
        subscribed: initialCount > 0,
        busy: false,
        error: null,
        supported: 'serviceWorker' in navigator && 'PushManager' in window,
        statusText: '',

        async init() {
            this.updateStatus();
            if (this.supported) {
                try {
                    const reg = await navigator.serviceWorker.ready;
                    const sub = await reg.pushManager.getSubscription();
                    this.subscribed = !!sub;
                    this.updateStatus();
                } catch (_e) {}
            }
        },

        updateStatus() {
            if (!this.supported) {
                this.statusText = 'Push nie je dostupný v tomto prehliadači.';
            } else if (Notification.permission === 'denied') {
                this.statusText = 'Notifikácie si zablokovala v nastaveniach prehliadača.';
            } else if (this.subscribed) {
                this.statusText = 'Zapnuté na tomto zariadení.';
            } else {
                this.statusText = 'Zapni a začnú ti chodiť upozornenia o nových správach a predajoch.';
            }
        },

        async toggle() {
            if (!this.supported || this.busy) return;
            this.busy = true;
            this.error = null;
            try {
                if (this.subscribed) {
                    await window.ArtDbPush.unsubscribe();
                    this.subscribed = false;
                } else {
                    await window.ArtDbPush.subscribe();
                    this.subscribed = true;
                }
            } catch (e) {
                this.error = e?.message || 'Zapnutie zlyhalo.';
            } finally {
                this.updateStatus();
                this.busy = false;
            }
        },
    }));
});
</script>
