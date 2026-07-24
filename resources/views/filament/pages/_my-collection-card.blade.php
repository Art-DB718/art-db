<a href="{{ route('artworks.show', $artwork) }}" target="_blank" rel="noopener"
   class="block group rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-gray-400 transition">
    @if ($artwork->primary_image)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
             alt="{{ $artwork->title }}"
             loading="lazy"
             class="w-full aspect-square object-cover bg-gray-100 dark:bg-gray-800 group-hover:opacity-90 transition">
    @else
        <div class="w-full aspect-square bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-400">no image</div>
    @endif
    <div class="p-3">
        <p class="text-sm font-medium text-gray-900 dark:text-white leading-tight truncate">{{ $artwork->title }}</p>
        @if ($artwork->artist)
            <p class="text-xs text-gray-500 mt-1 truncate">
                {{ $artwork->artist->display_name }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif
            </p>
        @endif
    </div>
</a>
