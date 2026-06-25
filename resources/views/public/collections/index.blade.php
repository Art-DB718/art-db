<x-layouts.public :title="'Collections — '.config('app.name', 'ArtDB')">

    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-16 md:py-20">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Curated by students</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">Collections</h1>
            <p class="mt-4 text-gray-600 max-w-2xl leading-relaxed">
                Student-curated groupings of works — thesis projects, series, themes, group submissions.
                A glimpse into how the next generation is organising its practice.
            </p>
        </div>
    </section>

    <section class="py-16">
        <div class="max-w-7xl mx-auto px-6">

            @if ($collections->isEmpty())
                <div class="py-24 text-center border border-dashed border-gray-200">
                    <p class="text-gray-500">No student collections published yet.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach ($collections as $collection)
                        <a href="{{ route('collections.show', $collection) }}" class="block group">
                            @if ($collection->cover_image)
                                <div class="overflow-hidden bg-gray-100">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($collection->cover_image) }}"
                                         alt="{{ $collection->title }}"
                                         class="w-full aspect-[4/3] object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400 text-sm">no cover</div>
                            @endif
                            <div class="mt-5">
                                <p class="text-xs uppercase tracking-[0.18em] text-gray-500">
                                    {{ $collection->artworks_count }} {{ Str::plural('work', $collection->artworks_count) }}
                                </p>
                                <h3 class="font-serif text-2xl mt-1">{{ $collection->title }}</h3>
                                @if ($collection->description)
                                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ Str::limit($collection->description, 160) }}</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

</x-layouts.public>
