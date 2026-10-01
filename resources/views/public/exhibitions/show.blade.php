@php
    $dates = trim(
        ($exhibition->start_date?->format('d.m.Y') ?? '')
        .($exhibition->end_date ? ' – '.$exhibition->end_date->format('d.m.Y') : '')
    );
    $seoDescription = trim(
        ucfirst($exhibition->status).' exhibition'
        .($exhibition->venue ? ' at '.$exhibition->venue : '')
        .($dates ? ', '.$dates : '')
        .($exhibition->curator ? '. Curated by '.$exhibition->curator : '')
        .'.'
    );
    if (strip_tags($exhibition->description ?? '')) {
        $seoDescription .= ' '.\Illuminate\Support\Str::limit(strip_tags($exhibition->description), 160);
    }
@endphp
<x-layouts.public
    :title="$exhibition->title.' — '.config('app.name', 'ArtDB')"
    :description="$seoDescription"
    :og-image="$exhibition->poster_image"
    :schema="[
        \App\Services\Seo\Schema::exhibition($exhibition),
        \App\Services\Seo\Schema::breadcrumbs([
            ['Exhibitions', route('exhibitions.index')],
            [$exhibition->title, route('exhibitions.show', $exhibition)],
        ]),
    ]">

    {{-- POSTER --}}
    @if ($exhibition->poster_image)
        <section class="bg-gray-100">
            <div class="max-w-7xl mx-auto">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($exhibition->poster_image) }}"
                     alt="{{ $exhibition->title }}"
                     class="w-full aspect-[3/1] object-cover">
            </div>
        </section>
    @endif

    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-12 md:py-16">
            <nav class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-8">
                <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('exhibitions.index') }}" class="hover:text-gray-900">Exhibitions</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $exhibition->title }}</span>
            </nav>

            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-3">{{ ucfirst($exhibition->status) }}</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">{{ $exhibition->title }}</h1>

            <dl class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
                @if ($exhibition->start_date || $exhibition->end_date)
                    <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-1">Dates</dt>
                        <dd class="text-gray-800">
                            {{ $exhibition->start_date?->format('d.m.Y') }}@if ($exhibition->end_date) – {{ $exhibition->end_date->format('d.m.Y') }}@endif
                        </dd>
                    </div>
                @endif

                @if ($exhibition->opening_at)
                    <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-1">Opening</dt>
                        <dd class="text-gray-800">{{ $exhibition->opening_at->format('d.m.Y H:i') }}</dd>
                    </div>
                @endif

                @if ($exhibition->venue || $exhibition->location)
                    <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-1">Venue</dt>
                        <dd class="text-gray-800">
                            @php $venueText = $exhibition->venue ?: $exhibition->location?->name; @endphp
                            @if ($hostingGallery)
                                <a href="{{ route('galleries.show', $hostingGallery) }}"
                                   class="underline underline-offset-4 decoration-gray-800 hover:text-black">{{ $venueText }}</a>
                            @else
                                {{ $venueText }}
                            @endif
                        </dd>
                    </div>
                @endif

                @if (filled($exhibition->address))
                    <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-1">Address</dt>
                        <dd class="text-gray-800 whitespace-pre-line">{{ $exhibition->address }}</dd>
                    </div>
                @endif

                @if ($exhibition->curator)
                    <div>
                        <dt class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-1">Curator</dt>
                        <dd class="text-gray-800">{{ $exhibition->curator }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </section>

    {{-- DESCRIPTION --}}
    @if ($exhibition->description || $exhibition->press_release)
        <section class="py-16">
            <div class="max-w-3xl mx-auto px-6">
                @if ($exhibition->description)
                    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                        {!! nl2br(e($exhibition->description)) !!}
                    </div>
                @endif

                @if ($exhibition->press_release)
                    <div class="mt-12">
                        <h2 class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Press release</h2>
                        <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                            {!! nl2br(e($exhibition->press_release)) !!}
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- INSTALLATION VIEWS — moved above Works on view so visitors see
         the show in situ first, then dig into individual pieces.
         Click any thumbnail to open a lightbox with prev/next arrows
         (keyboard ← → and Esc also work). --}}
    @if (is_array($exhibition->gallery_images) && count($exhibition->gallery_images))
        @php $imageUrls = collect($exhibition->gallery_images)->map(fn ($p) => \Illuminate\Support\Facades\Storage::url($p))->values(); @endphp
        <section class="py-16 border-t border-gray-200"
                 x-data="{
                     open: false,
                     index: 0,
                     images: @js($imageUrls),
                     show(i) { this.index = i; this.open = true; },
                     next() { this.index = (this.index + 1) % this.images.length; },
                     prev() { this.index = (this.index - 1 + this.images.length) % this.images.length; },
                 }"
                 x-on:keydown.escape.window="open = false"
                 x-on:keydown.arrow-right.window="if (open) next()"
                 x-on:keydown.arrow-left.window="if (open) prev()">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-10">Installation views</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($imageUrls as $i => $url)
                        <button type="button" x-on:click="show({{ $i }})"
                                class="block bg-gray-100 overflow-hidden w-full group focus:outline-none focus:ring-2 focus:ring-gray-900">
                            <img src="{{ $url }}"
                                 alt="{{ $exhibition->title }}"
                                 class="w-full aspect-[4/3] object-cover group-hover:opacity-90 transition">
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- LIGHTBOX --}}
            <div x-show="open" x-cloak x-transition.opacity
                 class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center"
                 x-on:click.self="open = false">
                <button type="button" x-on:click="open = false"
                        aria-label="Close"
                        class="absolute top-5 right-5 text-white/80 hover:text-white text-3xl leading-none">×</button>

                <button type="button" x-on:click="prev()"
                        aria-label="Previous"
                        x-show="images.length > 1"
                        class="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 text-white/80 hover:text-white text-5xl leading-none select-none">‹</button>

                <img x-bind:src="images[index]"
                     x-bind:alt="'Installation view ' + (index + 1)"
                     class="max-h-[90vh] max-w-[92vw] object-contain select-none">

                <button type="button" x-on:click="next()"
                        aria-label="Next"
                        x-show="images.length > 1"
                        class="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 text-white/80 hover:text-white text-5xl leading-none select-none">›</button>

                <div x-show="images.length > 1"
                     class="absolute bottom-5 left-1/2 -translate-x-1/2 text-xs tracking-[0.18em] uppercase text-white/70"
                     x-text="(index + 1) + ' / ' + images.length"></div>
            </div>
        </section>
    @endif

    {{-- ARTWORKS --}}
    @if ($exhibition->artworks->isNotEmpty())
        <section class="py-16 bg-gray-50 border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-10">Works on view</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12 items-start">
                    @foreach ($exhibition->artworks as $artwork)
                        <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                            @if ($artwork->primary_image)
                                <div class="overflow-hidden bg-white">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                         alt="{{ $artwork->title }}"
                                         class="w-full h-auto object-contain group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-[4/5] bg-white flex items-center justify-center text-gray-400 text-sm">no image</div>
                            @endif
                            <div class="mt-5">
                                <p class="font-semibold">{{ $artwork->artist?->display_name ?? '—' }}</p>
                                <p class="text-gray-600 italic">{{ $artwork->title }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
                                @if ($artwork->medium)
                                    <p class="text-sm text-gray-500 mt-1">{{ $artwork->medium->name }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.public>
