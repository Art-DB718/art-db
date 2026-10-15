{{-- PWA + push JS injected into Filament admin's <head>. Filament uses its
     own asset pipeline and does not pick up @vite from resources/views/layouts,
     so this partial carries the manifest tags and loads our app.js (SW
     registration + window.ArtDbPush helper used by the profile toggle).
     Only JS is loaded; Filament has its own Tailwind build. --}}
<x-pwa-head />
@vite(['resources/js/app.js'])
