<x-layouts.public :title="'Artworks — '.config('app.name', 'ArtDB')">

    {{-- HEADER --}}
    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-16 md:py-20">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">From the studios</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">Student works</h1>
            <p class="mt-4 text-gray-600 max-w-2xl leading-relaxed">
                Paintings, sculpture, photography, video, installation —
                published by students from art academies on the platform.
                Filter by medium, year or price to find what you're looking for.
            </p>
        </div>
    </section>

    {{-- FILTER + RESULTS --}}
    <section class="py-12">
        <div class="max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-10">

            {{-- FILTER SIDEBAR --}}
            <aside>
                <form method="GET" action="{{ route('artworks.index') }}" class="space-y-6 sticky top-24">

                    @if (request('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif
                    @if (request('view'))
                        <input type="hidden" name="view" value="{{ request('view') }}">
                    @endif

                    <div>
                        <label for="q" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Search</label>
                        <input type="text" name="q" id="q" value="{{ request('q') }}"
                               placeholder="Title or artist…"
                               class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                    </div>

                    <div>
                        <label for="artist_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Artist</label>
                        <select name="artist_id" id="artist_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                            <option value="">All artists</option>
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->id }}" @selected((int) request('artist_id') === $artist->id)>
                                    {{ trim(($artist->last_name ?? '').', '.($artist->first_name ?? ''), ', ') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="medium_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Medium</label>
                        <select name="medium_id" id="medium_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                            <option value="">All mediums</option>
                            @foreach ($mediums as $medium)
                                <option value="{{ $medium->id }}" @selected((int) request('medium_id') === $medium->id)>{{ $medium->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="genre_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Genre</label>
                        <select name="genre_id" id="genre_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                            <option value="">All genres</option>
                            @foreach ($genres as $genre)
                                <option value="{{ $genre->id }}" @selected((int) request('genre_id') === $genre->id)>{{ $genre->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Status</label>
                        <select name="status_id" id="status_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" @selected((int) request('status_id') === $status->id)>{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="gallery_id" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Gallery</label>
                        <select name="gallery_id" id="gallery_id" class="w-full px-3 py-2 border border-gray-300 text-sm bg-white focus:outline-none focus:border-gray-900">
                            <option value="">All galleries</option>
                            @foreach ($galleries as $g)
                                <option value="{{ $g->id }}" @selected((int) request('gallery_id') === $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <p class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Year</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="year_from" placeholder="From" value="{{ request('year_from') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                            <input type="number" name="year_to" placeholder="To" value="{{ request('year_to') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div>
                        <p class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Price</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" step="0.01" name="price_from" placeholder="Min" value="{{ request('price_from') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                            <input type="number" step="0.01" name="price_to" placeholder="Max" value="{{ request('price_to') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit"
                                class="flex-1 px-4 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                            Apply
                        </button>
                        <a href="{{ route('artworks.index') }}"
                           class="px-4 py-3 border border-gray-300 text-xs uppercase tracking-[0.18em] text-gray-700 hover:border-gray-900 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </aside>

            {{-- RESULTS --}}
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                    <p class="text-sm text-gray-500">
                        {{ $artworks->total() }} {{ Str::plural('work', $artworks->total()) }}
                        @if ($artworks->lastPage() > 1)
                            — page {{ $artworks->currentPage() }} of {{ $artworks->lastPage() }}
                        @endif
                    </p>

                    {{-- VIEW MODE SWITCH --}}
                    <div class="flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-gray-500">
                        <span class="mr-1">View</span>
                        @php
                            $viewLabels = [
                                'cards'   => 'Cards',
                                'gallery' => 'Gallery',
                                'list'    => 'List',
                            ];
                        @endphp
                        @foreach ($viewOptions as $opt)
                            @php
                                $isActiveView = $view === $opt;
                                $vUrl = request()->fullUrlWithQuery(['view' => $opt, 'page' => 1]);
                            @endphp
                            <a href="{{ $vUrl }}"
                               class="px-3 py-1.5 border {{ $isActiveView ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 text-gray-700 hover:border-gray-900' }} transition">
                                {{ $viewLabels[$opt] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if ($artworks->isEmpty())
                    <div class="py-24 text-center border border-dashed border-gray-200">
                        <p class="text-gray-500">No student works match your filters.</p>
                        <a href="{{ route('artworks.index') }}" class="mt-4 inline-block text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">Clear filters</a>
                    </div>
                @else
                    @if ($view === 'gallery')
                        {{-- GALLERY: large image-first grid, minimal info --}}
                        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                            @foreach ($artworks as $artwork)
                                <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                                    @if ($artwork->primary_image)
                                        <div class="overflow-hidden bg-gray-100">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                                 alt="{{ $artwork->title }}"
                                                 class="w-full aspect-[4/5] object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                        </div>
                                    @else
                                        <div class="w-full aspect-[4/5] bg-gray-100 flex items-center justify-center text-gray-400 text-sm">no image</div>
                                    @endif
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-700">{{ $artwork->artist?->display_name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500 italic">{{ $artwork->title }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                    @elseif ($view === 'list')
                        {{-- LIST: single column, image left + full info right --}}
                        <ul class="divide-y divide-gray-200">
                            @foreach ($artworks as $artwork)
                                <li>
                                    <a href="{{ route('artworks.show', $artwork) }}" class="block py-6 group grid grid-cols-[120px_1fr] sm:grid-cols-[200px_1fr] gap-6">
                                        @if ($artwork->primary_image)
                                            <div class="overflow-hidden bg-gray-100">
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                                     alt="{{ $artwork->title }}"
                                                     class="w-full aspect-square object-cover group-hover:opacity-90 transition">
                                            </div>
                                        @else
                                            <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400 text-xs">no image</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold group-hover:text-gray-500 transition">{{ $artwork->artist?->display_name ?? '—' }}</p>
                                            <p class="font-serif text-lg italic mt-1">{{ $artwork->title }}@if ($artwork->year_created), <span class="not-italic">{{ $artwork->year_created }}</span>@endif</p>
                                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                                                @if ($artwork->medium)
                                                    <span>{{ $artwork->medium->name }}</span>
                                                @endif
                                                @if ($artwork->height_cm || $artwork->width_cm)
                                                    <span>
                                                        {{ rtrim(rtrim((string) $artwork->height_cm, '0'), '.') }} ×
                                                        {{ rtrim(rtrim((string) $artwork->width_cm, '0'), '.') }}
                                                        @if ($artwork->depth_cm) × {{ rtrim(rtrim((string) $artwork->depth_cm, '0'), '.') }}@endif cm
                                                    </span>
                                                @endif
                                                @if ($artwork->status)
                                                    <span class="uppercase tracking-[0.18em] text-xs">{{ $artwork->status->name }}</span>
                                                @endif
                                            </div>
                                            <div class="mt-3">
                                                @if ($artwork->price && ! $artwork->price_on_request)
                                                    <p class="text-sm text-gray-800">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</p>
                                                @elseif ($artwork->price_on_request)
                                                    <p class="text-sm text-gray-500">Price on request</p>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                    @else
                        {{-- CARDS (default) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-12">
                            @foreach ($artworks as $artwork)
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

                    {{-- PER-PAGE + PAGINATION --}}
                    <div class="mt-16 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div class="flex items-center gap-3 text-xs uppercase tracking-[0.18em] text-gray-500">
                            <span>Show</span>
                            @foreach ($perPageOptions as $option)
                                @php
                                    $isActive = $perPage === $option;
                                    $url = request()->fullUrlWithQuery(['per_page' => $option, 'page' => 1]);
                                @endphp
                                <a href="{{ $url }}"
                                   class="px-3 py-1.5 border {{ $isActive ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 text-gray-700 hover:border-gray-900' }} transition">
                                    {{ $option }}
                                </a>
                            @endforeach
                            <span class="hidden sm:inline">per page</span>
                        </div>

                        <div>
                            {{ $artworks->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

</x-layouts.public>
