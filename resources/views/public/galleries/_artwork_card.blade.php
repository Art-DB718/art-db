<a href="{{ route('artworks.show', $artwork) }}" class="block group">
    @if ($artwork->primary_image)
        <div class="bg-gray-50 mb-3 overflow-hidden">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                 alt="{{ $artwork->title }}"
                 loading="lazy"
                 class="w-full h-auto object-contain group-hover:opacity-90 transition">
        </div>
    @else
        <div class="w-full aspect-[4/5] bg-gray-100 mb-3 flex items-center justify-center text-xs text-gray-400">no image</div>
    @endif
    <p class="text-sm font-medium text-gray-900 leading-tight">{{ $artwork->title }}</p>
    @if ($artwork->artist)
        <p class="text-xs text-gray-500 mt-1">{{ $artwork->artist->display_name }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
    @endif
</a>
