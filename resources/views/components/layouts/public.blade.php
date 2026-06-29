@props([
    'title'       => null,
    'description' => null,
    'ogImage'     => null,
])
@php
    $pageTitle       = $title ?? config('app.name', 'ArtDB');
    $metaDescription = $description ?? "Project Arch is an online archive and platform for galleries, artists and collectors — browse contemporary art, follow artists, curate collections.";
    $ogImageUrl      = $ogImage
        ? (str_starts_with($ogImage, 'http') ? $ogImage : \Illuminate\Support\Facades\Storage::url($ogImage))
        : null;
    $canonical = url()->current();
@endphp
<!DOCTYPE html>
<html lang="en" class="bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'ArtDB') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($ogImageUrl)
        <meta property="og:image" content="{{ $ogImageUrl }}">
    @endif

    {{-- Twitter --}}
    <meta name="twitter:card" content="{{ $ogImageUrl ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if ($ogImageUrl)
        <meta name="twitter:image" content="{{ $ogImageUrl }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased font-sans">

    <header x-data="{ menuOpen: false, platformOpen: false }" class="border-b border-gray-200 bg-white/95 backdrop-blur sticky top-0 z-20">
        <nav class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-between gap-6">
            <a href="{{ route('home') }}" class="font-serif text-2xl tracking-tight">
                {{ config('app.name', 'ArtDB') }}
            </a>

            {{-- Mobile burger --}}
            <button type="button" @click="menuOpen = !menuOpen"
                    class="md:hidden p-2 -mr-2 text-gray-700 hover:text-gray-900"
                    :aria-expanded="menuOpen.toString()" aria-label="Toggle menu">
                <svg x-show="!menuOpen" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
                <svg x-show="menuOpen" x-cloak width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M6 6l12 12M18 6l-12 12"/>
                </svg>
            </button>

            <ul class="hidden md:flex items-center gap-8 text-xs uppercase tracking-[0.18em] text-gray-700">
                <li><a href="{{ route('artworks.index') }}" class="hover:text-gray-400 transition">Artworks</a></li>
                <li><a href="{{ route('artists.index') }}" class="hover:text-gray-400 transition">Artists</a></li>
                <li><a href="{{ route('exhibitions.index') }}" class="hover:text-gray-400 transition">Exhibitions</a></li>
                <li><a href="{{ route('collections.index') }}" class="hover:text-gray-400 transition">Collections</a></li>
                {{-- Platform dropdown --}}
                <li class="relative group">
                    <a href="{{ route('platform') }}" class="hover:text-gray-400 transition flex items-center gap-1">
                        Platform
                        <svg width="12" height="12" class="transition-transform group-hover:rotate-180" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 4.5l3 3 3-3"/>
                        </svg>
                    </a>
                    <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-opacity duration-150 absolute top-full left-1/2 -translate-x-1/2 pt-3 z-30">
                        <div class="bg-white border border-gray-200 shadow-lg min-w-[200px] py-2">
                            <a href="{{ route('platform.gallery') }}"
                               class="block px-5 py-2.5 text-xs uppercase tracking-[0.18em] text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition">
                                Gallery
                            </a>
                            <a href="{{ route('platform.artist') }}"
                               class="block px-5 py-2.5 text-xs uppercase tracking-[0.18em] text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition">
                                Artist
                            </a>
                            <a href="{{ route('platform.collector') }}"
                               class="block px-5 py-2.5 text-xs uppercase tracking-[0.18em] text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition">
                                Collector
                            </a>
                        </div>
                    </div>
                </li>

                <li class="pl-6 ml-2 border-l border-gray-200 flex items-center gap-5">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-gray-400 transition">
                            {{ trim(explode(' ', auth()->user()->name)[0] ?: 'Account') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="uppercase tracking-[0.18em] text-gray-500 hover:text-gray-900 transition">
                                Log out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-gray-400 transition">Log in</a>
                        <a href="{{ route('register') }}"
                           class="px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                            Sign up
                        </a>
                    @endauth
                </li>
            </ul>
        </nav>

        {{-- Mobile drawer --}}
        <div x-show="menuOpen" x-cloak x-transition.opacity
             class="md:hidden border-t border-gray-200 bg-white">
            <ul class="px-6 py-4 space-y-1 text-sm uppercase tracking-[0.18em] text-gray-700">
                <li><a href="{{ route('artworks.index') }}" class="block py-2.5 hover:text-gray-400">Artworks</a></li>
                <li><a href="{{ route('artists.index') }}" class="block py-2.5 hover:text-gray-400">Artists</a></li>
                <li><a href="{{ route('exhibitions.index') }}" class="block py-2.5 hover:text-gray-400">Exhibitions</a></li>
                <li><a href="{{ route('collections.index') }}" class="block py-2.5 hover:text-gray-400">Collections</a></li>

                {{-- Platform with collapsible sub-menu --}}
                <li>
                    <button type="button" @click="platformOpen = !platformOpen"
                            class="w-full flex items-center justify-between py-2.5 hover:text-gray-400">
                        Platform
                        <svg width="12" height="12" class="transition-transform" :class="{ 'rotate-180': platformOpen }"
                             viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 4.5l3 3 3-3"/>
                        </svg>
                    </button>
                    <ul x-show="platformOpen" x-cloak class="pl-4 pb-2 space-y-1 text-xs">
                        <li><a href="{{ route('platform.gallery') }}"   class="block py-2 text-gray-600 hover:text-gray-900">Gallery</a></li>
                        <li><a href="{{ route('platform.artist') }}"    class="block py-2 text-gray-600 hover:text-gray-900">Artist</a></li>
                        <li><a href="{{ route('platform.collector') }}" class="block py-2 text-gray-600 hover:text-gray-900">Collector</a></li>
                    </ul>
                </li>

                <li class="pt-3 mt-3 border-t border-gray-200">
                    @auth
                        <a href="{{ route('dashboard') }}" class="block py-2.5 hover:text-gray-400">
                            {{ trim(explode(' ', auth()->user()->name)[0] ?: 'Account') }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block py-2.5 text-gray-500 hover:text-gray-900 uppercase tracking-[0.18em] text-sm w-full text-left">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="block py-2.5 hover:text-gray-400">Log in</a>
                        <a href="{{ route('register') }}"
                           class="block mt-2 px-4 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] text-center hover:bg-gray-700">
                            Sign up
                        </a>
                    @endauth
                </li>
            </ul>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-gray-200 bg-white">
        <div class="max-w-7xl mx-auto px-6 py-14 grid grid-cols-1 md:grid-cols-3 gap-12">
            <div>
                <h3 class="font-serif text-xl mb-3">{{ config('app.name', 'Project Arch') }}</h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                    An art database platform for galleries, collectors, artists and institutions.
                    Private, public, or anywhere in between — you choose what to share.
                </p>
            </div>

            <div>
                <h3 class="font-serif text-xl mb-3">Newsletter</h3>
                <p class="text-sm text-gray-600 mb-4">New works in the archive, upcoming exhibitions, names to watch — once a month, no spam.</p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                    @csrf
                    <input type="email" name="email" required placeholder="your@email.com"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-gray-900">
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-wider rounded hover:bg-gray-700 transition">
                        Subscribe
                    </button>
                </form>
                @if (session('newsletter_message'))
                    <p class="mt-3 text-xs text-green-700">{{ session('newsletter_message') }}</p>
                @endif
            </div>

            <div class="text-sm text-gray-500 md:text-right">
                © {{ now()->year }} {{ config('app.name', 'ArtDB') }}.<br>
                All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
