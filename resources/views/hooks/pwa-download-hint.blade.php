{{-- Admin-only hint injected via a Filament render hook.

     Runs on every admin page. Intercepts clicks on /prints/* links
     and, *only* when the page is being viewed inside a home-screen
     PWA on iOS (window.navigator.standalone === true), rewrites the
     href with ?download=1 so PrintController switches from inline
     stream to attachment. iOS then shows the native share sheet
     (Save to Files / Print / Mail) instead of trying to open the PDF
     in the stripped PWA webview. Desktop browsers and regular mobile
     Safari are untouched — they still get the inline PDF viewer. --}}
<script>
    (function () {
        // Fire only on an iOS/iPadOS home-screen PWA. macOS Safari's
        // "Add to Dock" also sets navigator.standalone === true, so the
        // extra isIOS check keeps Mac desktop + Dock web-apps on the
        // inline-stream path.
        var ua = navigator.userAgent || '';
        var isIOS = /iPhone|iPad|iPod/.test(ua)
            // iPadOS 13+ pretends to be a Mac; detect it via touch support.
            || (navigator.platform === 'MacIntel' && (navigator.maxTouchPoints || 0) > 1);

        if (!isIOS) return;
        if (!('standalone' in window.navigator)) return;
        if (window.navigator.standalone !== true) return;

        document.addEventListener('click', function (e) {
            var a = e.target.closest('a[href*="/prints/"]');
            if (!a || !a.href) return;
            try {
                var u = new URL(a.href, window.location.origin);
                if (!u.searchParams.has('download')) {
                    u.searchParams.set('download', '1');
                    a.href = u.toString();
                }
            } catch (err) { /* malformed URL — leave alone */ }
        }, true);
    })();
</script>
