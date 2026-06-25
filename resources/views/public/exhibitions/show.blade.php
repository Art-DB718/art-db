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
    :og-image="$exhibition->poster_image">

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
                        <dd class="text-gray-800">{{ $exhibition->venue ?: $exhibition->location?->name }}</dd>
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

    {{-- ARTWORKS --}}
    @if ($exhibition->artworks->isNotEmpty())
        <section class="py-16 bg-gray-50 border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-10">Works on view</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                    @foreach ($exhibition->artworks as $artwork)
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

    {{-- GALLERY IMAGES --}}
    @if (is_array($exhibition->gallery_images) && count($exhibition->gallery_images))
        <section class="py-16 border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-10">Installation views</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($exhibition->gallery_images as $image)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($image) }}" target="_blank" class="block bg-gray-100 overflow-hidden">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                                 alt="{{ $exhibition->title }}"
                                 class="w-full aspect-[4/3] object-cover hover:opacity-90 transition">
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

</x-layouts.public>
