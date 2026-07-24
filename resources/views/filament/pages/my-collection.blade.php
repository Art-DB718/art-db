<x-filament-panels::page>
    <div x-data="{ tab: 'saved' }" class="space-y-6">

        {{-- Tabs --}}
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button type="button" @click="tab = 'saved'"
                    :class="tab === 'saved'
                        ? 'text-primary-600 border-primary-500'
                        : 'text-gray-600 border-transparent hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                    class="px-4 py-2 -mb-px border-b-2 text-sm font-medium transition">
                Saved to my collection
                <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800">{{ $saved->count() }}</span>
            </button>
            <button type="button" @click="tab = 'liked'"
                    :class="tab === 'liked'
                        ? 'text-primary-600 border-primary-500'
                        : 'text-gray-600 border-transparent hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                    class="px-4 py-2 -mb-px border-b-2 text-sm font-medium transition">
                Liked
                <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800">{{ $liked->count() }}</span>
            </button>
        </div>

        {{-- Saved --}}
        <div x-show="tab === 'saved'">
            @if ($saved->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-sm text-gray-500">
                    You haven't saved anything to your collection yet.
                    Browse the public archive and click <strong>Add to my collection</strong> on an artwork.
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($saved as $artwork)
                        @include('filament.pages._my-collection-card', ['artwork' => $artwork])
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Liked --}}
        <div x-show="tab === 'liked'" x-cloak>
            @if ($liked->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-sm text-gray-500">
                    You haven't liked any artworks yet.
                    Open any artwork on the public site and hit the <strong>Like</strong> heart.
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach ($liked as $artwork)
                        @include('filament.pages._my-collection-card', ['artwork' => $artwork])
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
