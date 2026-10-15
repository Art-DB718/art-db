{{--
    PWA head tags. Included from every top-level layout (web app, guest auth,
    Filament admin via render hook) so the manifest and apple-touch-icon are
    present on every page the service worker might cache.
--}}
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#1e293b">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Art-DB">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('pwa/apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('pwa/icon-192.png') }}">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('pwa/icon-512.png') }}">
{{-- VAPID public key for Web Push subscriptions. Safe to expose in HTML:
     only the server's PRIVATE key signs push messages; this half just lets
     the browser's pushManager encrypt subscriptions back to our endpoint. --}}
@if ($vapid = config('webpush.vapid.public_key'))
    <meta name="vapid-public-key" content="{{ $vapid }}">
@endif
