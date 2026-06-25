<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Artist dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('status'))
                <div class="px-4 py-3 bg-green-50 border border-green-200 text-sm text-green-800 rounded">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Profile card --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-center gap-6">
                    @if ($profile?->profile_image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->profile_image) }}"
                             alt="{{ $profile->display_name }}"
                             class="w-20 h-20 rounded-full object-cover">
                    @else
                        <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center text-2xl font-serif text-gray-400">
                            {{ strtoupper(substr($profile?->last_name ?? $user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <p class="text-xs uppercase tracking-[0.18em] text-gray-500">Welcome</p>
                        <h1 class="font-serif text-2xl">{{ $profile?->display_name ?? $user->name }}</h1>
                        @if ($profile && ! $profile->is_published)
                            <p class="text-xs text-amber-700 mt-1">Profile not yet published. Contact gallery to publish.</p>
                        @endif
                    </div>
                    <a href="/admin" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
                        Open admin
                    </a>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Total artworks</p>
                    <p class="font-serif text-3xl">{{ $totalCount }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Published</p>
                    <p class="font-serif text-3xl">{{ $publishedCount }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Draft</p>
                    <p class="font-serif text-3xl">{{ $totalCount - $publishedCount }}</p>
                </div>
            </div>

            {{-- Recent artworks --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-baseline justify-between mb-6">
                    <h2 class="font-serif text-xl">My recent artworks</h2>
                    <a href="{{ route('my.artworks') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
                        View all
                    </a>
                </div>

                @if ($artworks->isEmpty())
                    <div class="py-12 text-center border border-dashed border-gray-200 rounded">
                        <p class="text-gray-500">You haven't added any artworks yet.</p>
                        <a href="/admin/artworks/create" class="mt-3 inline-block px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                            Add your first artwork
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        @foreach ($artworks as $artwork)
                            <a href="/admin/artworks/{{ $artwork->id }}/edit" class="block group">
                                @if ($artwork->primary_image)
                                    <div class="overflow-hidden bg-gray-100 rounded">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                             alt="{{ $artwork->title }}"
                                             class="w-full aspect-square object-cover group-hover:opacity-90 transition">
                                    </div>
                                @else
                                    <div class="w-full aspect-square bg-gray-100 rounded flex items-center justify-center text-gray-400 text-xs">no image</div>
                                @endif
                                <p class="mt-2 text-sm font-medium truncate">{{ $artwork->title }}</p>
                                <p class="text-xs text-gray-500">{{ $artwork->is_published ? 'Published' : 'Draft' }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- COLLECTIONS --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex items-baseline justify-between mb-6">
                    <h2 class="font-serif text-xl">My collections</h2>
                    <a href="/admin/collections/create"
                       class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
                        + New collection
                    </a>
                </div>

                @if ($collections->isEmpty())
                    <div class="py-12 text-center border border-dashed border-gray-200 rounded">
                        <p class="text-gray-500">You haven't curated any collections yet.</p>
                        <p class="text-xs text-gray-400 mt-2">Group works by theme, series or material — visible publicly when you publish a collection.</p>
                        <a href="/admin/collections/create"
                           class="mt-4 inline-block px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                            Create your first collection
                        </a>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($collections as $collection)
                            <li class="py-4 flex items-center gap-4">
                                @if ($collection->cover_image)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($collection->cover_image) }}"
                                         alt="{{ $collection->title }}"
                                         class="w-16 h-16 rounded object-cover flex-shrink-0">
                                @else
                                    <div class="w-16 h-16 rounded bg-gray-100 flex-shrink-0"></div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="font-serif text-lg truncate">{{ $collection->title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $collection->artworks_count }} {{ Str::plural('work', $collection->artworks_count) }}
                                        @if ($collection->is_public)
                                            <span class="ml-2 text-green-700">· Public</span>
                                        @else
                                            <span class="ml-2 text-gray-400">· Private</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 text-xs uppercase tracking-[0.18em]">
                                    @if ($collection->is_public)
                                        <a href="{{ route('collections.show', $collection) }}" target="_blank"
                                           class="text-gray-500 hover:text-gray-900">View →</a>
                                    @endif
                                    <a href="/admin/collections/{{ $collection->id }}/edit"
                                       class="px-3 py-1.5 border border-gray-300 hover:border-gray-900 transition">
                                        Edit
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
