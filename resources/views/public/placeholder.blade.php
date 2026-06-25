<x-layouts.public :title="($heading ?? 'Coming soon').' — '.config('app.name', 'ArtDB')">
    <section class="max-w-3xl mx-auto px-6 py-32 text-center">
        <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">{{ $heading ?? 'Page' }}</p>
        <h1 class="font-serif text-4xl md:text-5xl mb-6">Coming soon</h1>
        <p class="text-gray-600 leading-relaxed mb-10">
            This section is being built. Please check back soon.
        </p>
        <a href="{{ route('home') }}" class="text-xs uppercase tracking-[0.18em] underline underline-offset-4 hover:text-gray-500">
            Back to home
        </a>
    </section>
</x-layouts.public>
