@php
    $gallerySeoDescription = $gallery->description
        ? \Illuminate\Support\Str::limit(strip_tags($gallery->description), 160)
        : $gallery->name.' — represented artists and works on '.config('app.name', 'Art DB').'.';
@endphp
<x-layouts.public
    :title="$gallery->name.' — '.config('app.name', 'Art DB')"
    :description="$gallerySeoDescription"
    :og-image="$gallery->cover_image ?? $gallery->logo"
    :schema="[
        \App\Services\Seo\Schema::gallery($gallery),
        \App\Services\Seo\Schema::breadcrumbs([
            ['Galleries', route('galleries.index')],
            [$gallery->name, route('galleries.show', $gallery)],
        ]),
    ]">
    <section class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <a href="{{ route('galleries.index') }}" class="text-xs uppercase tracking-[0.18em] text-gray-500 hover:text-gray-900">← All galleries</a>

            <header class="mt-6 mb-10 flex flex-col md:flex-row md:items-start gap-6 border-b border-gray-200 pb-8">
                @if ($gallery->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($gallery->logo) }}" alt="{{ $gallery->name }}" class="w-24 h-24 object-contain">
                @endif
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Gallery</p>
                    <h1 class="font-serif text-4xl md:text-5xl">{{ $gallery->name }}</h1>

                    {{-- Address — multi-line block --}}
                    @php
                        $addrLines = collect([
                            $gallery->address_line1,
                            $gallery->address_line2,
                            trim(collect([$gallery->postal_code, $gallery->city])->filter()->implode(' ')),
                            $gallery->country?->name,
                        ])->filter();
                    @endphp
                    @if ($addrLines->isNotEmpty())
                        <address class="not-italic text-sm text-gray-600 mt-4 leading-relaxed">
                            @foreach ($addrLines as $line)
                                <span class="block">{{ $line }}</span>
                            @endforeach
                        </address>
                    @endif
                </div>

                {{-- Contact block --}}
                @if ($gallery->website || $gallery->email || $gallery->phone)
                    <div class="text-sm md:text-right space-y-2 md:min-w-[200px]">
                        <p class="text-xs uppercase tracking-[0.18em] text-gray-500">Contact</p>
                        @if ($gallery->website)
                            <p><a href="{{ $gallery->website }}" target="_blank" rel="noopener" class="text-gray-900 underline hover:no-underline">{{ parse_url($gallery->website, PHP_URL_HOST) ?: $gallery->website }}</a></p>
                        @endif
                        @if ($gallery->email)
                            <p><a href="mailto:{{ $gallery->email }}" class="text-gray-900 hover:underline">{{ $gallery->email }}</a></p>
                        @endif
                        @if ($gallery->phone)
                            <p><a href="tel:{{ preg_replace('/[^+0-9]/', '', $gallery->phone) }}" class="text-gray-900 hover:underline">{{ $gallery->phone }}</a></p>
                        @endif
                    </div>
                @endif
            </header>

            @if ($gallery->description)
                <div class="prose max-w-2xl mb-12">{{ $gallery->description }}</div>
            @endif

            {{-- Artists row renderer: shows the first `$initial` cards,
                 hides the rest behind a "Show all N" toggle. 4 per row on
                 desktop, 2 on mobile. --}}
            @php
                $renderArtistCard = fn ($artist) => view('public.galleries._artist_card', ['artist' => $artist])->render();
            @endphp

            @if ($gallery->artists->isNotEmpty())
                @php $items = $gallery->artists; $initial = 4; @endphp
                <h2 class="font-serif text-2xl mb-6">Represented artists</h2>
                <div x-data="{ expanded: false }" class="mb-16">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200">
                        @foreach ($items->take($initial) as $artist)
                            {!! $renderArtistCard($artist) !!}
                        @endforeach
                    </div>
                    @if ($items->count() > $initial)
                        <div x-show="expanded" x-cloak>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200 border-t-0">
                                @foreach ($items->slice($initial) as $artist)
                                    {!! $renderArtistCard($artist) !!}
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="button" x-on:click="expanded = !expanded"
                                    class="text-xs uppercase tracking-[0.18em] text-gray-600 hover:text-gray-900 border border-gray-300 px-5 py-2">
                                <span x-show="! expanded">Show all {{ $items->count() }} artists</span>
                                <span x-show="expanded" x-cloak>Show less</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($alsoShowing->isNotEmpty())
                @php $items = $alsoShowing; $initial = 4; @endphp
                <h2 class="font-serif text-2xl mb-2">Featured artists</h2>
                <p class="text-sm text-gray-500 mb-6">Artists shown by the gallery outside their represented roster.</p>
                <div x-data="{ expanded: false }" class="mb-16">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200">
                        @foreach ($items->take($initial) as $artist)
                            {!! $renderArtistCard($artist) !!}
                        @endforeach
                    </div>
                    @if ($items->count() > $initial)
                        <div x-show="expanded" x-cloak>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200 border-t-0">
                                @foreach ($items->slice($initial) as $artist)
                                    {!! $renderArtistCard($artist) !!}
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <button type="button" x-on:click="expanded = !expanded"
                                    class="text-xs uppercase tracking-[0.18em] text-gray-600 hover:text-gray-900 border border-gray-300 px-5 py-2">
                                <span x-show="! expanded">Show all {{ $items->count() }} artists</span>
                                <span x-show="expanded" x-cloak>Show less</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <h2 class="font-serif text-2xl mb-6">Presented artworks</h2>
            @if ($artworks->isEmpty())
                <p class="text-gray-500 italic">No published artworks yet.</p>
            @else
                @php $initial = 4; @endphp
                <div x-data="{ expanded: false }">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 items-start">
                        @foreach ($artworks->take($initial) as $artwork)
                            @include('public.galleries._artwork_card', ['artwork' => $artwork])
                        @endforeach
                    </div>
                    @if ($artworks->count() > $initial)
                        <div x-show="expanded" x-cloak class="grid grid-cols-2 md:grid-cols-4 gap-6 items-start mt-6">
                            @foreach ($artworks->slice($initial) as $artwork)
                                @include('public.galleries._artwork_card', ['artwork' => $artwork])
                            @endforeach
                        </div>
                        <div class="mt-6 text-center">
                            <button type="button" x-on:click="expanded = !expanded"
                                    class="text-xs uppercase tracking-[0.18em] text-gray-600 hover:text-gray-900 border border-gray-300 px-5 py-2">
                                <span x-show="! expanded">Show all {{ $artworks->count() }} artworks</span>
                                <span x-show="expanded" x-cloak>Show less</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            @if ($currentExhibitions->isNotEmpty() || $upcomingExhibitions->isNotEmpty() || $pastExhibitions->isNotEmpty())
                <div class="mt-20">
                    <h2 class="font-serif text-2xl mb-10">Exhibitions</h2>

                    @php $initial = 3; @endphp

                    @foreach ([
                        'Current'  => $currentExhibitions,
                        'Upcoming' => $upcomingExhibitions,
                        'Past'     => $pastExhibitions,
                    ] as $label => $group)
                        @continue($group->isEmpty())
                        <h3 class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-6">{{ $label }}</h3>
                        <div x-data="{ expanded: false }" class="mb-14">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                                @foreach ($group->take($initial) as $exhibition)
                                    @include('public.exhibitions._card', ['exhibition' => $exhibition])
                                @endforeach
                            </div>
                            @if ($group->count() > $initial)
                                <div x-show="expanded" x-cloak class="grid grid-cols-1 md:grid-cols-3 gap-10 mt-10">
                                    @foreach ($group->slice($initial) as $exhibition)
                                        @include('public.exhibitions._card', ['exhibition' => $exhibition])
                                    @endforeach
                                </div>
                                <div class="mt-6 text-center">
                                    <button type="button" x-on:click="expanded = !expanded"
                                            class="text-xs uppercase tracking-[0.18em] text-gray-600 hover:text-gray-900 border border-gray-300 px-5 py-2">
                                        <span x-show="! expanded">Show all {{ $group->count() }} {{ Str::lower($label) }}</span>
                                        <span x-show="expanded" x-cloak>Show less</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
