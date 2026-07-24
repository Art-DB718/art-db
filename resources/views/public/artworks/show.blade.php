@php
    $seoBits = array_filter([
        $artwork->medium?->name,
        $artwork->year_created,
        ($artwork->height_cm && $artwork->width_cm)
            ? rtrim(rtrim((string) $artwork->height_cm, '0'), '.').'×'.rtrim(rtrim((string) $artwork->width_cm, '0'), '.').' cm'
            : null,
    ]);
    $seoDescription = ($artwork->artist?->display_name ? $artwork->artist->display_name.', ' : '')
        .'“'.$artwork->title.'”'
        .(count($seoBits) ? ' — '.implode(', ', $seoBits) : '')
        .(strip_tags($artwork->description ?? '') ? '. '.\Illuminate\Support\Str::limit(strip_tags($artwork->description), 140) : '');
@endphp
<x-layouts.public
    :title="$artwork->title.' — '.($artwork->artist?->display_name ?? config('app.name', 'ArtDB'))"
    :description="$seoDescription"
    :og-image="$artwork->primary_image">

    <section class="max-w-7xl mx-auto px-6 py-12 md:py-16">

        {{-- Breadcrumb --}}
        <nav class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-10">
            <a href="{{ route('home') }}" class="hover:text-gray-900">Home</a>
            <span class="mx-2">/</span>
            <a href="{{ route('artworks.index') }}" class="hover:text-gray-900">Artworks</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ $artwork->title }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">

            {{-- IMAGES --}}
            <div>
                @if ($artwork->primary_image)
                    <div class="bg-gray-100 overflow-hidden">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                             alt="{{ $artwork->title }}"
                             class="w-full h-auto object-contain">
                    </div>
                @else
                    <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400">
                        no image
                    </div>
                @endif

                @if (is_array($artwork->gallery_images) && count($artwork->gallery_images))
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-4">
                        @foreach ($artwork->gallery_images as $image)
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($image) }}" target="_blank" class="block bg-gray-100 overflow-hidden">
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($image) }}"
                                     alt="{{ $artwork->title }}"
                                     class="w-full aspect-square object-cover hover:opacity-90 transition">
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- INFO --}}
            <div>
                @if ($artwork->artist)
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">
                        <a href="{{ route('artists.show', $artwork->artist) }}" class="hover:text-gray-900">
                            {{ $artwork->artist->display_name }}
                        </a>
                    </p>
                @endif

                <h1 class="font-serif text-3xl md:text-4xl italic mb-2">
                    {{ $artwork->title }}@if ($artwork->year_created), <span class="not-italic">{{ $artwork->year_created }}</span>@endif
                </h1>

                {{-- Attribution — who published this artwork on the platform.
                     Gallery owners get a link to their public gallery page;
                     Collector owners just get their display name (no public
                     collector profile exists); Artist owners are already
                     credited via the artist name above the title. --}}
                @php
                    $owner        = $artwork->owner;
                    $ownerRole    = $owner?->role?->value;
                    $ownerGallery = $ownerRole === 'gallery' ? $owner->gallery : null;
                @endphp
                @if ($ownerGallery && $ownerGallery->is_published)
                    <p class="mt-4 text-sm text-gray-600">
                        <span class="uppercase tracking-[0.18em] text-xs text-gray-500 mr-2">Presented by</span>
                        <a href="{{ route('galleries.show', $ownerGallery) }}"
                           class="text-gray-900 hover:underline font-medium">
                            {{ $ownerGallery->name }}
                        </a>
                    </p>
                @elseif ($ownerRole === 'collector' && $owner)
                    <p class="mt-4 text-sm text-gray-600">
                        <span class="uppercase tracking-[0.18em] text-xs text-gray-500 mr-2">From the collection of</span>
                        <span class="text-gray-900 font-medium">{{ $owner->name }}</span>
                    </p>
                @endif

                <dl class="mt-8 space-y-3 text-sm">
                    @if ($artwork->medium)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Medium</dt>
                            <dd class="col-span-2 text-gray-800">{{ $artwork->medium->name }}</dd>
                        </div>
                    @endif

                    @if ($artwork->genre)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Genre</dt>
                            <dd class="col-span-2 text-gray-800">{{ $artwork->genre->name }}</dd>
                        </div>
                    @endif

                    @if ($artwork->materials)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Materials</dt>
                            <dd class="col-span-2 text-gray-800">{{ $artwork->materials }}</dd>
                        </div>
                    @endif

                    @if ($artwork->height_cm || $artwork->width_cm)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Dimensions</dt>
                            <dd class="col-span-2 text-gray-800">
                                {{ rtrim(rtrim((string) $artwork->height_cm, '0'), '.') }}
                                × {{ rtrim(rtrim((string) $artwork->width_cm, '0'), '.') }}
                                @if ($artwork->depth_cm) × {{ rtrim(rtrim((string) $artwork->depth_cm, '0'), '.') }}@endif
                                cm
                            </dd>
                        </div>
                    @endif

                    @if ($artwork->edition_number && $artwork->edition_total)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Edition</dt>
                            <dd class="col-span-2 text-gray-800">{{ $artwork->edition_number }} / {{ $artwork->edition_total }}</dd>
                        </div>
                    @endif

                    @if ($artwork->is_signed || $artwork->is_dated || $artwork->is_framed)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Details</dt>
                            <dd class="col-span-2 text-gray-800">
                                @php
                                    $bits = [];
                                    if ($artwork->is_signed) $bits[] = 'Signed';
                                    if ($artwork->is_dated)  $bits[] = 'Dated';
                                    if ($artwork->is_framed) $bits[] = 'Framed';
                                @endphp
                                {{ implode(' · ', $bits) }}
                            </dd>
                        </div>
                    @endif

                    @if ($artwork->status)
                        <div class="grid grid-cols-3 gap-4">
                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Status</dt>
                            <dd class="col-span-2 text-gray-800">{{ $artwork->status->name }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-8 pt-8 border-t border-gray-200">
                    @if ($artwork->price && ! $artwork->price_on_request)
                        <div class="flex items-baseline justify-between gap-6 flex-wrap">
                            <p class="font-serif text-2xl">{{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $artwork->currency }}</p>
                            @if (config('services.stripe.secret'))
                                <form method="POST" action="{{ route('checkout.buy', $artwork) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-6 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                                        Buy now
                                    </button>
                                </form>
                            @endif
                        </div>
                    @elseif ($artwork->price_on_request)
                        <p class="font-serif text-2xl text-gray-700">Price on request</p>
                    @endif
                </div>

                {{-- LIKE + SAVE — only when logged in as Gallery or Collector --}}
                @auth
                    @if (! auth()->user()->isArtist())
                        @php
                            $isLiked = auth()->user()->likedArtworks()->where('artwork_id', $artwork->id)->exists();
                            $isSaved = auth()->user()->savedArtworks()->where('artwork_id', $artwork->id)->exists();
                        @endphp
                        <div class="mt-6 flex items-center gap-3">
                            <form method="POST" action="{{ route('artworks.like', $artwork) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 border text-xs uppercase tracking-[0.18em] transition
                                               {{ $isLiked ? 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100' : 'border-gray-300 text-gray-700 hover:border-gray-900' }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $isLiked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                    </svg>
                                    {{ $isLiked ? 'Liked' : 'Like' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('artworks.save', $artwork) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 border text-xs uppercase tracking-[0.18em] transition
                                               {{ $isSaved ? 'bg-gray-900 border-gray-900 text-white hover:bg-gray-700' : 'border-gray-300 text-gray-700 hover:border-gray-900' }}">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $isSaved ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.5">
                                        <path d="M6 2v20l6-4 6 4V2z"/>
                                    </svg>
                                    {{ $isSaved ? 'Saved to collection' : 'Add to my collection' }}
                                </button>
                            </form>
                        </div>
                        @if (session('inquiry_message'))
                            <p class="mt-3 text-xs text-green-700">{{ session('inquiry_message') }}</p>
                        @endif
                    @endif
                @endauth

                @if ($artwork->description)
                    <div class="mt-8 prose prose-sm max-w-none text-gray-700 leading-relaxed">
                        {!! nl2br(e($artwork->description)) !!}
                    </div>
                @endif

                @if (is_array($artwork->tags) && count($artwork->tags))
                    <div class="mt-8">
                        <h3 class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-3">Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($artwork->tags as $tag)
                                <a href="{{ route('artworks.index', ['tag' => $tag]) }}"
                                   class="px-3 py-1 text-xs uppercase tracking-[0.18em] border border-gray-300 text-gray-700 hover:border-gray-900 hover:text-gray-900 transition">
                                    {{ $tag }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($artwork->provenance)
                    <div class="mt-8">
                        <h3 class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Provenance</h3>
                        <p class="text-sm text-gray-700 leading-relaxed">{!! nl2br(e($artwork->provenance)) !!}</p>
                    </div>
                @endif

                {{-- INQUIRY --}}
                <div class="mt-12 pt-10 border-t border-gray-200">
                    <h3 class="font-serif text-2xl mb-2">Inquire</h3>
                    <p class="text-sm text-gray-600 mb-6">Interested in this work? Send us a message — we'll be in touch shortly.</p>

                    @if (session('inquiry_message'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-sm text-green-800">
                            {{ session('inquiry_message') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('artworks.inquire', $artwork) }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="name" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Your name</label>
                            <input type="text" name="name" id="name" required value="{{ old('name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Email</label>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                   class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="message" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Message</label>
                            <textarea name="message" id="message" rows="5" required
                                      class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">{{ old('message', "I'd like to know more about ".$artwork->title.'.') }}</textarea>
                            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-start gap-3 pt-1 cursor-pointer select-none">
                            <input type="checkbox" name="subscribe_newsletter" value="1" {{ old('subscribe_newsletter') ? 'checked' : '' }}
                                   class="mt-0.5 h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700 leading-relaxed">
                                Also subscribe me to the {{ config('app.name', 'Art DB') }} newsletter — occasional updates
                                on new works, artists and exhibitions. You can unsubscribe any time.
                            </span>
                        </label>
                        <button type="submit"
                                class="px-8 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                            Send inquiry
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RELATED --}}
        @if ($related->isNotEmpty())
            <div class="mt-24 pt-16 border-t border-gray-200">
                <h2 class="font-serif text-2xl md:text-3xl mb-10">More by {{ $artwork->artist?->display_name ?? 'this artist' }}</h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-10">
                    @foreach ($related as $other)
                        <a href="{{ route('artworks.show', $other) }}" class="block group">
                            @if ($other->primary_image)
                                <div class="overflow-hidden bg-gray-100">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($other->primary_image) }}"
                                         alt="{{ $other->title }}"
                                         class="w-full aspect-square object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                </div>
                            @else
                                <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400 text-sm">no image</div>
                            @endif
                            <p class="mt-3 text-sm font-semibold">{{ $other->artist?->display_name ?? '—' }}</p>
                            <p class="text-sm text-gray-600 italic">{{ $other->title }}@if ($other->year_created), {{ $other->year_created }}@endif</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </section>

</x-layouts.public>
