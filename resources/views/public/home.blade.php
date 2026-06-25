<x-layouts.public :title="config('app.name', 'ArtDB')">

    {{-- HERO --}}
    <section class="bg-gray-50">
        <div class="max-w-7xl mx-auto px-6 py-24 md:py-36 text-center">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-6">Student artists</p>
            <h1 class="font-serif text-5xl md:text-7xl tracking-tight leading-[1.05] mb-8">
                Before the gallery.<br>
                Before the name.<br>
                <span class="italic text-gray-700">Here.</span>
            </h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-10 leading-relaxed">
                {{ config('app.name', 'ArtDB') }} gathers students from art academies and creative
                universities and presents their work to the public, to galleries and to collectors —
                from thesis projects to first solo pieces.
            </p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="{{ route('artworks.index') }}"
                   class="inline-block px-10 py-4 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                    Browse student works
                </a>
                @guest
                    <a href="{{ route('register') }}"
                       class="inline-block px-10 py-4 border border-gray-300 text-xs uppercase tracking-[0.18em] hover:border-gray-900 transition">
                        Join as a student
                    </a>
                @endguest
            </div>
        </div>
    </section>

    {{-- FEATURED ARTWORKS --}}
    @if ($featuredArtworks->isNotEmpty())
        <section class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex items-baseline justify-between mb-12">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">From the studios</p>
                        <h2 class="font-serif text-3xl md:text-4xl">Featured works</h2>
                    </div>
                    <a href="{{ route('artworks.index') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">View all</a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                    @foreach ($featuredArtworks as $artwork)
                        <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                            @if ($artwork->primary_image)
                                <div class="overflow-hidden bg-gray-100">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                         alt="{{ $artwork->title }}"
                                         class="w-full aspect-square object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400 text-sm">
                                    no image
                                </div>
                            @endif
                            <div class="mt-5">
                                <p class="font-semibold">{{ $artwork->artist?->display_name ?? '—' }}</p>
                                <p class="text-gray-600 italic">{{ $artwork->title }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
                                @if ($artwork->price && ! $artwork->price_on_request)
                                    <p class="text-sm text-gray-700 mt-1">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</p>
                                @elseif ($artwork->price_on_request)
                                    <p class="text-sm text-gray-500 mt-1">Price on request</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- EXHIBITIONS --}}
    @if ($exhibitions->isNotEmpty())
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex items-baseline justify-between mb-12">
                    <h2 class="font-serif text-3xl md:text-4xl">Exhibitions</h2>
                    <a href="{{ route('exhibitions.index') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">View all</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach ($exhibitions as $exhibition)
                        <a href="{{ route('exhibitions.show', $exhibition) }}" class="block group">
                            @if ($exhibition->poster_image)
                                <div class="overflow-hidden bg-gray-200">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($exhibition->poster_image) }}"
                                         alt="{{ $exhibition->title }}"
                                         class="w-full aspect-[4/3] object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-[4/3] bg-gray-200 flex items-center justify-center text-gray-400">no image</div>
                            @endif
                            <div class="mt-5">
                                <p class="text-xs uppercase tracking-[0.18em] text-gray-500">
                                    {{ ucfirst($exhibition->status) }}
                                </p>
                                <h3 class="font-serif text-2xl mt-1">{{ $exhibition->title }}</h3>
                                @if ($exhibition->venue)
                                    <p class="text-gray-600 mt-1">{{ $exhibition->venue }}</p>
                                @endif
                                @if ($exhibition->start_date || $exhibition->end_date)
                                    <p class="text-sm text-gray-500 mt-2">
                                        {{ $exhibition->start_date?->format('d.m.Y') }}@if ($exhibition->end_date) – {{ $exhibition->end_date->format('d.m.Y') }} @endif
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FEATURED ARTISTS --}}
    @if ($featuredArtists->isNotEmpty())
        <section class="py-24">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex items-baseline justify-between mb-12">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Currently studying</p>
                        <h2 class="font-serif text-3xl md:text-4xl">Featured artists</h2>
                    </div>
                    <a href="{{ route('artists.index') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">View all</a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-6 gap-y-10">
                    @foreach ($featuredArtists as $artist)
                        <a href="{{ route('artists.show', $artist) }}" class="block group text-center">
                            @if ($artist->profile_image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->profile_image) }}"
                                     alt="{{ $artist->display_name }}"
                                     class="w-full aspect-square object-cover rounded-full group-hover:opacity-90 transition">
                            @else
                                <div class="w-full aspect-square bg-gray-100 rounded-full flex items-center justify-center text-3xl font-serif text-gray-400">
                                    {{ strtoupper(substr($artist->last_name ?? $artist->first_name ?? '?', 0, 1)) }}
                                </div>
                            @endif
                            <p class="mt-4 font-semibold">{{ $artist->display_name }}</p>
                            @if ($artist->birth_year)
                                <p class="text-xs text-gray-500">{{ $artist->birth_year }}@if ($artist->death_year) – {{ $artist->death_year }} @endif</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- UNIVERSITIES --}}
    @if ($universities->isNotEmpty())
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-3">Where the work comes from</p>
                    <h2 class="font-serif text-3xl md:text-4xl mb-4">Art academies on the platform</h2>
                    <p class="text-gray-600 leading-relaxed">
                        Universities and art schools whose students are publishing here.
                        Each one represents a generation finding its voice.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-px bg-gray-200 border border-gray-200">
                    @foreach ($universities as $university)
                        <div class="bg-white p-6 hover:bg-gray-50 transition">
                            <p class="font-serif text-lg leading-tight">{{ $university->name }}</p>
                            @if ($university->city)
                                <p class="text-xs text-gray-500 mt-1">{{ $university->city }}</p>
                            @endif
                            <p class="mt-4 text-xs uppercase tracking-[0.18em] text-gray-500">
                                {{ $university->artists_count }} {{ Str::plural('student', $university->artists_count) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- NEWSLETTER CTA --}}
    <section class="py-24 bg-gray-900 text-white">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h2 class="font-serif text-3xl md:text-4xl mb-4">Stay close to the studios</h2>
            <p class="text-gray-300 mb-10 leading-relaxed">
                A monthly newsletter — new student works, graduation shows, names to watch.
                No spam.
            </p>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                @csrf
                <input type="email" name="email" required placeholder="your@email.com"
                       class="flex-1 px-4 py-3 bg-white text-gray-900 rounded focus:outline-none">
                <button type="submit" class="px-6 py-3 bg-white text-gray-900 font-semibold uppercase text-xs tracking-[0.18em] rounded hover:bg-gray-200 transition">
                    Subscribe
                </button>
            </form>
            @if (session('newsletter_message'))
                <p class="mt-5 text-sm text-green-300">{{ session('newsletter_message') }}</p>
            @endif
        </div>
    </section>

</x-layouts.public>
