<x-layouts.public
    :title="'Platform features — '.config('app.name', 'ArtDB')"
    description="ArtDB is a platform for galleries, artists and collectors. Galleries manage inventory and sales; artists publish their works; collectors track and acquire pieces. The public can browse everything marked as public.">

    {{-- HERO --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-gray-50 to-white border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-28 md:py-36 relative">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-8">How it works</p>
            <h1 class="font-serif text-5xl md:text-7xl tracking-tight leading-[1.1] mb-10 max-w-4xl">
                Where student practice<br>
                <span class="italic text-gray-700">meets the world.</span>
            </h1>
            <p class="text-base md:text-lg text-gray-700 leading-loose max-w-2xl">
                {{ config('app.name', 'ArtDB') }} connects artists
                that host them, the galleries who want to discover them, and the collectors who buy
                the first piece.
            </p>

            @if (auth()->guest())
                <div class="mt-12 flex flex-wrap gap-4">
                    <a href="{{ route('register') }}"
                       class="px-10 py-4 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Create an account
                    </a>
                    <a href="{{ route('login') }}"
                       class="px-10 py-4 border border-gray-300 text-xs uppercase tracking-[0.18em] hover:border-gray-900 transition">
                        Log in
                    </a>
                </div>
            @else
                <div class="mt-12">
                    <a href="{{ route('dashboard') }}"
                       class="px-10 py-4 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Open your dashboard
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- THREE ROLES --}}
    <section class="py-28 md:py-32">
        <div class="max-w-7xl mx-auto px-6">

            <div class="text-center max-w-2xl mx-auto mb-20">
                <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-5">Who it's for</p>
                <h2 class="font-serif text-3xl md:text-4xl">Three roles, one place.</h2>
                <p class="mt-6 text-gray-600 leading-loose">
                    The students who make the work. The institutions who discover it.
                    The collectors who back it. Each gets a workspace tuned to their part of the story.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-px bg-gray-200 border border-gray-200">

                {{-- GALLERY --}}
                <a href="{{ route('platform.gallery') }}" class="block bg-white p-10 md:p-12 group hover:bg-gray-50 transition">
                    <div class="font-serif text-5xl text-gray-300 mb-8">01</div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">For institutions</p>
                    <h3 class="font-serif text-2xl mb-6">Gallery</h3>
                    <p class="text-sm text-gray-700 leading-loose mb-8">
                        Discover student work before everyone else.
                        Save shortlists, reach the studio directly, follow the schools that matter.
                    </p>
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-900 group-hover:underline underline-offset-4">
                        Read more &rarr;
                    </p>
                </a>

                {{-- ARTIST --}}
                <a href="{{ route('platform.artist') }}" class="block bg-white p-10 md:p-12 group hover:bg-gray-50 transition">
                    <div class="font-serif text-5xl text-gray-300 mb-8">02</div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">For students</p>
                    <h3 class="font-serif text-2xl mb-6">Artist</h3>
                    <p class="text-sm text-gray-700 leading-loose mb-8">
                        A public portfolio while you're still in school.
                        Publish your works, write your statement, link your gallery.
                        Sign in with your academic email.
                    </p>
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-900 group-hover:underline underline-offset-4">
                        Read more &rarr;
                    </p>
                </a>

                {{-- COLLECTOR --}}
                <a href="{{ route('platform.collector') }}" class="block bg-white p-10 md:p-12 group hover:bg-gray-50 transition">
                    <div class="font-serif text-5xl text-gray-300 mb-8">03</div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">For collectors</p>
                    <h3 class="font-serif text-2xl mb-6">Collector</h3>
                    <p class="text-sm text-gray-700 leading-loose mb-8">
                        Buy at the start of a career.
                        Browse student work, save the pieces that catch you,
                        inquire or buy directly when something is available.
                    </p>
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-900 group-hover:underline underline-offset-4">
                        Read more &rarr;
                    </p>
                </a>
            </div>
        </div>
    </section>

    {{-- PUBLIC SECTION (dark) --}}
    <section class="py-28 md:py-32 bg-gray-900 text-white">
        <div class="max-w-6xl mx-auto px-6">

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-12 items-end mb-16">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400 mb-5">Without an account</p>
                    <h2 class="font-serif text-4xl md:text-5xl leading-tight">
                        Open to everyone
                    </h2>
                </div>
                <p class="text-base text-gray-300 max-w-md leading-loose">
                    No login needed to look. Anyone arriving at {{ config('app.name', 'ArtDB') }}
                    can browse every student work, artist profile, exhibition and curated
                    collection that's been published. To save, like, contact or buy — sign up free.
                </p>
            </div>

            {{-- live counts --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-px bg-gray-800 border border-gray-800 mb-14">
                <a href="{{ route('artworks.index') }}" class="block bg-gray-900 hover:bg-gray-800 transition p-10 md:p-12 group">
                    <p class="font-serif text-6xl md:text-7xl mb-4">{{ $counts['artworks'] }}</p>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">{{ Str::plural('work', $counts['artworks']) }}</p>
                    <p class="text-sm text-gray-300 group-hover:text-white transition mt-6">Artworks &rarr;</p>
                </a>
                <a href="{{ route('artists.index') }}" class="block bg-gray-900 hover:bg-gray-800 transition p-10 md:p-12 group">
                    <p class="font-serif text-6xl md:text-7xl mb-4">{{ $counts['artists'] }}</p>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">{{ Str::plural('artist', $counts['artists']) }}</p>
                    <p class="text-sm text-gray-300 group-hover:text-white transition mt-6">Artists &rarr;</p>
                </a>
                <a href="{{ route('exhibitions.index') }}" class="block bg-gray-900 hover:bg-gray-800 transition p-10 md:p-12 group">
                    <p class="font-serif text-6xl md:text-7xl mb-4">{{ $counts['exhibitions'] }}</p>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">{{ Str::plural('exhibition', $counts['exhibitions']) }}</p>
                    <p class="text-sm text-gray-300 group-hover:text-white transition mt-6">Exhibitions &rarr;</p>
                </a>
            </div>

            <p class="text-base text-gray-400 leading-loose max-w-3xl">
                The public catalogue is read-only. To save a piece, message an artist, or buy directly,
                <a href="{{ route('register') }}" class="text-white underline underline-offset-4 hover:text-gray-300">create a free account</a>
                — or <a href="{{ route('login') }}" class="text-white underline underline-offset-4 hover:text-gray-300">log in</a> if you already have one.
            </p>
        </div>
    </section>

    {{-- OPERATED BY --}}
    @if ($settings->company_name || $settings->email)
        <section class="py-24">
            <div class="max-w-2xl mx-auto px-6 text-center">
                <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-6">Operated by</p>
                <h3 class="font-serif text-2xl mb-4">{{ $settings->company_name }}</h3>
                @if ($settings->city || $settings->country)
                    <p class="text-sm text-gray-600">{{ trim(($settings->city ?? '').($settings->country ? ', '.$settings->country : '')) }}</p>
                @endif
                @if ($settings->email)
                    <p class="mt-8">
                        <a href="mailto:{{ $settings->email }}" class="text-xs uppercase tracking-[0.18em] hover:text-gray-500 underline underline-offset-8">{{ $settings->email }}</a>
                    </p>
                @endif
            </div>
        </section>
    @endif

</x-layouts.public>
