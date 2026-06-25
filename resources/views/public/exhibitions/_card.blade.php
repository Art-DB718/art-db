<a href="{{ route('exhibitions.show', $exhibition) }}" class="block group">
    @if ($exhibition->poster_image)
        <div class="overflow-hidden bg-gray-100">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($exhibition->poster_image) }}"
                 alt="{{ $exhibition->title }}"
                 class="w-full aspect-[4/3] object-cover group-hover:scale-[1.02] transition-transform duration-300">
        </div>
    @else
        <div class="w-full aspect-[4/3] bg-gray-100 flex items-center justify-center text-gray-400 text-sm">no poster</div>
    @endif
    <div class="mt-5">
        <p class="text-xs uppercase tracking-[0.18em] text-gray-500">{{ ucfirst($exhibition->status) }}</p>
        <h3 class="font-serif text-2xl mt-1">{{ $exhibition->title }}</h3>
        @if ($exhibition->venue)
            <p class="text-gray-600 mt-1">{{ $exhibition->venue }}</p>
        @endif
        @if ($exhibition->start_date || $exhibition->end_date)
            <p class="text-sm text-gray-500 mt-2">
                {{ $exhibition->start_date?->format('d.m.Y') }}@if ($exhibition->end_date) – {{ $exhibition->end_date->format('d.m.Y') }}@endif
            </p>
        @endif
    </div>
</a>
