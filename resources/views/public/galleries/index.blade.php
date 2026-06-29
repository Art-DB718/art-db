<x-layouts.public title="Galleries">
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="max-w-3xl mb-12">
                <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-3">Project Arch</p>
                <h1 class="font-serif text-4xl md:text-5xl mb-4">Galleries</h1>
                <p class="text-gray-600 leading-relaxed">Contemporary art galleries represented on Project Arch — discover their roster of artists and current programme.</p>
            </div>

            @if ($galleries->isEmpty())
                <p class="text-gray-500 italic">No galleries published yet.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-px bg-gray-200 border border-gray-200">
                    @foreach ($galleries as $gallery)
                        <a href="{{ route('galleries.show', $gallery) }}" class="bg-white p-6 hover:bg-gray-50 transition block">
                            @if ($gallery->logo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($gallery->logo) }}" alt="{{ $gallery->name }}" class="w-16 h-16 object-contain mb-4">
                            @endif
                            <p class="font-serif text-xl leading-tight mb-1">{{ $gallery->name }}</p>
                            @if ($gallery->city)
                                <p class="text-xs text-gray-500">{{ $gallery->city }}</p>
                            @endif
                            <p class="mt-4 text-xs uppercase tracking-[0.18em] text-gray-500">
                                {{ $gallery->artists_count }} {{ Str::plural('artist', $gallery->artists_count) }}
                            </p>
                        </a>
                    @endforeach
                </div>
                <div class="mt-10">{{ $galleries->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.public>
