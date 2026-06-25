<x-app-layout>
    <x-slot name="header">
        <div class="flex items-baseline justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My artworks') }}
            </h2>
            <a href="/admin/artworks/create"
               class="px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                Add artwork
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if ($artworks->isEmpty())
                <div class="bg-white shadow-sm rounded-lg p-12 text-center">
                    <p class="text-gray-500">You haven't added any artworks yet.</p>
                    <a href="/admin/artworks/create"
                       class="mt-4 inline-block px-4 py-2 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Add your first artwork
                    </a>
                </div>
            @else
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs uppercase tracking-[0.18em] text-gray-500">Work</th>
                                <th class="px-6 py-3 text-left text-xs uppercase tracking-[0.18em] text-gray-500">Year</th>
                                <th class="px-6 py-3 text-left text-xs uppercase tracking-[0.18em] text-gray-500">Medium</th>
                                <th class="px-6 py-3 text-left text-xs uppercase tracking-[0.18em] text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs uppercase tracking-[0.18em] text-gray-500">Price</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($artworks as $artwork)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($artwork->primary_image)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                                     alt="{{ $artwork->title }}"
                                                     class="w-12 h-12 rounded object-cover">
                                            @else
                                                <div class="w-12 h-12 rounded bg-gray-100"></div>
                                            @endif
                                            <span class="font-medium">{{ $artwork->title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $artwork->year_created ?: '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $artwork->medium?->name ?: '—' }}</td>
                                    <td class="px-6 py-4 text-xs uppercase tracking-[0.18em]">
                                        @if ($artwork->is_published)
                                            <span class="text-green-700">Published</span>
                                        @else
                                            <span class="text-gray-400">Draft</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        @if ($artwork->price && ! $artwork->price_on_request)
                                            {{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}
                                        @elseif ($artwork->price_on_request)
                                            <span class="text-gray-400">On request</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="/admin/artworks/{{ $artwork->id }}/edit"
                                           class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $artworks->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
