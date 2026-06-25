<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My collection') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('inquiry_message'))
                <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 text-sm text-green-800 rounded">
                    {{ session('inquiry_message') }}
                </div>
            @endif

            @if ($artworks->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-12 text-center">
                    <p class="text-gray-500">Your collection is empty.</p>
                    <p class="text-xs text-gray-400 mt-2">Browse the catalogue and tap the bookmark icon on any artwork to save it here.</p>
                    <a href="{{ route('artworks.index') }}"
                       class="mt-4 inline-block px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Browse artworks
                    </a>
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-sm text-gray-500 mb-6">{{ $artworks->total() }} {{ Str::plural('work', $artworks->total()) }} saved.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-10">
                        @foreach ($artworks as $artwork)
                            <div>
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
                                </a>
                                <div class="mt-4 flex items-baseline justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold truncate">{{ $artwork->artist?->display_name ?? '—' }}</p>
                                        <p class="text-sm text-gray-600 italic truncate">{{ $artwork->title }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
                                    </div>
                                    <form method="POST" action="{{ route('artworks.save', $artwork) }}" class="shrink-0">
                                        @csrf
                                        <button type="submit"
                                                title="Remove from collection"
                                                class="text-gray-400 hover:text-red-600 transition">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <path d="M6 2v20l6-4 6 4V2z" fill="currentColor"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-10">{{ $artworks->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
