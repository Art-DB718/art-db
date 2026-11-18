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
