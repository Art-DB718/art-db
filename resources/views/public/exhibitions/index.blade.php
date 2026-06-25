<x-layouts.public :title="'Exhibitions — '.config('app.name', 'ArtDB')">

    <section class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 py-16 md:py-20">
            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">Where the work shows</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">Exhibitions</h1>
            <p class="mt-4 text-gray-600 max-w-2xl leading-relaxed">
                Graduation shows, group exhibitions, open studios and end-of-year presentations
                organised by art academies and partner venues. Current, upcoming and past.
            </p>
        </div>
    </section>

    @php
        $renderCard = function ($exhibition) {
            return view('public.exhibitions._card', ['exhibition' => $exhibition])->render();
        };
    @endphp

    {{-- CURRENT --}}
    @if ($current->isNotEmpty())
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-10">Current</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach ($current as $exhibition)
                        {!! $renderCard($exhibition) !!}
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- UPCOMING --}}
    @if ($upcoming->isNotEmpty())
        <section class="py-16 bg-gray-50 border-t border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <h2 class="font-serif text-3xl md:text-4xl mb-10">Upcoming</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach ($upcoming as $exhibition)
                        {!! $renderCard($exhibition) !!}
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- PAST --}}
    <section class="py-16 border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="font-serif text-3xl md:text-4xl mb-10">Past</h2>

            @if ($past->isEmpty())
                <p class="text-gray-500">No past exhibitions yet.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($past as $exhibition)
                        {!! $renderCard($exhibition) !!}
                    @endforeach
                </div>

                <div class="mt-16">
                    {{ $past->links() }}
                </div>
            @endif
        </div>
    </section>

</x-layouts.public>
