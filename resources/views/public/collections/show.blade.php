<x-layouts.public :title="$collection->title.' — '.config('app.name', 'ArtDB')">

    {{-- HERO --}}
    @if ($collection->cover_image)
        <section class="bg-gray-100">
            <div class="max-w-7xl mx-auto">
                <img src="{{ \Illuminate\Support\Facades\Storage::url($collection->cover_image) }}"
                     alt="{{ $collection->title }}"
                     class="w-full aspect-[3/1] object-cover">
            </div>
        </section>
    @endif

    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-12 md:py-16">
            <nav class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-8">
                <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
                <span class="mx-2">/</span>
                <a href="{{ route('collections.index') }}" class="hover:text-gray-900">Collections</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">{{ $collection->title }}</span>
            </nav>

            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-3">Collection</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">{{ $collection->title }}</h1>

            @if ($collection->description)
                <p class="mt-6 text-gray-700 leading-relaxed max-w-3xl">{!! nl2br(e($collection->description)) !!}</p>
            @endif

            <p class="mt-6 text-sm text-gray-500">
                {{ $collection->artworks->count() }} {{ Str::plural('work', $collection->artworks->count()) }}
            </p>
        </div>
    </section>

    {{-- CHILDREN (sub-collections) --}}
    @if ($collection->children->isNotEmpty())
        <section class="py-12 border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-6">Sub-collections</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($collection->children as $child)
                        <a href="{{ route('collections.show', $child) }}" class="block group">
                            @if ($child->cover_image)
                                <div class="overflow-hidden bg-gray-100">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($child->cover_image) }}"
                                         alt="{{ $child->title }}"
                                         class="w-full aspect-square object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-square bg-gray-100"></div>
                            @endif
                            <p class="mt-3 text-sm font-serif text-lg">{{ $child->title }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- WORKS --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-6">

            @if ($collection->artworks->isEmpty())
                <div class="py-24 text-center border border-dashed border-gray-200">
                    <p class="text-gray-500">This collection is empty.</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-12">
                    @foreach ($collection->artworks as $artwork)
                        <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                            @if ($artwork->primary_image)
                                <div class="overflow-hidden bg-gray-100">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                         alt="{{ $artwork->title }}"
                                         class="w-full aspect-square object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400 text-sm">no image</div>
                            @endif
                            <div class="mt-5">
                                <p class="font-semibold">{{ $artwork->artist?->display_name ?? '—' }}</p>
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
            @endif
        </div>
    </section>

</x-layouts.public>
