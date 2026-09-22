<a href="{{ route('artworks.show', $artwork) }}" target="_blank" rel="noopener"
   class="block group rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-gray-400 transition">
    @if ($artwork->primary_image)
        <div class="bg-gray-50 dark:bg-gray-800 overflow-hidden">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                 alt="{{ $artwork->title }}"
                 loading="lazy"
                 class="w-full h-auto object-contain group-hover:opacity-90 transition">
        </div>
    @else
        <div class="w-full aspect-[4/5] bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-400">no image</div>
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
