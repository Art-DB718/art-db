@php
    $gallerySeoDescription = $gallery->description
        ? \Illuminate\Support\Str::limit(strip_tags($gallery->description), 160)
        : $gallery->name.' — represented artists and works on '.config('app.name', 'Art DB').'.';
@endphp
<x-layouts.public
    :title="$gallery->name.' — '.config('app.name', 'Art DB')"
    :description="$gallerySeoDescription"
    :og-image="$gallery->cover_image ?? $gallery->logo">
    <section class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <a href="{{ route('galleries.index') }}" class="text-xs uppercase tracking-[0.18em] text-gray-500 hover:text-gray-900">← All galleries</a>

            <header class="mt-6 mb-10 flex flex-col md:flex-row md:items-start gap-6 border-b border-gray-200 pb-8">
                @if ($gallery->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($gallery->logo) }}" alt="{{ $gallery->name }}" class="w-24 h-24 object-contain">
                @endif
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Gallery</p>
                    <h1 class="font-serif text-4xl md:text-5xl">{{ $gallery->name }}</h1>

                    {{-- Address — multi-line block --}}
                    @php
                        $addrLines = collect([
                            $gallery->address_line1,
                            $gallery->address_line2,
                            trim(collect([$gallery->postal_code, $gallery->city])->filter()->implode(' ')),
                            $gallery->country?->name,
                        ])->filter();
                    @endphp
                    @if ($addrLines->isNotEmpty())
                        <address class="not-italic text-sm text-gray-600 mt-4 leading-relaxed">
                            @foreach ($addrLines as $line)
                                <span class="block">{{ $line }}</span>
                            @endforeach
                        </address>
                    @endif
                </div>

                {{-- Contact block --}}
                @if ($gallery->website || $gallery->email || $gallery->phone)
                    <div class="text-sm md:text-right space-y-2 md:min-w-[200px]">
                        <p class="text-xs uppercase tracking-[0.18em] text-gray-500">Contact</p>
                        @if ($gallery->website)
                            <p><a href="{{ $gallery->website }}" target="_blank" rel="noopener" class="text-gray-900 underline hover:no-underline">{{ parse_url($gallery->website, PHP_URL_HOST) ?: $gallery->website }}</a></p>
                        @endif
                        @if ($gallery->email)
                            <p><a href="mailto:{{ $gallery->email }}" class="text-gray-900 hover:underline">{{ $gallery->email }}</a></p>
                        @endif
                        @if ($gallery->phone)
                            <p><a href="tel:{{ preg_replace('/[^+0-9]/', '', $gallery->phone) }}" class="text-gray-900 hover:underline">{{ $gallery->phone }}</a></p>
                        @endif
                    </div>
                @endif
            </header>

            @if ($gallery->description)
                <div class="prose max-w-2xl mb-12">{{ $gallery->description }}</div>
            @endif

            <h2 class="font-serif text-2xl mb-6">Represented artists</h2>
            @if ($gallery->artists->isEmpty())
                <p class="text-gray-500 italic mb-12">No artists yet.</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200 mb-16">
                    @foreach ($gallery->artists as $artist)
                        <a href="{{ route('artists.show', $artist) }}" class="bg-white p-6 hover:bg-gray-50 transition block">
                            @if ($artist->profile_image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->profile_image) }}" alt="" class="w-16 h-16 object-cover rounded-full mb-3">
                            @endif
                            <p class="font-serif text-lg leading-tight">{{ $artist->display_name }}</p>
                            @if ($artist->birth_year)
                                <p class="text-xs text-gray-500 mt-1">b. {{ $artist->birth_year }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($alsoShowing->isNotEmpty())
                <h2 class="font-serif text-2xl mb-2">Also showing works by</h2>
                <p class="text-sm text-gray-500 mb-6">Artists featured by the gallery outside their represented roster.</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200 mb-16">
                    @foreach ($alsoShowing as $artist)
                        <a href="{{ route('artists.show', $artist) }}" class="bg-white p-6 hover:bg-gray-50 transition block">
                            @if ($artist->profile_image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artist->profile_image) }}" alt="" class="w-16 h-16 object-cover rounded-full mb-3">
                            @endif
                            <p class="font-serif text-lg leading-tight">{{ $artist->display_name }}</p>
                            @if ($artist->birth_year)
                                <p class="text-xs text-gray-500 mt-1">b. {{ $artist->birth_year }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif

            <h2 class="font-serif text-2xl mb-6">Presented artworks</h2>
            @if ($artworks->isEmpty())
                <p class="text-gray-500 italic">No published artworks yet.</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach ($artworks as $artwork)
                        <a href="{{ route('artworks.show', $artwork) }}" class="block group">
                            @if ($artwork->primary_image)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                     alt="{{ $artwork->title }}"
                                     loading="lazy"
                                     class="w-full aspect-square object-cover bg-gray-100 mb-3 group-hover:opacity-90 transition">
                            @else
                                <div class="w-full aspect-square bg-gray-100 mb-3 flex items-center justify-center text-xs text-gray-400">no image</div>
                            @endif
                            <p class="text-sm font-medium text-gray-900 leading-tight">{{ $artwork->title }}</p>
                            @if ($artwork->artist)
                                <p class="text-xs text-gray-500 mt-1">{{ $artwork->artist->display_name }}@if ($artwork->year_created), {{ $artwork->year_created }}@endif</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
