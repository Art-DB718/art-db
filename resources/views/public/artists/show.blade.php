@php
    $seoDescription = $artist->short_bio
        ?: trim($artist->display_name
            .($artist->birth_year ? ' (b. '.$artist->birth_year.($artist->death_year ? '–'.$artist->death_year : '').')' : '')
            .($artist->country ? ', '.$artist->country->name : '')
            .' — represented by '.config('app.name', 'ArtDB').'.');
@endphp
<x-layouts.public
    :title="$artist->display_name.' — '.config('app.name', 'ArtDB')"
    :description="$seoDescription"
    :og-image="$artist->profile_image ?? $artist->cover_image">

    {{-- COVER --}}
    @if ($artist->cover_image)
        <section class="bg-gray-100">
            <div class="max-w-7xl mx-auto">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->cover_image) }}"
                     alt="{{ $artist->display_name }}"
                     class="w-full aspect-[3/1] object-cover">
            </div>
        </section>
    @endif

    {{-- HEADER --}}
    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-12 md:py-16">

            <nav class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-8">
                <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('artists.index') }}" class="hover:text-gray-900">Artists</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $artist->display_name }}</span>
            </nav>

            <div class="grid grid-cols-1 md:grid-cols-[200px_1fr] gap-10 items-start">
                <div>
                    @if ($artist->profile_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->profile_image) }}"
                             alt="{{ $artist->display_name }}"
                             class="w-full aspect-square object-cover rounded-full">
                    @else
                        <div class="w-full aspect-square bg-gray-100 rounded-full flex items-center justify-center text-5xl font-serif text-gray-400">
                            {{ strtoupper(substr($artist->last_name ?? $artist->first_name ?? '?', 0, 1)) }}
                        </div>
                    @endif
                </div>

                <div>
                    <h1 class="font-serif text-4xl md:text-5xl tracking-tight">{{ $artist->display_name }}</h1>

                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                        @if ($artist->birth_year)
                            <span>
                                Born {{ $artist->birth_year }}@if ($artist->birth_place), {{ $artist->birth_place }}@endif
                            </span>
                        @endif
                        @if ($artist->death_year)
                            <span>· Died {{ $artist->death_year }}</span>
                        @endif
                        @if ($artist->country)
                            <span>· {{ $artist->country->name }}</span>
                        @endif
                    </div>

                    @if ($artist->short_bio)
                        <p class="mt-6 text-gray-700 leading-relaxed max-w-3xl">{{ $artist->short_bio }}</p>
                    @endif

                    @if (is_array($artist->education ?? null) && count($artist->education))
                        <div class="mt-6 p-4 border-l-2 border-gray-300 bg-gray-50 max-w-2xl">
                            <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Education</p>
                            <ul class="text-sm text-gray-800 space-y-1">
                                @foreach ($artist->education as $edu)
                                    <li>
                                        @if (!empty($edu['institution'])){{ $edu['institution'] }}@endif
                                        @if (!empty($edu['degree'])) — {{ $edu['degree'] }}@endif
                                        @if (!empty($edu['field'])) ({{ $edu['field'] }})@endif
                                        @if (!empty($edu['year_from']) || !empty($edu['year_to']))
                                            <span class="text-xs text-gray-500">
                                                {{ $edu['year_from'] ?? '' }}@if (!empty($edu['year_to']))–{{ $edu['year_to'] }}@endif
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($artist->website || (is_array($artist->social_links) && count($artist->social_links)))
                        <div class="mt-6 flex flex-wrap gap-x-5 gap-y-2 text-xs uppercase tracking-[0.18em]">
                            @if ($artist->website)
                                <a href="{{ $artist->website }}" target="_blank" rel="noopener"
                                   class="underline underline-offset-4 hover:text-gray-500">Website</a>
                            @endif
                            @if (is_array($artist->social_links))
                                @foreach ($artist->social_links as $key => $url)
                                    @if (! empty($url))
                                        <a href="{{ $url }}" target="_blank" rel="noopener"
                                           class="underline underline-offset-4 hover:text-gray-500">{{ is_string($key) ? $key : 'Link' }}</a>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    @endif

                    @auth
                        @if (! auth()->user()->isArtist())
                            <div class="mt-6">
                                <a href="#contact-artist"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                                    Contact {{ trim($artist->first_name) }}
                                </a>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </section>

    {{-- BIOGRAPHY + STATEMENT --}}
    @if ($artist->biography || $artist->statement)
        <section class="py-16">
            <div class="max-w-4xl mx-auto px-6 grid grid-cols-1 md:grid-cols-2 gap-12">
                @if ($artist->biography)
                    <div>
                        <h2 class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Biography</h2>
                        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                            {!! nl2br(e($artist->biography)) !!}
                        </div>
                    </div>
                @endif

                @if ($artist->statement)
                    <div>
                        <h2 class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Statement</h2>
                        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed italic">
                            {!! nl2br(e($artist->statement)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ARTWORKS --}}
    @if ($artworks->isNotEmpty())
        <section class="py-16 bg-gray-50 border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex items-baseline justify-between mb-12">
                    <h2 class="font-serif text-3xl md:text-4xl">Works</h2>
                    <a href="{{ route('artworks.index', ['artist_id' => $artist->id]) }}"
                       class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
                        All works
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                    @foreach ($artworks as $artwork)
                        <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                            @if ($artwork->primary_image)
                                <div class="overflow-hidden bg-white">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                         alt="{{ $artwork->title }}"
                                         class="w-full aspect-square object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-square bg-white flex items-center justify-center text-gray-400 text-sm">no image</div>
                            @endif
                            <div class="mt-5">
                                <p class="text-gray-600 italic">{{ $artwork->title }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
                                @if ($artwork->medium)
                                    <p class="text-sm text-gray-500 mt-1">{{ $artwork->medium->name }}</p>
                                @endif
                                @if ($artwork->price && ! $artwork->price_on_request)
                                    <p class="text-sm text-gray-700 mt-1">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</p>
                                @elseif ($artwork->price_on_request)
                                    <p class="text-sm text-gray-500 mt-1">Price on request</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-16">
                    {{ $artworks->links() }}
                </div>
            </div>
        </section>
    @endif

    {{-- EXHIBITIONS --}}
    @if ($exhibitions->isNotEmpty())
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-12">Exhibitions</h2>

                <ul class="divide-y divide-gray-200">
                    @foreach ($exhibitions as $exhibition)
                        <li class="py-6">
                            <a href="{{ route('exhibitions.show', $exhibition) }}" class="grid grid-cols-1 md:grid-cols-[160px_1fr] gap-6 group">
                                @if ($exhibition->start_date || $exhibition->end_date)
                                    <p class="text-sm text-gray-500 md:text-right uppercase tracking-[0.18em]">
                                        {{ $exhibition->start_date?->format('Y') }}
                                        @if ($exhibition->end_date && $exhibition->end_date?->format('Y') !== $exhibition->start_date?->format('Y'))
                                            – {{ $exhibition->end_date->format('Y') }}
                                        @endif
                                    </p>
                                @else
                                    <span></span>
                                @endif
                                <div>
                                    <h3 class="font-serif text-xl group-hover:text-gray-500 transition">{{ $exhibition->title }}</h3>
                                    @if ($exhibition->venue)
                                        <p class="text-sm text-gray-600 mt-1">{{ $exhibition->venue }}</p>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- CONTACT FORM — Gallery / Collector only --}}
    @auth
        @if (! auth()->user()->isArtist())
            <section id="contact-artist" class="py-16 bg-gray-50 border-t border-gray-200">
                <div class="max-w-2xl mx-auto px-6">
                    <h2 class="font-serif text-3xl mb-2">Contact {{ $artist->display_name }}</h2>
                    <p class="text-sm text-gray-600 mb-8">
                        Your message goes to the gallery, which forwards it to the artist.
                        @if (auth()->user()->institution_name)
                            Sending as <strong>{{ auth()->user()->institution_name }}</strong>.
                        @endif
                    </p>

                    @if (session('inquiry_message'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-sm text-green-800">
                            {{ session('inquiry_message') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('artists.contact', $artist) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="subject" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Subject</label>
                            <input type="text" name="subject" id="subject" required maxlength="255"
                                   value="{{ old('subject', 'Interest in your work') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                            @error('subject') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="message" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Message</label>
                            <textarea name="message" id="message" rows="6" required maxlength="4000"
                                      class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">{{ old('message') }}</textarea>
                            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit"
                                class="px-8 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                            Send message
                        </button>
                    </form>
                </div>
            </section>
        @endif
    @endauth

</x-layouts.public>
