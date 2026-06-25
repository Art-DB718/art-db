<x-layouts.public :title="'Thank you — '.config('app.name', 'ArtDB')">
    <section class="max-w-2xl mx-auto px-6 py-32 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Order received</p>
        <h1 class="font-serif text-4xl md:text-5xl mb-6">Thank you.</h1>
        <p class="text-gray-600 leading-relaxed mb-10">
            Your purchase of <em>{{ $artwork->title }}</em>
            @if ($artwork->artist) by <strong>{{ $artwork->artist->display_name }}</strong>@endif
            has been received. We will be in touch shortly with delivery details.
        </p>
        <a href="{{ route('artworks.index') }}"
           class="inline-block px-10 py-4 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
            Continue browsing
        </a>
    </section>
</x-layouts.public>
