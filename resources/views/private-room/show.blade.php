@php
    $recipientLabel = $room->recipient_name
        ?: $room->recipient?->display_name
        ?: ($room->recipient_email ?: 'the addressee');
@endphp
<x-layouts.private-room :title="$room->title" :recipient-label="$recipientLabel">

    {{-- COVER + WELCOME --}}
    <section class="border-b border-gray-200">
        <div class="max-w-6xl mx-auto px-6 py-16 md:py-20">
            @if ($room->cover_image)
                <div class="mb-10 overflow-hidden bg-gray-100">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($room->cover_image) }}"
                         alt="{{ $room->title }}"
                         class="w-full aspect-[3/1] object-cover">
                </div>
            @endif

            <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-4">For {{ $recipientLabel }}</p>
            <h1 class="font-serif text-4xl md:text-5xl tracking-tight">{{ $room->title }}</h1>

            @if ($room->welcome_message)
                <div class="mt-8 prose prose-sm max-w-2xl text-gray-700 leading-relaxed">
                    {!! nl2br(e($room->welcome_message)) !!}
                </div>
            @endif

            @if ($room->expires_at)
                <p class="mt-6 text-xs text-gray-400">
                    This viewing is available until {{ $room->expires_at->format('d.m.Y') }}.
                </p>
            @endif
        </div>
    </section>

    {{-- WORKS --}}
    <section class="py-16">
        <div class="max-w-6xl mx-auto px-6">

            @if ($room->artworks->isEmpty())
                <div class="py-24 text-center border border-dashed border-gray-200">
                    <p class="text-gray-500">No works selected for this viewing yet.</p>
                </div>
            @else
                @if ($room->discount_percent)
                    <div class="mb-10 px-4 py-3 bg-gray-900 text-white inline-block">
                        <span class="text-xs uppercase tracking-[0.18em]">Private viewing discount</span>
                        <span class="ml-3 font-serif text-lg">−{{ $room->discount_percent }}%</span>
                    </div>
                @endif

                <div class="space-y-24">
                    @foreach ($room->artworks as $artwork)
                        @php
                            // Effective price logika:
                            // 1) pivot display_price má prioritu
                            // 2) inak base price → discount_percent
                            $basePrice = $artwork->pivot->display_price
                                ?? ($artwork->price_on_request ? null : $artwork->price);
                            $currency  = $artwork->pivot->currency ?? $artwork->currency ?? 'EUR';

                            $effective = null;
                            if ($basePrice !== null && $room->show_prices) {
                                $effective = (float) $basePrice;
                                if ($room->discount_percent && ! $artwork->pivot->display_price) {
                                    $effective = round($effective * (1 - $room->discount_percent / 100), 2);
                                }
                            }
                        @endphp

                        <article class="grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
                            <div>
                                @if ($artwork->primary_image)
                                    <div class="overflow-hidden bg-gray-100">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($artwork->primary_image) }}"
                                             alt="{{ $artwork->title }}"
                                             class="w-full h-auto object-contain">
                                    </div>
                                @else
                                    <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-400 text-sm">no image</div>
                                @endif
                            </div>

                            <div>
                                @if ($artwork->artist)
                                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500 mb-2">{{ $artwork->artist->display_name }}</p>
                                @endif

                                <h2 class="font-serif text-3xl italic mb-1">
                                    {{ $artwork->title }}@if ($artwork->year_created), <span class="not-italic">{{ $artwork->year_created }}</span>@endif
                                </h2>

                                <dl class="mt-6 space-y-3 text-sm">
                                    @if ($artwork->medium)
                                        <div class="grid grid-cols-3 gap-4">
                                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Medium</dt>
                                            <dd class="col-span-2 text-gray-800">{{ $artwork->medium->name }}</dd>
                                        </div>
                                    @endif
                                    @if ($artwork->height_cm || $artwork->width_cm)
                                        <div class="grid grid-cols-3 gap-4">
                                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Dimensions</dt>
                                            <dd class="col-span-2 text-gray-800">
                                                {{ rtrim(rtrim((string) $artwork->height_cm, '0'), '.') }} ×
                                                {{ rtrim(rtrim((string) $artwork->width_cm, '0'), '.') }}
                                                @if ($artwork->depth_cm) × {{ rtrim(rtrim((string) $artwork->depth_cm, '0'), '.') }}@endif cm
                                            </dd>
                                        </div>
                                    @endif
                                    @if ($artwork->edition_number && $artwork->edition_total)
                                        <div class="grid grid-cols-3 gap-4">
                                            <dt class="text-gray-500 uppercase tracking-[0.18em] text-xs">Edition</dt>
                                            <dd class="col-span-2 text-gray-800">{{ $artwork->edition_number }} / {{ $artwork->edition_total }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                {{-- PRICE --}}
                                <div class="mt-8 pt-8 border-t border-gray-200">
                                    @if ($effective !== null)
                                        <p class="font-serif text-2xl">{{ number_format($effective, 0, '.', ' ') }} {{ $currency }}</p>
                                        @if ($room->discount_percent && ! $artwork->pivot->display_price && $artwork->price)
                                            <p class="mt-1 text-sm text-gray-400 line-through">
                                                {{ number_format((float) $artwork->price, 0, '.', ' ') }} {{ $currency }}
                                            </p>
                                        @endif
                                    @else
                                        <p class="font-serif text-2xl text-gray-700">Price on request</p>
                                    @endif
                                </div>

                                @if ($artwork->pivot->private_note)
                                    <div class="mt-6 p-4 bg-gray-50 border-l-2 border-gray-300">
                                        <p class="text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Curator's note</p>
                                        <p class="text-sm text-gray-700">{{ $artwork->pivot->private_note }}</p>
                                    </div>
                                @endif

                                @if ($artwork->description)
                                    <div class="mt-6 prose prose-sm max-w-none text-gray-700">
                                        {!! nl2br(e($artwork->description)) !!}
                                    </div>
                                @endif

                                @if ($room->allow_inquiry)
                                    <div class="mt-6">
                                        <a href="#inquire" data-artwork-id="{{ $artwork->id }}"
                                           class="js-inquire-trigger inline-block px-6 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                                            Inquire about this work
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- INQUIRY FORM --}}
    @if ($room->allow_inquiry)
        <section id="inquire" class="py-16 bg-gray-50 border-t border-gray-200">
            <div class="max-w-2xl mx-auto px-6">
                <h2 class="font-serif text-3xl mb-2">Inquire</h2>
                <p class="text-sm text-gray-600 mb-8">
                    Tell us which work interests you, or ask anything else — we'll get back to you shortly.
                </p>

                @if (session('inquiry_message'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 text-sm text-green-800">
                        {{ session('inquiry_message') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('private-room.inquire', $room->token) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="artwork_id" id="inquire-artwork-id" value="">

                    <div>
                        <label for="name" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Your name</label>
                        <input type="text" name="name" id="name" required
                               value="{{ old('name', $room->recipient_name ?: $room->recipient?->display_name) }}"
                               class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Email</label>
                        <input type="email" name="email" id="email" required
                               value="{{ old('email', $room->recipient_email ?: $room->recipient?->email) }}"
                               class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">
                        @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="message" class="block text-xs uppercase tracking-[0.18em] text-gray-500 mb-2">Message</label>
                        <textarea name="message" id="message" rows="5" required
                                  class="w-full px-3 py-2 border border-gray-300 text-sm focus:outline-none focus:border-gray-900">{{ old('message') }}</textarea>
                        @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="px-8 py-3 bg-gray-900 text-white text-xs uppercase tracking-[0.18em] hover:bg-gray-700 transition">
                        Send inquiry
                    </button>
                </form>
            </div>
        </section>

        <script>
            // Pri kliknutí na "Inquire about this work" pri jednom diele prefilluj artwork_id.
            document.querySelectorAll('.js-inquire-trigger').forEach(el => {
                el.addEventListener('click', e => {
                    const id = e.currentTarget.dataset.artworkId;
                    const input = document.getElementById('inquire-artwork-id');
                    if (input) input.value = id;
                });
            });
        </script>
    @endif

</x-layouts.private-room>
