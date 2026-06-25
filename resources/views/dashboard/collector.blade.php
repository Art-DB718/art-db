<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Collector dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('status'))
                <div class="px-4 py-3 bg-green-50 border border-green-200 text-sm text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-lg p-6">
                <p class="text-xs uppercase tracking-[0.18em] text-gray-500">Welcome</p>
                <h1 class="font-serif text-2xl">{{ $user->name }}</h1>
                <p class="text-sm text-gray-600 mt-2">
                    Browse the gallery's catalogue and send inquiries about works you'd like to acquire.
                    Your collection will appear here as purchases are recorded.
                </p>
                <div class="mt-4 flex gap-3">
                    <a href="{{ route('artworks.index') }}"
                       class="px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Browse artworks
                    </a>
                    <a href="{{ route('artists.index') }}"
                       class="px-4 py-2 border border-gray-300 text-xs uppercase tracking-[0.18em] hover:border-gray-900 transition">
                        Artists
                    </a>
                </div>
            </div>

            {{-- SAVED (personal collection) --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-baseline justify-between mb-4">
                    <h2 class="font-serif text-xl">My collection</h2>
                    <a href="{{ route('my.collection') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">View all</a>
                </div>

                @if ($savedArtworks->isEmpty())
                    <div class="py-12 text-center border border-dashed border-gray-200 rounded">
                        <p class="text-gray-500">Your collection is empty.</p>
                        <p class="text-xs text-gray-400 mt-2">Browse the catalogue and tap the bookmark on any artwork to save it here.</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($savedArtworks as $artwork)
                            <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                                @if ($artwork->primary_image)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                         alt="{{ $artwork->title }}"
                                         class="w-full aspect-square object-cover rounded">
                                @else
                                    <div class="w-full aspect-square bg-gray-100 rounded"></div>
                                @endif
                                <p class="mt-2 text-sm font-medium truncate">{{ $artwork->artist?->display_name ?? '—' }}</p>
                                <p class="text-xs text-gray-500 italic truncate">{{ $artwork->title }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- LIKED --}}
            @if ($likedArtworks->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h2 class="font-serif text-xl mb-4">Liked works</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($likedArtworks as $artwork)
                            <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                                @if ($artwork->primary_image)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                         alt="{{ $artwork->title }}"
                                         class="w-full aspect-square object-cover rounded">
                                @else
                                    <div class="w-full aspect-square bg-gray-100 rounded"></div>
                                @endif
                                <p class="mt-2 text-xs text-gray-500 truncate">{{ $artwork->artist?->display_name }} · <span class="italic">{{ $artwork->title }}</span></p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
