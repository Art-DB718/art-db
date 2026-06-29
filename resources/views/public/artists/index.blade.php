<x-layouts.public :title="'Artists — '.config('app.name', 'ArtDB')">

    {{-- HEADER --}}
    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-16 md:py-20">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Currently studying</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">Student artists</h1>
            <p class="mt-4 text-gray-600 max-w-2xl leading-relaxed">
                Painters, sculptors, photographers and multimedia artists publishing their
                practice across the region. Filter by gallery or country.
            </p>
        </div>
    </section>

    {{-- FILTERS --}}
    <section class="border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <form method="GET" action="{{ route('artists.index') }}" class="flex flex-col md:flex-row md:items-end gap-4">
                <div class="flex-1">
                    <label for="q" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Search</label>
                    <input type="text" name="q" id="q" value="{{ request('q') }}"
                           placeholder="Name…"
                           class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                </div>
                <div class="w-full md:w-56">
                    <label for="country_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Country</label>
                    <select name="country_id" id="country_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                        <option value="">All countries</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((int) request('country_id') === $country->id)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-64">
                    <label for="gallery_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Gallery</label>
                    <select name="gallery_id" id="gallery_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                        <option value="">All galleries</option>
                        @foreach ($galleries as $g)
                            <option value="{{ $g->id }}" @selected((int) request('gallery_id') === $g->id)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-6 py-2.5 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Apply
                    </button>
                    <a href="{{ route('artists.index') }}"
                       class="px-6 py-2.5 border border-gray-300 text-xs uppercase tracking-[0.18em] text-gray-700 hover:border-gray-900 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </section>

    {{-- GRID --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-6">

            <p class="text-sm text-gray-500 mb-10">
                {{ $artists->total() }} {{ Str::plural('artist', $artists->total()) }}
                @if ($artists->lastPage() > 1)
                    — page {{ $artists->currentPage() }} of {{ $artists->lastPage() }}
                @endif
            </p>

            @if ($artists->isEmpty())
                <div class="py-24 text-center border border-dashed border-gray-200">
                    <p class="text-gray-500">No students match your filters.</p>
                    <a href="{{ route('artists.index') }}" class="mt-4 inline-block text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">Clear filters</a>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-12">
                    @foreach ($artists as $artist)
                        <a href="{{ route('artists.show', $artist) }}" class="block group text-center">
                            @if ($artist->profile_image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->profile_image) }}"
                                     alt="{{ $artist->display_name }}"
                                     class="w-full aspect-square object-cover rounded-full group-hover:opacity-90 transition">
                            @else
                                <div class="w-full aspect-square bg-gray-100 rounded-full flex items-center justify-center text-4xl font-serif text-gray-400">
                                    {{ strtoupper(substr($artist->last_name ?? $artist->first_name ?? '?', 0, 1)) }}
                                </div>
                            @endif
                            <p class="mt-4 font-semibold">{{ $artist->display_name }}</p>
                            @if ($artist->birth_year)
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $artist->birth_year }}@if ($artist->death_year) – {{ $artist->death_year }}@endif
                                </p>
                            @endif
                            @if ($artist->field_of_study)
                                <p class="text-xs text-gray-400 mt-0.5">{{ $artist->field_of_study }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>

                <div class="mt-16">
                    {{ $artists->links() }}
                </div>
            @endif
        </div>
    </section>

</x-layouts.public>
