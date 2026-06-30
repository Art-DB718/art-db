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

            <header class="mt-6 mb-10 flex flex-col md:flex-row md:items-end gap-6 border-b border-gray-200 pb-8">
                @if ($gallery->logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($gallery->logo) }}" alt="{{ $gallery->name }}" class="w-24 h-24 object-contain">
                @endif
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">Gallery</p>
                    <h1 class="font-serif text-4xl md:text-5xl">{{ $gallery->name }}</h1>
                    @if ($gallery->city || $gallery->country)
                        <p class="text-gray-600 mt-2">{{ collect([$gallery->city, $gallery->country?->name])->filter()->implode(' · ') }}</p>
                    @endif
                </div>
                <div class="text-sm text-right space-y-1">
                    @if ($gallery->website)<p><a href="{{ $gallery->website }}" target="_blank" rel="noopener" class="underline hover:no-underline">{{ parse_url($gallery->website, PHP_URL_HOST) ?: $gallery->website }}</a></p>@endif
                    @if ($gallery->email)<p><a href="mailto:{{ $gallery->email }}" class="hover:underline">{{ $gallery->email }}</a></p>@endif
                    @if ($gallery->phone)<p>{{ $gallery->phone }}</p>@endif
                </div>
            </header>

            @if ($gallery->description)
                <div class="prose max-w-2xl mb-12">{{ $gallery->description }}</div>
            @endif

            <h2 class="font-serif text-2xl mb-6">Represented artists</h2>
            @if ($gallery->artists->isEmpty())
                <p class="text-gray-500 italic">No artists yet.</p>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-gray-200 border border-gray-200">
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
        </div>
    </section>
</x-layouts.public>
