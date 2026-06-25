<x-layouts.private-room title="Expired">
    <section class="max-w-2xl mx-auto px-6 py-32 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">{{ $room->title }}</p>
        <h1 class="font-serif text-4xl md:text-5xl mb-6">This viewing has expired</h1>
        <p class="text-gray-600 leading-relaxed mb-10">
            The private room you are trying to open is no longer available.
            Please contact the gallery if you would like to revisit these works.
        </p>
        <a href="{{ route('platform') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
            Contact the gallery
        </a>
    </section>
</x-layouts.private-room>
