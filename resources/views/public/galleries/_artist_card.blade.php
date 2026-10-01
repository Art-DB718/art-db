<a href="{{ route('artists.show', $artist) }}" class="bg-white p-6 hover:bg-gray-50 transition block">
    @if ($artist->profile_image)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->profile_image) }}" alt="" class="w-16 h-16 object-cover rounded-full mb-3">
    @endif
    <p class="font-serif text-lg leading-tight">{{ $artist->display_name }}</p>
    @if ($artist->birth_year)
        <p class="text-xs text-gray-500 mt-1">b. {{ $artist->birth_year }}</p>
    @endif
</a>
